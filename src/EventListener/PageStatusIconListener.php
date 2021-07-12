<?php


namespace Terminal42\FolderpageBundle\EventListener;

class PageStatusIconListener
{
    /**
     * Return our custom image for the folder page type.
     *
     * @param object $page
     * @param string $image
     *
     * @return string
     */
    public function onGetPageStatusIcon($page, $image)
    {
        if ('folder' === $page->type) {
            return 'folderC.svg';
        }

        return $image;
    }
}
