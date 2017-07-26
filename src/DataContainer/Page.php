<?php

/*
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

namespace Terminal42\FolderpageBundle\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Database;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Page
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var AttributeBagInterface
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var \BackendUser
     */
    private $user;

    /**
     * Constructor.
     *
     * @param Connection            $db
     * @param RequestStack          $requestStack
     * @param SessionInterface      $session
     * @param RouterInterface       $router
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        Connection $db,
        RequestStack $requestStack,
        SessionInterface $session,
        RouterInterface $router,
        TokenStorageInterface $tokenStorage
    ) {
        $this->db = $db;
        $this->requestStack = $requestStack;
        $this->session = $session->getBag('contao_backend');
        $this->router = $router;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Override the default breadcrumb menu, we want to show folder pages before root pages.
     */
    public function addBreadcrumb()
    {
        $this->updateBreadcrumbNode();

        $nodeId = $this->session->get('tl_page_node');

        if ($nodeId < 1) {
            return;
        }

        $trail = $this->getBreadcrumbTrail($nodeId);

        // Generate breadcrumb trail
        if (0 === count($trail)) {
            $this->session->set('tl_page_node', 0);

            return;
        }

        $this->checkTrailAccess($nodeId, $trail);
        $this->buildBreadcrumb($nodeId, $trail);
    }

    /**
     * Show a warning if there is no language fallback page.
     */
    public function showFallbackWarning()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->has('act')) {
            return;
        }

        $messages = \System::importStatic('Messages');
        \Message::addRaw($messages->languageFallback());

        if ($this->hasInvalidTopLevels()) {
            \Message::addRaw('<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['topLevelRegular'].'</p>');
        }
    }

    public function hasInvalidTopLevels()
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS count FROM tl_page WHERE pid=0 AND type!='root' AND type!='folder'"
        );

        return $result->fetchColumn() > 0;
    }

    /**
     * Sets fixed configuration for a folder page.
     *
     * @param \Contao\DataContainer $dc
     */
    public function configureFolderPage($dc)
    {
        if (null === $dc->activeRecord || 'folder' !== $dc->activeRecord->type) {
            return;
        }

        $this->db->update(
            'tl_page',
            [
                'noSearch' => '1',
                'sitemap' => 'map_never',
                'hide' => '1',
                'published' => '1',
                'start' => '',
                'stop' => '',
            ],
            [
                'id' => $dc->id,
            ]
        );
    }

    /**
     * Make sure that top-level pages are root pages or folders.
     *
     * @param string                $value
     * @param \Contao\DataContainer $dc
     *
     * @throws \Exception
     *
     * @return string
     */
    public function onSaveType($value, $dc)
    {
        if ('root' !== $value && 'folder' !== $value && $dc->activeRecord->pid === 0) {
            throw new \Exception($GLOBALS['TL_LANG']['ERR']['topLevelRoot']);
        }

        return $value;
    }

    /**
     * A folder page can never be unpublished.
     *
     * @param string                $value
     * @param \Contao\DataContainer $dc
     *
     * @return string
     */
    public function onSavePublished($value, $dc)
    {
        if (null !== $dc->activeRecord && 'folder' === $dc->activeRecord->type) {
            return '1';
        }

        return $value;
    }

    /**
     * Clean up all folder page aliases (as they might contain something).
     *
     * @param string $value
     *
     * @return string
     */
    public function emptyFolderAliases($value)
    {
        $this->db->update('tl_page', ['alias' => ''], ['type' => 'folder']);

        return $value;
    }

    /**
     * Adjust the alias of the page.
     *
     * @param string                $value
     * @param \Contao\DataContainer $dc
     *
     * @throws \Exception
     *
     * @return string
     */
    public function adjustAlias($value, $dc)
    {
        // Nothing to adjust if no folderUrls
        if (!Config::get('folderUrl')) {
            return $value;
        }

        // If current page is of type folder, update children
        if ($dc->activeRecord && $dc->activeRecord->type === 'folder') {
            $childRecords = Database::getInstance()->getChildRecords([$dc->id], 'tl_page');
            $this->updateChildren($childRecords);

            return $value;
        }

        $tl_page = new \tl_page();

        // Clean the alias
        $value = $this->cleanAlias($value);

        try {
            $value = $tl_page->generateAlias($value, $dc);
        } catch (\Exception $e) {
            // The alias already exists so add ID just like the original method would
            $value = $value.'-'.$dc->id;

            // Validate the alias once again and throw an error if it exists
            $value = $tl_page->generateAlias($value, $dc);
        }

        return $value;
    }

    /**
     * Returns all allowed page types as array.
     *
     * @param \Contao\DataContainer $dc
     *
     * @return array
     */
    public function getPageTypes($dc)
    {
        $options = [];
        $rootAllowed = true;

        if ($dc->activeRecord->pid > 0) {
            $rootAllowed = false;
            $parentType = $this->db->fetchColumn('SELECT type FROM tl_page WHERE id=?', [$dc->activeRecord->pid]);

            // Allow root in second level if the parent is folder
            if ($parentType === 'folder') {
                $rootAllowed = true;
            }
        }

        foreach (array_keys($GLOBALS['TL_PTY']) as $pty) {
            // Root pages are allowed on the first level only (see #6360)
            if ($pty === 'root' && !$rootAllowed) {
                continue;
            }

            // Allow the currently selected option and anything the user has access to
            if ($pty === $dc->value || $this->user->hasAccess($pty, 'alpty')) {
                $options[] = $pty;
            }
        }

        return $options;
    }

    /**
     * Sets a new node if input value is given.
     */
    private function updateBreadcrumbNode()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->has('pn')) {
            $this->session->set('tl_page_node', (int) $request->query->get('pn'));

            $params = array_merge(
                $request->get('_route_params'),
                $request->query->all()
            );

            unset($params['pn']);

            throw new RedirectResponseException(
                $this->router->generate(
                    $request->get('_route'),
                    $params
                )
            );
        }
    }

    /**
     * @param $nodeId
     *
     * @return PageModel[]
     */
    private function getBreadcrumbTrail($nodeId)
    {
        $pages = [];
        $pageId = $nodeId;

        do {
            $page = PageModel::findByPk($pageId);

            if (null === $page) {
                // Currently selected page does not exits
                if ($pageId === $nodeId) {
                    return [];
                }

                break;
            }

            $pages[] = $page;

            // Do not show the mounted pages
            if (!$this->user->isAdmin && $this->user->hasAccess($page->id, 'pagemounts')) {
                break;
            }

            $pageId = $page->pid;
        } while ($pageId > 0);

        return $pages;
    }

    /**
     * @param int         $nodeId
     * @param PageModel[] $trail
     */
    private function checkTrailAccess($nodeId, array $trail)
    {
        $trailIds = array_map(
            function (PageModel $page) {
                return $page->id;
            },
            $trail
        );

        // Check whether the node is mounted
        if (!$this->user->isAdmin && !$this->user->hasAccess($trailIds, 'pagemounts')) {
            $this->session->set('tl_page_node', 0);

            \System::log('Page ID '.$nodeId.' was not mounted', 'tl_page addBreadcrumb', TL_ERROR);

            throw new RedirectResponseException($this->router->generate('contao_backend', ['act' => 'error']));
        }
    }

    /**
     * @param int         $nodeId
     * @param PageModel[] $trail
     */
    private function buildBreadcrumb($nodeId, array $trail)
    {
        foreach ($trail as $page) {
            // No link for the active page
            if ($page->id === $nodeId) {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true).' '.$page->title;
            } else {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true).' <a href="'.Backend::addToUrl('pn='.$page->id).'">'.$page->title.'</a>';
            }
        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = [$nodeId];

        // Add root link
        $links[] = \Image::getHtml('pagemounts.svg').' <a href="'.\Backend::addToUrl('pn=0').'" title="'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $links = array_reverse($links);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>'.implode(' &gt; </li><li>', $links).'</li>
</ul>';
    }

    /**
     * Update the children pages.
     *
     * @param array $ids
     */
    private function updateChildren(array $ids)
    {
        if (count($ids) < 1) {
            return;
        }

        foreach ($ids as $id) {
            $alias = $this->db->fetchColumn('SELECT alias FROM tl_page WHERE id=?', [$id]);
            $alias = $this->cleanAlias($alias);

            $this->db->update('tl_page', ['alias' => $alias], ['id' => $id]);
        }
    }

    /**
     * Clean the alias.
     *
     * @param string $alias
     *
     * @return string
     */
    private function cleanAlias($alias)
    {
        return ltrim(preg_replace('@/+@', '/', $alias), '/');
    }
}
