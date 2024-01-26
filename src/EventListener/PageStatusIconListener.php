<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Database\Result;
use Contao\PageModel;

#[AsHook('getPageStatusIcon')]
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
