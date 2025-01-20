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
use Radebatz\TypeInfo\Exception\InvalidArgumentException;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\SubTypeIdentifier;
use Radebatz\TypeInfo\Tests\Fixtures\AbstractDummy;
use Radebatz\TypeInfo\Tests\Fixtures\Dummy;
use Radebatz\TypeInfo\Tests\Fixtures\DummyBackedEnum;
use Radebatz\TypeInfo\Tests\Fixtures\DummyCollection;
use Radebatz\TypeInfo\Tests\Fixtures\DummyEnum;
use Radebatz\TypeInfo\Tests\Fixtures\DummyWithTemplates;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;
use Radebatz\TypeInfo\TypeResolver\StringTypeResolver;

class StringTypeResolverTest extends TestCase
{
    private StringTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new StringTypeResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(Type $expectedType, string $string, ?TypeContext $typeContext = null)
    {
        $this->assertEquals($expectedType, $this->resolver->resolve($string, $typeContext));
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolveStringable(Type $expectedType, string $string, ?TypeContext $typeContext = null)
    {
        $this->assertEquals($expectedType, $this->resolver->resolve(new class($string) implements \Stringable {
            private string $value;

            public function __construct(string $value)
            {
                $this->value = $value;
            }

            public function __toString(): string
            {
                return $this->value;
            }
        }, $typeContext));
    }

    /**
     * @return iterable<array{0: Type, 1: string, 2?: TypeContext}>
     */
    public static function resolveDataProvider(): iterable
    {
        $typeContextFactory = new TypeContextFactory(new StringTypeResolver());

        // callable
        yield [Type::callable(), 'callable(string, int): mixed'];

        // array
        yield [Type::list(Type::bool()), 'bool[]'];

        // array shape
        yield [Type::array(), 'array{0: true, 1: false}'];

        // object shape
        yield [Type::object(), 'object{foo: true, bar: false}'];

        // this
        yield [Type::object(Dummy::class), '$this', $typeContextFactory->createFromClassName(Dummy::class, AbstractDummy::class)];

        // const
        yield [Type::array(), 'array[1, 2, 3]'];
        yield [Type::false(), 'false'];
        yield [Type::float(), '1.23'];
        yield [Type::int(), '1'];
        yield [Type::null(), 'null'];
        yield [Type::string(), '"string"'];
        yield [Type::true(), 'true'];

        // identifiers
        yield [Type::bool(), 'bool'];
        yield [Type::bool(), 'boolean'];
        yield [Type::true(), 'true'];
        yield [Type::false(), 'false'];
        yield [Type::int(), 'int'];
        yield [Type::int(), 'integer'];
        yield [Type::int(new Type\SubType(SubTypeIdentifier::POSITIVE_INT)), SubTypeIdentifier::POSITIVE_INT];
        yield [Type::int(new Type\SubType(SubTypeIdentifier::NEGATIVE_INT)), SubTypeIdentifier::NEGATIVE_INT];
        yield [Type::int(new Type\SubType(SubTypeIdentifier::NON_POSITIVE_INT)), SubTypeIdentifier::NON_POSITIVE_INT];
        yield [Type::int(new Type\SubType(SubTypeIdentifier::NON_NEGATIVE_INT)), SubTypeIdentifier::NON_NEGATIVE_INT];
        yield [Type::int(new Type\SubType(SubTypeIdentifier::NON_ZERO_INT)), SubTypeIdentifier::NON_ZERO_INT];
        yield [Type::float(), 'float'];
        yield [Type::float(), 'double'];
        yield [Type::string(), 'string'];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::CLASS_STRING)), SubTypeIdentifier::CLASS_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::TRAIT_STRING)), SubTypeIdentifier::TRAIT_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::INTERFACE_STRING)), SubTypeIdentifier::INTERFACE_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::CALLABLE_STRING)), SubTypeIdentifier::CALLABLE_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::NUMERIC_STRING)), SubTypeIdentifier::NUMERIC_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::LOWERCASE_STRING)), SubTypeIdentifier::LOWERCASE_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::NON_EMPTY_LOWERCASE_STRING)), SubTypeIdentifier::NON_EMPTY_LOWERCASE_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::NON_EMPTY_STRING)), SubTypeIdentifier::NON_EMPTY_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::NON_FALSY_STRING)), SubTypeIdentifier::NON_FALSY_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::TRUTHY_STRING)), SubTypeIdentifier::TRUTHY_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::LITERAL_STRING)), SubTypeIdentifier::LITERAL_STRING];
        yield [Type::string(new Type\SubType(SubTypeIdentifier::HTML_ESCAPED_STRING)), SubTypeIdentifier::HTML_ESCAPED_STRING];
        yield [Type::resource(), 'resource'];
        yield [Type::object(), 'object'];
        yield [Type::callable(), 'callable'];
        yield [Type::array(), 'array'];
        yield [Type::array(), 'non-empty-array'];
        yield [Type::list(), 'list'];
        yield [Type::list(), 'non-empty-list'];
        yield [Type::iterable(), 'iterable'];
        yield [Type::mixed(), 'mixed'];
        yield [Type::null(), 'null'];
        yield [Type::void(), 'void'];
        yield [Type::never(), 'never'];
        yield [Type::never(new Type\SubType(SubTypeIdentifier::NEVER_RETURN)), SubTypeIdentifier::NEVER_RETURN];
        yield [Type::never(new Type\SubType(SubTypeIdentifier::NEVER_RETURNS)), SubTypeIdentifier::NEVER_RETURNS];
        yield [Type::never(new Type\SubType(SubTypeIdentifier::NO_RETURN)), SubTypeIdentifier::NO_RETURN];
        yield [Type::union(Type::int(), Type::string()), 'array-key'];
        yield [Type::union(Type::int(), Type::float(), Type::string(), Type::bool()), 'scalar'];
        yield [Type::union(Type::int(), Type::float()), 'number'];
        yield [Type::union(Type::int(), Type::float(), Type::string()), 'numeric'];
        yield [Type::object(AbstractDummy::class), 'self', $typeContextFactory->createFromClassName(Dummy::class, AbstractDummy::class)];
        yield [Type::object(Dummy::class), 'static', $typeContextFactory->createFromClassName(Dummy::class, AbstractDummy::class)];
        yield [Type::object(AbstractDummy::class), 'parent', $typeContextFactory->createFromClassName(Dummy::class)];
        yield [Type::object(Dummy::class), 'Dummy', $typeContextFactory->createFromClassName(Dummy::class)];
        if (\PHP_VERSION_ID >= 80000) {
            yield [Type::enum(DummyEnum::class), 'DummyEnum', $typeContextFactory->createFromClassName(DummyEnum::class)];
            yield [Type::enum(DummyBackedEnum::class), 'DummyBackedEnum', $typeContextFactory->createFromClassName(DummyBackedEnum::class)];
            yield [Type::template('T', Type::union(Type::int(), Type::string())), 'T', $typeContextFactory->createFromClassName(DummyWithTemplates::class)];
            yield [Type::template('T', Type::union(Type::int(), Type::string())), 'T', $typeContextFactory->createFromClassName(DummyWithTemplates::class)];
            yield [Type::template('V'), 'V', $typeContextFactory->createFromReflection(new \ReflectionMethod(DummyWithTemplates::class, 'getPrice'))];
        }

        // nullable
        yield [Type::nullable(Type::int()), '?int'];

        // generic
        yield [Type::generic(Type::object(\DateTime::class), Type::string(), Type::bool()), \DateTime::class . '<string, bool>'];
        yield [Type::generic(Type::object(\DateTime::class), Type::generic(Type::object(\Stringable::class), Type::bool())), \sprintf('%s<%s<bool>>', \DateTime::class, \Stringable::class)];
        yield [Type::int(new Type\IntRangeSubType([0, 100])), 'int<0, 100>'];

        // union
        yield [Type::union(Type::int(), Type::string()), 'int|string'];
        yield [Type::mixed(), 'int|mixed'];
        yield [Type::mixed(), 'mixed|int'];

        // intersection
        yield [Type::intersection(Type::object(\DateTime::class), Type::object(\Stringable::class)), \DateTime::class . '&' . \Stringable::class];

        // DNF
        yield [Type::union(Type::int(), Type::intersection(Type::object(\DateTime::class), Type::object(\Stringable::class))), \sprintf('int|(%s&%s)', \DateTime::class, \Stringable::class)];

        // collection objects
        yield [Type::collection(Type::object(\Traversable::class)), \Traversable::class];
        yield [Type::collection(Type::object(\Traversable::class), Type::string()), \Traversable::class . '<string>'];
        yield [Type::collection(Type::object(\Traversable::class), Type::bool(), Type::string()), \Traversable::class . '<string, bool>'];
        yield [Type::collection(Type::object(\Iterator::class)), \Iterator::class];
        yield [Type::collection(Type::object(\Iterator::class), Type::string()), \Iterator::class . '<string>'];
        yield [Type::collection(Type::object(\Iterator::class), Type::bool(), Type::string()), \Iterator::class . '<string, bool>'];
        yield [Type::collection(Type::object(\IteratorAggregate::class)), \IteratorAggregate::class];
        yield [Type::collection(Type::object(\IteratorAggregate::class), Type::string()), \IteratorAggregate::class . '<string>'];
        yield [Type::collection(Type::object(\IteratorAggregate::class), Type::bool(), Type::string()), \IteratorAggregate::class . '<string, bool>'];
        yield [Type::collection(Type::object(DummyCollection::class), Type::bool(), Type::string()), DummyCollection::class . '<string, bool>'];
    }

    public function testCannotResolveNonStringType()
    {
        $this->expectException(UnsupportedException::class);
        $this->resolver->resolve(123);
    }

    public function testCannotResolveThisWithoutTypeContext()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->resolve('$this');
    }

    public function testCannotResolveSelfWithoutTypeContext()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->resolve('self');
    }

    public function testCannotResolveStaticWithoutTypeContext()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->resolve('static');
    }

    public function testCannotResolveParentWithoutTypeContext()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->resolve('parent');
    }

    public function testCannotUnknownIdentifier()
    {
        $this->expectException(UnsupportedException::class);
        $this->resolver->resolve('unknown');
    }
}
