<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Tests\TypeResolver;

use PHPUnit\Framework\TestCase;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Tests\Fixtures\ReflectionExtractableDummy;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;
use Radebatz\TypeInfo\TypeResolver\ReflectionPropertyTypeResolver;
use Radebatz\TypeInfo\TypeResolver\ReflectionTypeResolver;

class ReflectionPropertyTypeResolverTest extends TestCase
{
    private ReflectionPropertyTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReflectionPropertyTypeResolver(new ReflectionTypeResolver(), new TypeContextFactory());
    }

    public function testCannotResolveNonReflectionProperty()
    {
        $this->expectException(UnsupportedException::class);
        $this->resolver->resolve(123);
    }

    public function testCannotResolveReflectionPropertyWithoutType()
    {
        $this->expectException(UnsupportedException::class);

        $reflectionClass = new \ReflectionClass(ReflectionExtractableDummy::class);
        $reflectionProperty = $reflectionClass->getProperty('nothing');

        $this->resolver->resolve($reflectionProperty);
    }

    public function testResolve()
    {
        $reflectionClass = new \ReflectionClass(ReflectionExtractableDummy::class);
        $reflectionProperty = $reflectionClass->getProperty('builtin');

        $this->assertEquals(Type::int(), $this->resolver->resolve($reflectionProperty));
    }

    public function testCreateTypeContextOrUseProvided()
    {
        $reflectionClass = new \ReflectionClass(ReflectionExtractableDummy::class);
        $reflectionProperty = $reflectionClass->getProperty('self');

        $this->assertEquals(Type::object(ReflectionExtractableDummy::class), $this->resolver->resolve($reflectionProperty));

        $typeContext = (new TypeContextFactory())->createFromClassName(self::class);

        $this->assertEquals(Type::object(self::class), $this->resolver->resolve($reflectionProperty, $typeContext));
    }
}
