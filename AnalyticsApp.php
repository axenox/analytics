<?php
namespace axenox\Analytics;

use exface\Core\Factories\DataSourceFactory;
use exface\Core\Interfaces\InstallerInterface;
use exface\Core\CommonLogic\Model\App;
use exface\Core\CommonLogic\AppInstallers\AbstractSqlDatabaseInstaller;

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
        
        return $installer;
    }
}