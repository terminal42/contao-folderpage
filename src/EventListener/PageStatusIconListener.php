<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Database\Result;
use Contao\PageModel;
use Symfony\Component\Asset\Packages;

#[AsHook('getPageStatusIcon')]
class PageStatusIconListener
{
    public function __construct(private readonly Packages $packages)
    {
    }

    /**
     * Return our custom image for the folder page type.
     *
     * @param PageModel|Result|\stdClass $page
     */
    public function __invoke($page, string $image): string
    {
        if ('folder' === $page->type) {
            $sub = '';

            // Get the sub from the image name generate by Contao
            if (preg_match('/^regular_(\d+).svg$/', $image, $matches)) {
                $sub = $matches[1];
            }

            $file = \sprintf('folder%s.svg', $sub > 0 ? '_'.$sub : '');

            return $this->packages->getUrl($file, 'terminal42_folderpage');
        }

        return $image;
    }
}
