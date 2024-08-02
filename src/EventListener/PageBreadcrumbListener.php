<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Overrides the default breadcrumb menu, we want to show folder pages before root pages.
 * Duplicated from Contao\Backend::addPagesBreadcrumb() but updated for DI.
 */
#[AsHook('loadDataContainer')]
class PageBreadcrumbListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(string $table): void
    {
        if ('tl_page' !== $table) {
            return;
        }

        foreach (($GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'] ?? []) as $k => $callback) {
            if (!\is_array($callback) || 'tl_page' !== $callback[0] || 'addBreadcrumb' !== $callback[1]) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][$k] = $this->addBreadcrumb(...);

            return;
        }
    }

    private function addBreadcrumb(): void
    {
        /** @var AttributeBagInterface $objSession */
        $objSession = $this->requestStack->getSession()->getBag('contao_backend');

        // Set a new node
        if (null !== Input::get('pn')) {
            // Check the path (thanks to Arnaud Buchoux)
            if (Validator::isInsecurePath(Input::get('pn', true))) {
                throw new \RuntimeException('Insecure path '.Input::get('pn', true));
            }

            $objSession->set('tl_page_node', Input::get('pn', true));
            Controller::redirect(preg_replace('/&pn=[^&]*/', '', Environment::get('requestUri')));
        }

        $intNode = (int) $objSession->get('tl_page_node', 0);

        if ($intNode < 1) {
            return;
        }

        $arrIds = [];
        $arrLinks = [];
        $objUser = $this->tokenStorage->getToken()?->getUser();

        if (!$objUser instanceof BackendUser) {
            return;
        }

        // Generate breadcrumb trail
        $intId = $intNode;

        do {
            $page = $this->connection->fetchAssociative('SELECT * FROM tl_page WHERE id=?', [$intId]);

            if (false === $page) {
                // The currently selected page does not exist
                if ($intId === $intNode) {
                    $objSession->set('tl_page_node', 0);

                    return;
                }

                break;
            }

            $arrIds[] = $intId;

            // No link for the active page or pages in the trail
            if ((int) $page['id'] === $intNode || !$objUser->hasAccess($page['id'], 'pagemounts')) {
                $arrLinks[] = Backend::addPageIcon($page, '', null, '', true).' '.$page['title'];
            } else {
                $arrLinks[] = Backend::addPageIcon($page, '', null, '', true).' <a href="'.Backend::addToUrl('pn='.$page['id']).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$page['title'].'</a>';
            }

            $intId = (int) $page['pid'];
        } while ($intId > 0);

        // Check whether the node is mounted
        if (!$objUser->hasAccess($arrIds, 'pagemounts')) {
            $objSession->set('tl_page_node', 0);

            throw new AccessDeniedException('Page ID '.$intNode.' is not mounted.');
        }

        // Limit tree and disable root trails
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = [$intNode];
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['showRootTrails'] = false;

        // Add root link
        $arrLinks[] = Image::getHtml('pagemounts.svg').' <a href="'.Backend::addToUrl('pn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $arrLinks = array_reverse($arrLinks);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] = ($GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] ?? '').'

<nav aria-label="'.$GLOBALS['TL_LANG']['MSC']['breadcrumbMenu'].'">
  <ul id="tl_breadcrumb">
    <li>'.implode(' › </li><li>', $arrLinks).'</li>
  </ul>
</nav>';
    }
}
