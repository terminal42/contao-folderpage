<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
class ConfigureFolderPageListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Sets fixed configuration for a folder page.
     */
    public function __invoke(DC_Table $dc): void
    {
        if (($activeRecord = $dc->getActiveRecord()) === null) {
            return;
        }

        if (($activeRecord['type'] ?? null) !== 'folder') {
            return;
        }

        $data = [
            'alias' => '',
            'start' => '',
            'stop' => '',
            'robots' => 'noindex,nofollow',
        ];

        $schemaManager = $this->connection->createSchemaManager();

        // @phpstan-ignore-next-line
        if (method_exists($schemaManager, 'introspectTableColumnsByUnquotedName')) {
            $columns = $schemaManager->introspectTableColumnsByUnquotedName('tl_page');
        } else {
            $columns = $schemaManager->listTableColumns('tl_page');
        }

        if (\array_key_exists('noSearch', $columns)) {
            $data['noSearch'] = '1';
        } elseif (\array_key_exists('searchIndexer', $columns)) {
            $data['searchIndexer'] = 'never_index';
        }

        $this->connection->update(
            'tl_page',
            $data,
            ['id' => $dc->id],
        );
    }
}
