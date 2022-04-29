<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Page
{
    private Connection $db;

    private RequestStack $requestStack;

    private SessionBagInterface $session;

    private RouterInterface $router;

    private UserInterface $user;

    public function __construct(Connection $db, RequestStack $requestStack, SessionInterface $session, RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->db = $db;
        $this->requestStack = $requestStack;
        $this->session = $session->getBag('contao_backend');
        $this->router = $router;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Override the default breadcrumb menu, we want to show folder pages before root pages.
     */
    public function addBreadcrumb(): void
    {
        $this->updateBreadcrumbNode();

        $nodeId = (int) $this->session->get('tl_page_node');

        if ($nodeId < 1) {
            return;
        }

        $trail = $this->getBreadcrumbTrail($nodeId);

        // Generate breadcrumb trail
        if (0 === \count($trail)) {
            $this->session->set('tl_page_node', 0);

            return;
        }

        $this->checkTrailAccess($nodeId, $trail);
        $this->buildBreadcrumb($nodeId, $trail);
    }

    /**
     * Show a warning if there is no language fallback page.
     */
    public function showFallbackWarning(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->has('act')) {
            return;
        }

        $messages = System::importStatic('Messages');
        Message::addRaw($messages->languageFallback());

        if ($this->hasInvalidTopLevels()) {
            Message::addRaw('<p class="tl_error">'.$GLOBALS['TL_LANG']['ERR']['topLevelRegular'].'</p>');
        }
    }

    public function hasInvalidTopLevels(): bool
    {
        return $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM tl_page WHERE pid=0 AND type!='root' AND type!='folder'"
        ) > 0;
    }

    /**
     * Sets fixed configuration for a folder page.
     */
    public function configureFolderPage(DataContainer $dc): void
    {
        if (null === $dc->activeRecord || 'folder' !== $dc->activeRecord->type) {
            return;
        }

        $this->db->update(
            'tl_page',
            [
                'alias' => '',
                'noSearch' => '1',
                'sitemap' => 'map_never',
                'start' => '',
                'stop' => '',
                'robots' => 'noindex,nofollow',
            ],
            [
                'id' => $dc->id,
            ]
        );
    }

    /**
     * Make sure that top-level pages are root pages or folders.
     *
     *@throws \Exception
     */
    public function onSaveType(string $value, DataContainer $dc): string
    {
        if ('root' !== $value && 'folder' !== $value && 0 === (int) $dc->activeRecord->pid) {
            throw new \Exception($GLOBALS['TL_LANG']['ERR']['topLevelRoot']);
        }

        return $value;
    }

    /**
     * Clean up all folder page aliases (as they might contain something).
     */
    public function emptyFolderAliases(string $value): string
    {
        $this->db->update('tl_page', ['alias' => ''], ['type' => 'folder']);

        return $value;
    }

    /**
     * Adjust the alias of the page.
     *
     *@throws \Exception
     */
    public function adjustAlias(string $value, DataContainer $dc): string
    {
        // Nothing to adjust if no folderUrls
        if (!Config::get('folderUrl')) {
            return $value;
        }

        // If current page is of type folder, update children
        if ($dc->activeRecord && 'folder' === $dc->activeRecord->type) {
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
            $value .= '-'.$dc->id;

            // Validate the alias once again and throw an error if it exists
            $value = $tl_page->generateAlias($value, $dc);
        }

        return $value;
    }

    /**
     * Returns all allowed page types as array.
     */
    public function getPageTypes(DataContainer $dc): array
    {
        $options = [];
        $rootAllowed = true;

        if ($dc->activeRecord->pid > 0) {
            $rootAllowed = false;
            $parentType = $this->db->fetchOne('SELECT type FROM tl_page WHERE id=?', [$dc->activeRecord->pid]);

            // Allow root in second level if the parent is folder
            if ('folder' === $parentType) {
                $rootAllowed = true;
            }
        }

        foreach (array_keys($GLOBALS['TL_PTY']) as $pty) {
            // Root pages are allowed on the first level only (see #6360)
            if ('root' === $pty && !$rootAllowed) {
                continue;
            }

            // Allow the currently selected option and anything the user has access to
            if ($pty === $dc->value || $this->user->hasAccess($pty, 'alpty')) {
                $options[] = $pty;
            }
        }

        return $options;
    }

    public function setRootType(DataContainer $dc): void
    {
        if ('create' !== Input::get('act')) {
            return;
        }

        // Insert into
        if (0 === (int) Input::get('pid')) {
            $GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';
        } else {
            $objPage = Database::getInstance()->prepare('SELECT * FROM '.$dc->table.' WHERE id=?')
                ->limit(1)
                ->execute(Input::get('pid'))
            ;

            if (0 === (int) $objPage->pid && 'folder' === $objPage->type) {
                $GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';
            }
        }
    }

    /**
     * Sets a new node if input value is given.
     */
    private function updateBreadcrumbNode(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->query->has('pn')) {
            $this->session->set('tl_page_node', (int) $request->query->get('pn'));

            $params = array_merge(
                $request->get('_route_params'),
                $request->query->all()
            );

            unset($params['pn']);

            throw new RedirectResponseException($this->router->generate($request->get('_route'), $params));
        }
    }

    /**
     * @param $nodeId
     *
     * @return array<PageModel>
     */
    private function getBreadcrumbTrail(int $nodeId)
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

            $pageId = (int) $page->pid;
        } while ($pageId > 0);

        return $pages;
    }

    /**
     * @param array<PageModel> $trail
     */
    private function checkTrailAccess(int $nodeId, array $trail): void
    {
        $trailIds = array_map(
            static fn (PageModel $page) => $page->id,
            $trail
        );

        // Check whether the node is mounted
        if (!$this->user->isAdmin && !$this->user->hasAccess($trailIds, 'pagemounts')) {
            $this->session->set('tl_page_node', 0);

            System::log('Page ID '.$nodeId.' was not mounted', 'tl_page addBreadcrumb', TL_ERROR);

            throw new RedirectResponseException($this->router->generate('contao_backend', ['act' => 'error']));
        }
    }

    /**
     * @param array<PageModel> $trail
     */
    private function buildBreadcrumb(int $nodeId, array $trail): void
    {
        foreach ($trail as $page) {
            // No link for the active page
            if ($page->id === $nodeId) {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true).' '.$page->title;
            } else {
                $links[] = Backend::addPageIcon($page->row(), '', null, '', true).' <a href="'.Backend::addToUrl('pn='.$page->id).'">'.$page->title.'</a>';
            }
        }

        // Limit tree and disable root trails
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = [$nodeId];
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['showRootTrails'] = false;

        // Add root link
        $links[] = Image::getHtml('pagemounts.svg').' <a href="'.Backend::addToUrl('pn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $links = array_reverse($links);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] = $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] ?? '';
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>'.implode(' &gt; </li><li>', $links).'</li>
</ul>';
    }

    /**
     * Update the children pages.
     */
    private function updateChildren(array $ids): void
    {
        if (\count($ids) < 1) {
            return;
        }

        foreach ($ids as $id) {
            $alias = $this->db->fetchOne('SELECT alias FROM tl_page WHERE id=?', [$id]);
            $alias = $this->cleanAlias($alias);

            $this->db->update('tl_page', ['alias' => $alias], ['id' => $id]);
        }
    }

    /**
     * Clean the alias.
     */
    private function cleanAlias(string $alias): string
    {
        return ltrim(preg_replace('@/+@', '/', $alias), '/');
    }
}
