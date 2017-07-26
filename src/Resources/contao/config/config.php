<?php

/*
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

/*
 * Page type.
 */
$GLOBALS['TL_PTY']['folder'] = 'Terminal42\\FolderpageBundle\\PageType\\FolderPage';

/*
 * Replace core Hooks
 */
foreach ($GLOBALS['TL_HOOKS']['getSystemMessages'] as $k => $v) {
    if ('Messages' === $v[0] && 'topLevelRoot' === $v[1]) {
        $GLOBALS['TL_HOOKS']['getSystemMessages'][$k][0] = 'terminal42_folderpage.listener.system_messages';
        $GLOBALS['TL_HOOKS']['getSystemMessages'][$k][1] = 'onGetSystemMessages';
        break;
    }
}

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getPageStatusIcon'][] = ['terminal42_folderpage.listener.page_status_icon', 'onGetPageStatusIcon'];
