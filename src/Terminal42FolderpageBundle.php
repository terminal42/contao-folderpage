<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42FolderpageBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
