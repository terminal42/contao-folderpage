<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\FolderpageBundle\Terminal42FolderpageBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            (new BundleConfig(Terminal42FolderpageBundle::class))
                ->setReplace(['folderpage'])
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
