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
 * Config.
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = ['terminal42_folderpage.datacontainer.page', 'configureFolderPage'];

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] as $k => $callback) {
    if (!\is_array($callback) || 'tl_page' !== $callback[0]) {
        continue;
    }

    if ('addBreadcrumb' === $callback[1]) {
        $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = ['terminal42_folderpage.datacontainer.page', 'addBreadcrumb'];
    }

    if ('showFallbackWarning' === $callback[1]) {
        $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = ['terminal42_folderpage.datacontainer.page', 'showFallbackWarning'];
    }

    if ('setRootType' === $callback[1]) {
        $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = ['terminal42_folderpage.datacontainer.page', 'setRootType'];
    }
}

/*
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['folder'] = '{title_legend},title,type;{meta_legend},pageTitle;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,hide,guests;{publish_legend},published';

/*
 * Fields
 */
if (isset($GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback']) && 'checkRootType' === $GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0][1]) {
    $GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0] = ['terminal42_folderpage.datacontainer.page', 'onSaveType'];
}

$GLOBALS['TL_DCA']['tl_page']['fields']['type']['options_callback'][0] = 'terminal42_folderpage.datacontainer.page';

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][] = ['terminal42_folderpage.datacontainer.page', 'adjustAlias'];
array_unshift($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'], ['terminal42_folderpage.datacontainer.page', 'emptyFolderAliases']);

$GLOBALS['TL_DCA']['tl_page']['fields']['hide']['eval']['tl_class'] = 'clr w50';
