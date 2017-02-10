<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

namespace Terminal42\FolderpageBundle\PageType;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\PageRegular;

class FolderPage extends PageRegular
{
    /**
     * Generate a 404 page if this page is rendered in the frontend.
     *
     * @param \PageModel $objPage
     * @param boolean    $blnCheckRequest
     *
     * @throws PageNotFoundException
     */
    public function generate($objPage, $blnCheckRequest=false)
    {
        throw new PageNotFoundException();
    }
}
