<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Controller;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @Page(contentComposition=false)
 */
class FolderPageController implements DynamicRouteInterface
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(PageModel $pageModel): Response
    {
        throw new PageNotFoundException();
    }

    public function configurePageRoute(PageRoute $route): void
    {
        $pageModel = $route->getPageModel();

        if ($pageModel->hide || !$this->getForwardPage((int) $pageModel->id)) {
            throw new ResourceNotFoundException(sprintf('Page ID %s is not routable', $pageModel->id));
        }
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }

    private function getForwardPage(int $pageId): ?PageModel
    {
        $this->framework->initialize();

        return PageModel::findFirstPublishedRegularByPid($pageId);
    }
}
