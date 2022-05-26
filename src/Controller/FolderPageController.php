<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Controller;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\ServiceAnnotation\Page;

/**
 * @Page(path=false, contentComposition=false)
 */
class FolderPageController
{
    public function __invoke(): void
    {
        throw new PageNotFoundException();
    }
}
