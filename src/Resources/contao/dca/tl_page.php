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
 * Config
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('\\Terminal42\\FolderpageBundle\\DcaManager', 'configureFolderPage');

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] as $k => $callback) {
	if ($callback[1] == 'addBreadcrumb') {
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k][0] = '\\Terminal42\\FolderpageBundle\\DcaManager';
	}

	if ($callback[1] == 'showFallbackWarning') {
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k][0] = '\\Terminal42\\FolderpageBundle\\DcaManager';
	}
}


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['folder'] = '{title_legend},title,type;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},guests';


/**
 * Fields
 */
if ($GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0][1] == 'checkRootType') {
	$GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0][0] = '\\Terminal42\\FolderpageBundle\\DcaManager';
}



