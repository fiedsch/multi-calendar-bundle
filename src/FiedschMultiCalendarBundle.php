<?php

declare(strict_types=1);

namespace Fiedsch\MultiCalendarBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use function dirname;

class FiedschMultiCalendarBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

}