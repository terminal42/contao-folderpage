<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Terminal42\FolderpageBundle\EventListener\FilterPageTypeListener;
use Terminal42\FolderpageBundle\Voter\PageTypeAccessVoter;

class ConfigureVoterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.security.data_container.page_type_access_voter')) {
            $container->removeDefinition(PageTypeAccessVoter::class);
        } else {
            $container->removeDefinition(FilterPageTypeListener::class);
        }
    }
}
