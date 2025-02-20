<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Tests;

use PHPUnit\Framework\TestCase;
use Radebatz\TypeInfo\Tests\Fixtures\DummyBackedEnum;
use Radebatz\TypeInfo\Tests\Fixtures\DummyEnum;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\Type\BackedEnumType;
use Radebatz\TypeInfo\Type\BuiltinType;
use Radebatz\TypeInfo\Type\CollectionType;
use Radebatz\TypeInfo\Type\EnumType;
use Radebatz\TypeInfo\Type\GenericType;
use Radebatz\TypeInfo\Type\IntersectionType;
use Radebatz\TypeInfo\Type\NullableType;
use Radebatz\TypeInfo\Type\ObjectType;
use Radebatz\TypeInfo\Type\TemplateType;
use Radebatz\TypeInfo\Type\UnionType;
use Radebatz\TypeInfo\TypeIdentifier;

class TypeFactoryTest extends TestCase
{
    public function testCreateBuiltin()
    {
        $this->assertEquals(new BuiltinType(TypeIdentifier::INT), Type::builtin(TypeIdentifier::INT));
        $this->assertEquals(new BuiltinType(TypeIdentifier::INT), Type::builtin('int'));
        $this->assertEquals(new BuiltinType(TypeIdentifier::INT), Type::int());
        $this->assertEquals(new BuiltinType(TypeIdentifier::FLOAT), Type::float());
        $this->assertEquals(new BuiltinType(TypeIdentifier::STRING), Type::string());
        $this->assertEquals(new BuiltinType(TypeIdentifier::BOOL), Type::bool());
        $this->assertEquals(new BuiltinType(TypeIdentifier::RESOURCE), Type::resource());
        $this->assertEquals(new BuiltinType(TypeIdentifier::FALSE), Type::false());
        $this->assertEquals(new BuiltinType(TypeIdentifier::TRUE), Type::true());
        $this->assertEquals(new BuiltinType(TypeIdentifier::CALLABLE), Type::callable());
        $this->assertEquals(new BuiltinType(TypeIdentifier::NULL), Type::null());
        $this->assertEquals(new BuiltinType(TypeIdentifier::MIXED), Type::mixed());
        $this->assertEquals(new BuiltinType(TypeIdentifier::VOID), Type::void());
        $this->assertEquals(new BuiltinType(TypeIdentifier::NEVER), Type::never());
    }

    public function testCreateArray()
    {
        $this->assertEquals(new CollectionType(new BuiltinType(TypeIdentifier::ARRAY)), Type::array());

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING)),
                new BuiltinType(TypeIdentifier::BOOL),
            )),
            Type::array(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::STRING),
                new BuiltinType(TypeIdentifier::BOOL),
            )),
            Type::array(Type::bool(), Type::string()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::INT),
                new BuiltinType(TypeIdentifier::BOOL),
            ), true),
            Type::array(Type::bool(), Type::int(), true),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::INT),
                new BuiltinType(TypeIdentifier::MIXED),
            ), true),
            Type::list(),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::INT),
                new BuiltinType(TypeIdentifier::BOOL),
            ), true),
            Type::list(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::STRING),
                new BuiltinType(TypeIdentifier::MIXED),
            )),
            Type::dict(),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                new BuiltinType(TypeIdentifier::STRING),
                new BuiltinType(TypeIdentifier::BOOL),
            )),
            Type::dict(Type::bool()),
        );
    }

    public function testCreateIterable()
    {
        $this->assertEquals(new CollectionType(new BuiltinType(TypeIdentifier::ITERABLE)), Type::iterable());

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ITERABLE),
                new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING)),
                new BuiltinType(TypeIdentifier::BOOL),
            )),
            Type::iterable(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ITERABLE),
                new BuiltinType(TypeIdentifier::STRING),
                new BuiltinType(TypeIdentifier::BOOL),
            )),
            Type::iterable(Type::bool(), Type::string()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(TypeIdentifier::ITERABLE),
                new BuiltinType(TypeIdentifier::INT),
                new BuiltinType(TypeIdentifier::BOOL),
            ), true),
            Type::iterable(Type::bool(), Type::int(), true),
        );
    }

    public function testCreateObject()
    {
        $this->assertEquals(new BuiltinType(TypeIdentifier::OBJECT), Type::object());
        $this->assertEquals(new ObjectType(self::class), Type::object(self::class));
    }

    /**
     * @requires PHP 8.0
     */
    public function testCreateEnum()
    {
        $this->assertEquals(new EnumType(DummyEnum::class), Type::enum(DummyEnum::class));
        $this->assertEquals(new BackedEnumType(DummyBackedEnum::class, new BuiltinType(TypeIdentifier::STRING)), Type::enum(DummyBackedEnum::class));
        $this->assertEquals(
            new BackedEnumType(DummyBackedEnum::class, new BuiltinType(TypeIdentifier::INT)),
            Type::enum(DummyBackedEnum::class, new BuiltinType(TypeIdentifier::INT)),
        );
    }

    public function testCreateGeneric()
    {
        $this->assertEquals(
            new GenericType(new ObjectType(self::class), new BuiltinType(TypeIdentifier::INT)),
            Type::generic(Type::object(self::class), Type::int()),
        );
    }

    public function testCreateTemplate()
    {
        $this->assertEquals(new TemplateType('T', new BuiltinType(TypeIdentifier::INT)), Type::template('T', Type::int()));
        $this->assertEquals(new TemplateType('T', Type::mixed()), Type::template('T'));
    }

    public function testCreateUnion()
    {
        $this->assertEquals(new UnionType(new BuiltinType(TypeIdentifier::INT), new ObjectType(self::class)), Type::union(Type::int(), Type::object(self::class)));
        $this->assertEquals(new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING)), Type::union(Type::int(), Type::string(), Type::int()));
        $this->assertEquals(new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING)), Type::union(Type::int(), Type::union(Type::int(), Type::string())));
    }

    public function testCreateIntersection()
    {
        $this->assertEquals(new IntersectionType(new ObjectType(\DateTime::class), new ObjectType(self::class)), Type::intersection(Type::object(\DateTime::class), Type::object(self::class)));
        $this->assertEquals(new IntersectionType(new ObjectType(\DateTime::class), new ObjectType(self::class)), Type::intersection(Type::object(\DateTime::class), Type::object(self::class), Type::object(self::class)));
        $this->assertEquals(new IntersectionType(new ObjectType(\DateTime::class), new ObjectType(self::class)), Type::intersection(Type::object(\DateTime::class), Type::intersection(Type::object(\DateTime::class), Type::object(self::class))));
    }

    public function testCreateNullable()
    {
        $this->assertEquals(new NullableType(new BuiltinType(TypeIdentifier::INT)), Type::nullable(Type::int()));
        $this->assertEquals(new NullableType(new BuiltinType(TypeIdentifier::INT)), Type::nullable(Type::nullable(Type::int())));
        $this->assertEquals(new BuiltinType(TypeIdentifier::MIXED), Type::nullable(Type::mixed()));

        $this->assertEquals(
            new NullableType(new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING))),
            Type::nullable(Type::union(Type::int(), Type::string())),
        );
        $this->assertEquals(
            new NullableType(new UnionType(new BuiltinType(TypeIdentifier::INT), new BuiltinType(TypeIdentifier::STRING))),
            Type::nullable(Type::union(Type::int(), Type::string(), Type::null())),
        );
    }
}
