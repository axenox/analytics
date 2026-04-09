/*
 * This tracker can be pulled into a page as a <script> tag to send analytics events to a specified endpoint. 
 * 
 * It intercepts XHR calls, extracts relevant data, and sends it as a tracker.
 */
(function () {
    const BEACON_ID = "[#~tracker_uid#]";
    const ENDPOINT = "[#~url#]";

    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    const EXCLUDED_PATTERNS = [
        new RegExp("^" + escapeRegex(ENDPOINT)), // prevent recursion
        /vendor\/.*/,
        /api\/pwa\/errors/,
        /.*\.properties/,
        /.*\/context/,
        /.*\/manifest\.json.*/
    ];

    const SAMPLE_RATE = 1.0;

    function shouldTrack(url) {
        if (!url) return false;
        return !EXCLUDED_PATTERNS.some(rx => rx.test(url));
    }

    function nowTs() {
        return new Date().toISOString();
    }

    function getDeviceId() {
        try {
            return (window.exfPWA && window.exfPWA.getDeviceId())
                ? window.exfPWA.getDeviceId()
                : "unknown";
        } catch (e) {
            return "unknown";
        }
    }

    function parseQueryString(qs) {
        const params = new URLSearchParams(qs);
        const result = {};

        params.forEach((value, key) => {
            assign(result, key, value);
        });

        return result;
    }

    function assign(obj, key, value) {
        // Split "data[columns][0][attribute_alias]" → ["data", "columns", "0", "attribute_alias"]
        const parts = key
            .replace(/\]/g, '')
            .split('[');

        let current = obj;

        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            const nextPart = parts[i + 1];

            const isLast = i === parts.length - 1;
            const isArrayIndex = !isNaN(nextPart);

            if (isLast) {
                if (Array.isArray(current)) {
                    current[part] = value;
                } else {
                    current[part] = value;
                }
            } else {
                if (!(part in current)) {
                    // decide object vs array
                    current[part] = isArrayIndex ? [] : {};
                }

                current = current[part];
            }
        }
    }

    function tryParseJSON(str) {
        try {
            return JSON.parse(str);
        } catch (e) {
            return null;
        }
    }

    function getCurrentPageAlias() {
        try {
            const url = new URL(window.location.href);
            const hash = url.hash;
            
            // Check if we have a hash with #/
            if (hash && hash.startsWith('#/')) {
                // Extract part after #/ until next / or end
                const afterHash = hash.substring(2); // Remove #/
                const pageMatch = afterHash.split('/')[0];
                if (pageMatch) return pageMatch;
            }
            
            // Fallback: extract filename without extension
            const pathname = url.pathname;
            const filename = pathname.split('/').pop(); // Get last part
            return filename.replace(/\.[^/.]+$/, ''); // Remove extension
        } catch (e) {
            return '';
        }
    }

    function extractDataSummary(payloadObj, bIsResponse = false) {
        if (!payloadObj) return {};

        const data = {};

        // Normalize fields based on your RPC structure
        if (payloadObj.data) {
            data.object = payloadObj.data?.oId || payloadObj.data.object_alias;
        }

        if (bIsResponse) {
            // Extract columns: data[rows][0] -> keys 
            data.columns = payloadObj.rows?.[0] ? Object.keys(payloadObj.rows[0]) : [];
        }
        else {
            // Extract columns: data[columns][N][0] (for example attriute_alias, column_name)]
            data.columns = (payloadObj.data?.columns || []).map(col => Object.values(col)[0]);
        }

        // Extract filters: data[filters][conditions][N][expression]
        data.filters = (payloadObj.data?.filters?.conditions || [])
            .map(c => c.expression)
            .filter(Boolean);

        // Extract Sorters:
        if (payloadObj.sort) data.sorters = [payloadObj.sort];

        // Pagination
        data.limit = payloadObj.length ? parseInt(payloadObj.length, 10) : undefined;
        data.offset = payloadObj.start ? parseInt(payloadObj.start, 10) : undefined;
        data.rows = payloadObj.rows ? payloadObj.rows.length : null;

        return data;
    }

    function sendEvent(event) {
        // Todo sah: maybe add proper rate limiting or pooling?
        if (Math.random() > SAMPLE_RATE) return;

        const payload = {
            v: 1,
            dId: getDeviceId(),
            events: [event]
        };

        const blob = new Blob([JSON.stringify(payload)], {
            type: "application/json"
        });

        navigator.sendBeacon(ENDPOINT, blob);
    }

    // ---- XHR INTERCEPT ----

    const origOpen = XMLHttpRequest.prototype.open;
    const origSend = XMLHttpRequest.prototype.send;
    const origSetRequestHeader = XMLHttpRequest.prototype.setRequestHeader;

    XMLHttpRequest.prototype.open = function (method, url) {
        this._rum = {
            method: method,
            url: url,
            start: 0,
            requestHeaders: {}
        };

        return origOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.setRequestHeader = function (header, value) {
        if (this._rum) {
            this._rum.requestHeaders[header.toLowerCase()] = value;
        }
        return origSetRequestHeader.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function (body) {
        if (!this._rum || !shouldTrack(this._rum.url)) {
            return origSend.apply(this, arguments);
        }

        this._rum.start = performance.now();
        this._rum.body = body;

        this.addEventListener("loadend", () => {
            try {
                const duration = performance.now() - this._rum.start;

                let requestPayload = null;

                // Parse body
                if (typeof this._rum.body === "string") {
                    requestPayload =
                        tryParseJSON(this._rum.body) ||
                        parseQueryString(this._rum.body);
                } else if (this._rum.body instanceof FormData) {
                    const obj = {};
                    this._rum.body.forEach((v, k) => (obj[k] = v));
                    requestPayload = obj;
                }

                // Also parse URL params
                const urlObj = new URL(this._rum.url, window.location.origin);
                const urlParams = parseQueryString(urlObj.search);

                const payload = requestPayload || urlParams;

                // Parse response safely
                let responseJson = {};
                try {
                    responseJson = JSON.parse(this.responseText);
                } catch (e) {}
                
                let type = "ajax";
                switch (true) {
                    case this._rum.url.includes("/viewcontroller/"):
                        type = 'widget';
                        if (payload && !payload.action) {
                            payload.action = 'exface.Core.ShowWidget';
                        }
                        // try and extract widget id from url
                        if (payload && !payload.element) {
                            const sWidgetId = this._rum.url.match(/\/([^/]+)\.viewcontroller\.js/);
                            if (sWidgetId) payload.element = sWidgetId[1];
                        }
                        break;
                    case payload?.action !== undefined:
                        type = 'action';
                        break;
                }

                const event = {
                    ts: nowTs(),
                    type: type,
                    page: getCurrentPageAlias(),
                    widget: payload?.element || "",
                    action: {
                        alias: payload?.action,
                        object: payload?.object
                    },
                    request: extractDataSummary(payload),
                    response: extractDataSummary(responseJson, true),
                    duration: duration,
                    url: this._rum.url,
                    method: this._rum.method
                };

                // Extract X-Request-ID
                event.xrId =
                    this.getResponseHeader("X-Request-ID") ||
                    this.getResponseHeader("x-request-id");

                
                // log server/sql errors 
                if (responseJson.error) {
                    event.response_error = {
                        code: responseJson.error.code,
                        title: responseJson.error.title,
                        message: responseJson.error.message,
                        type: responseJson.error.type,
                        logid: responseJson.error.logid,
                    };
                }

                // Extract X-Request-ID
                const serverTiming =
                    this.getResponseHeader("Server-Timing") ||
                    this.getResponseHeader("server-timing");
                if (typeof serverTiming === "string") {
                    event.duration_server = parseFloat(serverTiming.split('=').pop())
                }

                sendEvent(event);

            } catch (e) {
                // catch js errors and send as event
                sendEvent({
                    ts: nowTs(),
                    type: "error",
                    js_error: { message: e.message, stack: e.stack },
                });

                console.warn(e);
            }
        });

        return origSend.apply(this, arguments);
    };

})();