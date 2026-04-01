<?php
namespace axenox\Analytics;

use axenox\Analytics\Facades\AnalyticsFacade;
use exface\Core\CommonLogic\AppInstallers\MySqlDatabaseInstaller;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\Factories\FacadeFactory;
use exface\Core\Interfaces\InstallerInterface;
use exface\Core\CommonLogic\Model\App;

class AnalyticsApp extends App
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Model\App::getInstaller()
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // SQL schema
        // TODO Add support for other DB types?
        $schema_installer = new MySqlDatabaseInstaller($this->getSelector());
        $schema_installer
            ->setDataSourceSelector('0x11f181e1f7d1d76881e1025041000001')
            ->setFoldersWithMigrations(['InitDB','Migrations'])
            ->setFoldersWithStaticSql(['Views']);
        $installer->addInstaller($schema_installer);

        // Deployer facade
        $facadeInstaller = new HttpFacadeInstaller($this->getSelector());
        $facadeInstaller->setFacade(FacadeFactory::createFromString(AnalyticsFacade::class, $this->getWorkbench()));
        $installer->addInstaller($facadeInstaller);
        
        return $installer;
    }
}