<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Tests\Type;

use PHPUnit\Framework\TestCase;
use Radebatz\TypeInfo\Exception\InvalidArgumentException;
use Radebatz\TypeInfo\Tests\Fixtures\DummyBackedEnum;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\Type\BackedEnumType;

class BackedEnumTypeTest extends TestCase
{
    public function testCannotCreateInvalidBackingBuiltinType()
    {
        $this->expectException(InvalidArgumentException::class);
        new BackedEnumType(DummyBackedEnum::class, Type::bool());
    }

    public function testToString()
    {
        $this->assertSame(DummyBackedEnum::class, (string) new BackedEnumType(DummyBackedEnum::class, Type::int()));
    }
}
