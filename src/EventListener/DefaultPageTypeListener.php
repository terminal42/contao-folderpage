<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Input;
use Doctrine\DBAL\Connection;

/**
 * Sets the default value of tl_page.type. In Contao, a root type is only allowed
 * if pid=0, but with folderpage the PID can also be a folder page ID.
 *
 * @Hook("loadDataContainer")
 */
class DefaultPageTypeListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(string $table): void
    {
        if ('tl_page' !== $table) {
            return;
        }

        foreach (($GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] ?? []) as $k => $callback) {
            if (!\is_array($callback) || 'tl_page' !== $callback[0] || 'setRootType' !== $callback[1]) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = fn (...$args) => $this->setRootType(...$args);

            return;
        }
    }

    private function setRootType(): void
    {
        if ('create' !== Input::get('act')) {
            return;
        }

        // Insert into
        if (0 === (int) Input::get('pid')) {
            $GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';

            return;
        }

        $isRootFolder = true;
        $pid = (int) Input::get('pid');
        $skipFirst = 1 === (int) Input::get('mode');

        do {
            $parentPage = $this->connection->fetchAssociative('SELECT pid, type FROM tl_page WHERE id=?', [$pid]);

            if (false === $parentPage) {
                break;
            }

            $pid = $parentPage['pid'];

            // Paste after the given page: pasting after root page inside a folder is ok.
            if ($skipFirst) {
                $skipFirst = false;
                continue;
            }

            if ('folder' !== $parentPage['type']) {
                $isRootFolder = false;
                break;
            }
        } while ($pid > 0);

        if ($isRootFolder) {
            $GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';
        }
    }
}
