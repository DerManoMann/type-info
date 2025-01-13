<?php

namespace Radebatz\TypeInfo\Tests\Fixtures;

final class DummyCollection implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        return [];
    }
}
