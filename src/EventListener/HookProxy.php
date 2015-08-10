<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

namespace Terminal42\FolderpageBundle;

class HookProxy
{
    public function getSystemMessages()
    {
        return \System::getContainer()
            ->get('terminal42_folderpage.listener.system_messages')
            ->topLevelRoot()
        ;
    }

    public function getPageStatusIcon($page, $image)
    {
        return \System::getContainer()
            ->get('terminal42_folderpage.listener.page_status_icon')
            ->getFolderpageIcon($page, $image)
        ;
    }
}
