<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Tests\TypeContext;

use PHPUnit\Framework\TestCase;
use Radebatz\TypeInfo\Exception\LogicException;
use Radebatz\TypeInfo\Tests\Fixtures\AbstractDummy;
use Radebatz\TypeInfo\Tests\Fixtures\Dummy;
use Radebatz\TypeInfo\Tests\Fixtures\DummyExtendingStdClass;
use Radebatz\TypeInfo\Tests\Fixtures\DummyWithUses;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;

class TypeContextTest extends TestCase
{
    public function testNormalize()
    {
        $typeContext = (new TypeContextFactory())->createFromClassName(DummyWithUses::class);

        $this->assertSame(DummyWithUses::class, $typeContext->normalize('DummyWithUses'));
        $this->assertSame(Type::class, $typeContext->normalize('Type'));
        $this->assertSame('\\' . \DateTimeImmutable::class, $typeContext->normalize('DateTime'));
        $this->assertSame('Radebatz\\TypeInfo\\Tests\\Fixtures\\unknown', $typeContext->normalize('unknown'));
        $this->assertSame('unknown', $typeContext->normalize('\\unknown'));

        $typeContextWithoutNamespace = new TypeContext('Foo', 'Bar');
        $this->assertSame('unknown', $typeContextWithoutNamespace->normalize('unknown'));
    }

    public function testGetDeclaringClass()
    {
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->getDeclaringClass());
        $this->assertSame(AbstractDummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class, AbstractDummy::class)->getDeclaringClass());
    }

    public function testGetCalledClass()
    {
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->getCalledClass());
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class, AbstractDummy::class)->getCalledClass());
    }

    public function testGetParentClass()
    {
        $this->assertSame(AbstractDummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->getParentClass());
        $this->assertSame(\stdClass::class, (new TypeContextFactory())->createFromClassName(DummyExtendingStdClass::class)->getParentClass());
    }

    public function testCannotGetParentClassWhenDoNotInherit()
    {
        $this->expectException(LogicException::class);
        (new TypeContextFactory())->createFromClassName(AbstractDummy::class)->getParentClass();
    }
}
