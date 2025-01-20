<?php

namespace Radebatz\TypeInfo\Type;

class SubType
{
    protected $subtype;

    public function __construct(string $subtype)
    {
        $this->subtype = $subtype;
    }
}
