<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Controller;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

#[AsPage(contentComposition: false)]
class FolderPageController
{
    public function __invoke(PageModel $pageModel): Response
    {
        throw new PageNotFoundException();
    }
}
