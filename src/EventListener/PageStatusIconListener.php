<?php

/*
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

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
            return 'bundles/terminal42folderpage/folder.gif';
        }

        return $image;
    }
}
