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
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\Type\CollectionType;
use Radebatz\TypeInfo\Type\GenericType;
use Radebatz\TypeInfo\TypeIdentifier;

class CollectionTypeTest extends TestCase
{
    public function testCannotCreateInvalidBuiltinType()
    {
        $this->expectException(InvalidArgumentException::class);
        new CollectionType(Type::int());
    }

    public function testCanOnlyConstructListWithIntKeyType()
    {
        new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::int(), Type::bool()), true);
        $this->addToAssertionCount(1);

        $this->expectException(InvalidArgumentException::class);
        new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::bool()), true);
    }

    public function testIsList()
    {
        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::bool()));
        $this->assertFalse($type->isList());

        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::bool()), true);
        $this->assertTrue($type->isList());
    }

    public function testGetCollectionKeyType()
    {
        $type = new CollectionType(Type::builtin(TypeIdentifier::ARRAY));
        $this->assertEquals(Type::union(Type::int(), Type::string()), $type->getCollectionKeyType());

        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::bool()));
        $this->assertEquals(Type::int(), $type->getCollectionKeyType());

        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::bool()));
        $this->assertEquals(Type::string(), $type->getCollectionKeyType());
    }

    public function testGetCollectionValueType()
    {
        $type = new CollectionType(Type::builtin(TypeIdentifier::ARRAY));
        $this->assertEquals(Type::mixed(), $type->getCollectionValueType());

        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::bool()));
        $this->assertEquals(Type::bool(), $type->getCollectionValueType());

        $type = new CollectionType(new GenericType(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::bool()));
        $this->assertEquals(Type::bool(), $type->getCollectionValueType());
    }

    public function testWrappedTypeIsSatisfiedBy()
    {
        $type = new CollectionType(Type::builtin(TypeIdentifier::ARRAY));
        $this->assertTrue($type->wrappedTypeIsSatisfiedBy(static fn (Type $t): bool => 'array' === (string) $t));

        $type = new CollectionType(Type::builtin(TypeIdentifier::ITERABLE));
        $this->assertFalse($type->wrappedTypeIsSatisfiedBy(static fn (Type $t): bool => 'array' === (string) $t));
    }

    public function testToString()
    {
        $type = new CollectionType(Type::builtin(TypeIdentifier::ITERABLE));
        $this->assertEquals('iterable', (string) $type);

        $type = new CollectionType(Type::generic(Type::builtin(TypeIdentifier::ARRAY), Type::bool()));
        $this->assertEquals('array<bool>', (string) $type);

        $type = new CollectionType(new GenericType(Type::builtin(TypeIdentifier::ARRAY), Type::string(), Type::bool()));
        $this->assertEquals('array<string,bool>', (string) $type);
    }
}
