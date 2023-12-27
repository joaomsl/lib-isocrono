<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query\Bind;

use InvalidArgumentException;
use pmmp\thread\ThreadSafe;

class Bind extends ThreadSafe
{

    public function __construct(
        private string $placeholder,
        private mixed $value,
        private BindType $type = BindType::STRING
    ) {
        if(empty($placeholder)) {
            throw new InvalidArgumentException('The placeholder cannot be empty.');
        }
    }

    public function getPlaceholder(): string 
    {
        return $this->placeholder;
    }

    public function getValue(): mixed 
    {
        return $this->value;
    }

    public function getType(): BindType
    {
        return $this->type;
    }
    
}
