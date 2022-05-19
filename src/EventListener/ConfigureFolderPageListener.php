<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * @Callback(table="tl_page", target="config.onsubmit")
 */
class ConfigureFolderPageListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sets fixed configuration for a folder page.
     */
    public function __invoke(DataContainer $dc): void
    {
        if (null === $dc->activeRecord || 'folder' !== $dc->activeRecord->type) {
            return;
        }

        $this->connection->update(
            'tl_page',
            [
                'alias' => '',
                'noSearch' => '1',
                'sitemap' => 'map_never',
                'start' => '',
                'stop' => '',
                'robots' => 'noindex,nofollow',
            ],
            [
                'id' => $dc->id,
            ]
        );
    }
}
