<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-folderpage
 */

class PageFolder extends PageRegular
{

	public function generate($objPage)
	{
		$objHandler = new $GLOBALS['TL_PTY']['error_404']();
		$objHandler->generate($objPage->id);
	}
}
