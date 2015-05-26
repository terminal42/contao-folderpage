<?php

namespace Terminal42\FolderpageBundle;

use Contao\System;

class HookManager
{
    /**
     * Show a warning if there are non-root pages on the top-level
     *
     * @return string
     */
    public function topLevelRoot()
    {
        /** @var DcaManager $helper */
        $helper = System::getContainer()->get('terminal42.folderpage.dcamanager');

        if ($helper->hasInvalidTopLevels()) {
            return '<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['topLevelRegular'] . '</p>';
        }

        return '';
    }

    /**
     * Return our custom image for the folder page type.
     *
     * @param object $page
     * @param string $image
     *
     * @return string
     */
    public function getFolderPageIcon($page, $image)
    {
        if ($page->type == 'folder') {
            return 'bundles/terminal42folderpage/folder.gif';
        }

        return $image;
    }
}
