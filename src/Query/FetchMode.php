<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query;

enum FetchMode: int
{

    case SUCCESSFULLY = 0;
    case FIRST_RESULT = 1;
    case ALL_RESULTS = 2;
    case LAST_INSERT_ID = 3;

}
