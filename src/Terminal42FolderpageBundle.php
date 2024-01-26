<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\FolderpageBundle\DependencyInjection\Compiler\ConfigureVoterPass;

class Terminal42FolderpageBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigureVoterPass());
    }
}
