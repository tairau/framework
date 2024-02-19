<?php

declare(strict_types=1);

namespace Spiral\Core;

use Spiral\Core\Exception\Scope\BadScopeException;

class Options
{
    /**
     * Check that object is created in the correct scope.
     * The scope is defined by the {@see \Spiral\Core\Attribute\Scope} attribute.
     * If the check is enabled and the object is created outside
     * the required scope, an exception {@see BadScopeException} will be thrown.
     *
     * Will be set to true by default since version 4.0
     */
    public bool $checkScope = false;
}
