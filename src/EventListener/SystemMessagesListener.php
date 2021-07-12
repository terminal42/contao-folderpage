<?php


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
