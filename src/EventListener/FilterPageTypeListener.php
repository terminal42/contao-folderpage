<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Doctrine\DBAL\Connection;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * Allows a folder page on the top level of the page tree.
 *
 * @ServiceTag("kernel.event_listener")
 */
class FilterPageTypeListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(FilterPageTypeEvent $event): void
    {
        $dc = $event->getDataContainer();

        if (!$dc->activeRecord) {
            return;
        }

        // The first level can only have root pages (see #6360)
        if (!$dc->activeRecord->pid) {
            $event->addOption('folder');

            return;
        }

        $isRootFolder = true;
        $pid = $dc->activeRecord->pid;

        do {
            $parentPage = $this->connection->fetchAssociative('SELECT pid, type FROM tl_page WHERE id=?', [$pid]);

            if (false === $parentPage) {
                break;
            }

            if ('folder' !== $parentPage['type']) {
                $isRootFolder = false;
                break;
            }

            $pid = $parentPage['pid'];
        } while ($pid > 0);

        if ($isRootFolder) {
            $event->setOptions(['root', 'folder']);
        }
    }
}
