<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Controller;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(contentComposition=false)
 */
class FolderPageController
{
    public function __invoke(PageModel $pageModel): Response
    {
        throw new PageNotFoundException();
    }
}
