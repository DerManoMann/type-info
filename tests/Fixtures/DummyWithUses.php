<?php

namespace Radebatz\TypeInfo\Tests\Fixtures;

use Radebatz\TypeInfo\Type;
use \DateTimeImmutable as DateTime;

final class DummyWithUses
{
    private \DateTimeInterface $createdAt;

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getType(): Type
    {
        throw new \LogicException('Should not be called.');
    }
}
