<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\PageModel;

class PageStatusIconListener
{
    /**
     * Return our custom image for the folder page type.
     *
     * @param PageModel $page
     *
     * @return string
     */
    public function onGetPageStatusIcon($page, string $image)
    {
        if ('folder' === $page->type) {
            return 'folderC.svg';
        }

        return $image;
    }
}
