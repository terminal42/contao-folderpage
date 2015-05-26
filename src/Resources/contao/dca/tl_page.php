<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = function ($dc) {
    if ($dc->activeRecord && $dc->activeRecord->type == 'folder') {
        \System::getContainer()
            ->get('terminal42.folderpage.dcamanager')
            ->configureFolderPage($dc->id)
        ;
    }
};

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] as $k => $callback) {
	if ($callback[1] == 'addBreadcrumb') {
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = function () {
            \System::getContainer()
                ->get('terminal42.folderpage.dcamanager')
                ->addBreadcrumb()
            ;
        };
	}

	if ($callback[1] == 'showFallbackWarning') {
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = function () {
            \System::getContainer()
                ->get('terminal42.folderpage.dcamanager')
                ->showFallbackWarning()
            ;
        };
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
    $GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0] = function ($varValue, DataContainer $dc) {
        return \System::getContainer()
            ->get('terminal42.folderpage.dcamanager')
            ->checkRootType($varValue, $dc->activeRecord)
        ;
    };
}
