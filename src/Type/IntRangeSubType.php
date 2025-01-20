<?php

namespace Radebatz\TypeInfo\Type;

use Radebatz\TypeInfo\SubTypeIdentifier;

class IntRangeSubType extends SubType
{
    protected array $range;

    public function __construct(array $range)
    {
        parent::__construct(SubTypeIdentifier::RANGE_INT);
        $this->range = $range;
    }

    public function getRange(): array
    {
        return $this->range;
    }
}
