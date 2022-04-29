<?php

declare(strict_types=1);

/*
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2022, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

namespace Terminal42\FolderpageBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class NoRobotsMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $statement = $this->connection->prepare(
            "SELECT * FROM tl_page WHERE `type` = 'folder' AND `robots` != 'noindex,nofollow'"
        );

        return $statement->executeStatement() > 0;
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function run(): MigrationResult
    {
        $statement = $this->connection->prepare(
            "UPDATE `tl_page` SET `robots` = 'noindex,nofollow' WHERE `type` = 'folder' AND `robots` != 'noindex,nofollow'"
        );
        $count = $statement->executeStatement();

        return $this->createResult(
            true,
            'Updated '.$count.' folder pages.'
        );
    }
}
