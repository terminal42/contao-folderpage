<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-folderpage
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'FolderPageMessages'         => 'system/modules/folderpage/FolderPageMessages.php',
    'PageFolder'                 => 'system/modules/folderpage/PageFolder.php',
));
