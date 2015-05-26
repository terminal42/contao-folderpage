<?php

/**
 * folderpage extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2012-2015, terminal42 gmbh
 * @license    LGPL-3.0+
 * @link       http://github.com/terminal42/contao-folderpage
 */

namespace Terminal42\FolderpageBundle;

use Contao\Backend;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DcaManager
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
        $this->db           = $db;
        $this->requestStack = $requestStack;
        $this->session      = $session->getBag('contao_backend');
        $this->router       = $router;
        $this->user         = $tokenStorage->getToken()->getUser();
    }

    /**
     * Override the default breadcrumb menu, we want to show folder pages before root pages
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
        if (empty($trail)) {
            $this->session->set('tl_page_node', 0);
            return;
        }

        $this->checkTrailAccess($nodeId, $trail);
        $this->buildBreadcrumb($nodeId, $trail);
    }

    /**
     * Make sure that top-level pages are root pages or folders
     *
     * @param string $type
     * @param object $activeRecord
     *
     * @return string
     *
     * @throws \Exception
     */
    public function checkRootType($type, $activeRecord)
    {
        if ($type != 'root' && $type != 'folder' && $activeRecord->pid == 0) {
            throw new \Exception($GLOBALS['TL_LANG']['ERR']['topLevelRoot']);
        }

        return $type;
    }

    /**
     * Show a warning if there is no language fallback page
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
            \Message::addRaw('<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['topLevelRegular'] . '</p>');
        }
    }

    public function hasInvalidTopLevels()
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS count FROM tl_page WHERE pid=0 AND type!='root' AND type!='folder'"
        );

        return ($result->fetchColumn() > 0);
    }

    /**
     * Sets fixed configuration for a folder page.
     *
     * @param int $id The tl_page record ID
     */
    public function configureFolderPage($id)
    {
        $this->db->update(
            'tl_page',
            [
                'noSearch'  => '1',
                'sitemap'   => 'map_never',
                'hide'      => '1',
                'published' => '1',
                'start'     => '',
                'stop'      => '',
            ],
            [
                'id' => $id
            ]
        );
    }

    /**
     * Sets a new node if input value is given
     */
    private function updateBreadcrumbNode()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->has('node')) {
            $this->session->set('tl_page_node', (int) $request->query->get('node'));

            $params = array_merge(
                $request->get('_route_params'),
                $request->query->all()
            );

            unset($params['node']);

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
        $pages  = [];
        $pageId = $nodeId;

        do {
            $page = PageModel::findByPk($pageId);

            if (null === $page) {
                // Currently selected page does not exits
                if ($pageId == $nodeId) {
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

            \System::log('Page ID ' . $nodeId . ' was not mounted', 'tl_page addBreadcrumb', TL_ERROR);

            throw new RedirectResponseException($this->router->generate('contao_backend', ['act'=>'error']));
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
            if ($page->id == $nodeId) {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true) . ' ' . $page->title;
            } else {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true) . ' <a href="' . Backend::addToUrl('node=' . $page->id) . '">' . $page->title . '</a>';
            }
        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = array($nodeId);

        // Add root link
        $links[] = '<img src="system/themes/' . Backend::getTheme() . '/images/pagemounts.gif" width="18" height="18" alt="" /> <a href="' . Backend::addToUrl('node=0') . '">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';
        $links   = array_reverse($links);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>' . implode(' &gt; </li><li>', $links) . '</li>
</ul>';
    }
}
