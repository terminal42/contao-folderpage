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
 * Config
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('tl_page_folderpage', 'configureFolderPage');

foreach( $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] as $k => $callback )
{
	if ($callback[1] == 'addBreadcrumb')
	{
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k][0] = 'tl_page_folderpage';
	}

	if ($callback[1] == 'showFallbackWarning')
	{
		$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k][0] = 'tl_page_folderpage';
	}
}


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['folder'] = '{title_legend},title,type;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},guests';


/**
 * Fields
 */
if ($GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0][1] == 'checkRootType')
{
	$GLOBALS['TL_DCA']['tl_page']['fields']['type']['save_callback'][0][0] = 'tl_page_folderpage';
}


class tl_page_folderpage extends tl_page
{

	/**
	 * Override the default breadcrumb menu, we want to show pages before root pages
	 */
	public function addBreadcrumb()
	{
		// Set a new node
		if (isset($_GET['node']))
		{
			$this->Session->set('tl_page_node', $this->Input->get('node'));
			$this->redirect(preg_replace('/&node=[^&]*/', '', $this->Environment->request));
		}

		$intNode = $this->Session->get('tl_page_node');

		if ($intNode < 1)
		{
			return;
		}

		$arrIds = array();
		$arrLinks = array();

		// Generate breadcrumb trail
		if ($intNode)
		{
			$intId = $intNode;

			do
			{
				$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
								->limit(1)
								->execute($intId);

				if ($objPage->numRows < 1)
				{
					// Currently selected page does not exits
					if ($intId == $intNode)
					{
						$this->Session->set('tl_page_node', 0);
						return;
					}

					break;
				}

				$arrIds[] = $intId;

				// No link for the active page
				if ($objPage->id == $intNode)
				{
					$arrLinks[] = $this->addIcon($objPage->row(), '', null, '', true) . ' ' . $objPage->title;
				}
				else
				{
					$arrLinks[] = $this->addIcon($objPage->row(), '', null, '', true) . ' <a href="' . $this->addToUrl('node='.$objPage->id) . '">' . $objPage->title . '</a>';
				}

				// Do not show the mounted pages
				if (!$this->User->isAdmin && $this->User->hasAccess($objPage->id, 'pagemounts'))
				{
					break;
				}

				$intId = $objPage->pid;
			}
			while ($intId > 0);
		}

		// Check whether the node is mounted
		if (!$this->User->isAdmin && !$this->User->hasAccess($arrIds, 'pagemounts'))
		{
			$this->Session->set('tl_page_node', 0);

			$this->log('Page ID '.$intNode.' was not mounted', 'tl_page addBreadcrumb', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		// Limit tree
		$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = array($intNode);

		// Add root link
		$arrLinks[] = '<img src="system/themes/' . $this->getTheme() . '/images/pagemounts.gif" width="18" height="18" alt="" /> <a href="' . $this->addToUrl('node=0') . '">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';
		$arrLinks = array_reverse($arrLinks);

		// Insert breadcrumb menu
		$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>' . implode(' &gt; </li><li>', $arrLinks) . '</li>
</ul>';
	}


	/**
	 * Make sure that top-level pages are root pages or folders
	 * @param mixed
	 * @param DataContainer
	 * @return mixed
	 * @throws Exception
	 */
	public function checkRootType($varValue, DataContainer $dc)
	{
		if ($varValue != 'root' && $varValue != 'folder' && $dc->activeRecord->pid == 0)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['topLevelRoot']);
		}

		return $varValue;
	}


	/**
	 * Show a warning if there is no language fallback page
	 */
	public function showFallbackWarning()
	{
		if ($this->Input->get('act') != '')
		{
			return;
		}

		$this->import('Messages');
		$this->addRawMessage($this->Messages->languageFallback());

		$objCount = $this->Database->execute("SELECT COUNT(*) AS count FROM tl_page WHERE pid=0 AND type!='root' AND type!='folder'");

		if ($objCount->count > 0)
		{
			$this->addRawMessage('<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['topLevelRegular'] . '</p>');
		}
	}


	public function configureFolderPage($dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'folder')
		{
			$arrSet = array
			(
				'noSearch'		=> '1',
				'sitemap'		=> 'map_never',
				'hide'			=> '1',
				'published'		=> '1',
				'start'			=> '',
				'stop'			=> '',
			);

			$this->Database->prepare("UPDATE tl_page %s WHERE id=?")->set($arrSet)->execute($dc->id);
		}
	}
}
