<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class NoRobotsMigration extends AbstractMigration
{
    private Connection $connection;

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

        $matches = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_page WHERE `type` = 'folder' AND `robots` != 'noindex,nofollow'"
        );

        return $matches > 0;
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function run(): MigrationResult
    {
        $statement = $this->connection->prepare(
            "UPDATE `tl_page` SET `robots` = 'noindex,nofollow' WHERE `type` = 'folder'"
        );
        $count = $statement->executeStatement();

        return $this->createResult(
            true,
            'Updated '.$count.' folder pages.'
        );
    }
}
