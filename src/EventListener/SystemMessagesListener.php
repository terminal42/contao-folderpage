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

use Terminal42\FolderpageBundle\DataContainer\Page;

class SystemMessagesListener
{
    /**
     * @var Page
     */
    private $page;

    /**
     * Constructor.
     *
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * Show a warning if there are non-root pages on the top-level.
     *
     * @return string
     */
    public function onGetSystemMessages()
    {
        if ($this->page->hasInvalidTopLevels()) {
            return '<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['topLevelRegular'].'</p>';
        }

        return '';
    }
}
