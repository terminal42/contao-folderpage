<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-folderpage
 */

class FolderPageMessages extends Messages
{

	/**
	 * Show a warning if there are non-root pages on the top-level
	 * @return string
	 */
	public function topLevelRoot()
	{
		$objCount = $this->Database->execute("SELECT COUNT(*) AS count FROM tl_page WHERE pid=0 AND type!='root' AND type!='folder'");

		if ($objCount->count > 0)
		{
			return '<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['topLevelRegular'] . '</p>';
		}

		return '';
	}
}
