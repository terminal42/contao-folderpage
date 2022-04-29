<?php

declare(strict_types=1);

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
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * Show a warning if there are non-root pages on the top-level.
     */
    public function onGetSystemMessages(): string
    {
        if ($this->page->hasInvalidTopLevels()) {
            return '<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['topLevelRegular'].'</p>';
        }

        return '';
    }
}
