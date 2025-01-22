<?php

namespace Radebatz\TypeInfo\Type;

class SubType
{
    protected $subTypeIdentifier;

    public function __construct(string $subTypeIdentifier)
    {
        $this->subTypeIdentifier = $subTypeIdentifier;
    }

    public function getSubTypeIdentifier(): string
    {
        return $this->subTypeIdentifier;
    }
}
