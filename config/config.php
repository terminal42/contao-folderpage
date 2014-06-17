<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2014, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-folderpage
 */

/**
 * Page type
 */
$GLOBALS['TL_PTY']['folder'] = 'PageFolder';

/**
 * Replace core Hooks
 */
foreach( $GLOBALS['TL_HOOKS']['getSystemMessages'] as $k => $v )
{
	if ($v[0] == 'Messages')
	{
		$GLOBALS['TL_HOOKS']['getSystemMessages'][$k][0] = 'FolderPageMessages';
	}
}
