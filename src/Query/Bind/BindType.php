<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query\Bind;

enum BindType: int
{

    case STRING = 0;
    case INT = 1;
    case BOOLEAN = 4;

}
