<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Database\Result;
use Contao\PageModel;

/**
 * @Hook("getPageStatusIcon")
 */
class PageStatusIconListener
{
    /**
     * Return our custom image for the folder page type.
     *
     * @param PageModel|Result|\stdClass $page
     */
    public function __invoke($page, string $image): string
    {
        if ('folder' === $page->type) {
            return 'folderC.svg';
        }

        return $image;
    }
}
