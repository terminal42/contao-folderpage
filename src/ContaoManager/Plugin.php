<?php


namespace Terminal42\FolderpageBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            (new BundleConfig('Terminal42\FolderpageBundle\Terminal42FolderpageBundle'))
                ->setReplace(['folderpage'])
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle']),
        ];
    }
}
