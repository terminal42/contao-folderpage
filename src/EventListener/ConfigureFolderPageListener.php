<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
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
    public function __invoke(DataContainer $dc): void
    {
        if (null === $dc->activeRecord || 'folder' !== $dc->activeRecord->type) {
            return;
        }

        $data = [
            'alias' => '',
            'start' => '',
            'stop' => '',
            'robots' => 'noindex,nofollow',
        ];

        $columns = $this->connection->createSchemaManager()->listTableColumns('tl_page');

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
