<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreErrorsOnPackage('symfony/config', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/dependency-injection', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/http-foundation', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/http-kernel', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/security-core', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/service-contracts', [ErrorType::SHADOW_DEPENDENCY])
;
