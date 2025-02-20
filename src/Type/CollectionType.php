<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\Type;

use Radebatz\TypeInfo\Exception\InvalidArgumentException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeIdentifier;

/**
 * Represents a key/value collection type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @template T of BuiltinType<TypeIdentifier::ARRAY>|BuiltinType<TypeIdentifier::ITERABLE>|ObjectType|GenericType
 *
 * @implements WrappingTypeInterface<T>
 */
final class CollectionType extends Type implements WrappingTypeInterface
{
    /**
     * @var BuiltinType|ObjectType|GenericType
     */
    private $type;
    private bool $isList = false;

    /**
     * @param T $type
     */
    public function __construct(
        $type,
        bool $isList = false
    ) {
        $this->type = $type;
        $this->isList = $isList;

        if ($type instanceof BuiltinType && TypeIdentifier::ARRAY !== $type->getTypeIdentifier() && TypeIdentifier::ITERABLE !== $type->getTypeIdentifier()) {
            throw new InvalidArgumentException(\sprintf('Cannot create "%s" with "%s" type.', self::class, $type));
        }

        if ($this->isList()) {
            $keyType = $this->getCollectionKeyType();

            if (!$keyType instanceof BuiltinType || TypeIdentifier::INT !== $keyType->getTypeIdentifier()) {
                throw new InvalidArgumentException(\sprintf('"%s" is not a valid list key type.', (string) $keyType));
            }
        }
    }

    public function getWrappedType(): Type
    {
        return $this->type;
    }

    public function isList(): bool
    {
        return $this->isList;
    }

    public function getCollectionKeyType(): Type
    {
        $defaultCollectionKeyType = self::union(self::int(), self::string());

        if ($this->type instanceof GenericType) {
            switch (\count($this->type->getVariableTypes())) {
                case 2: return $this->type->getVariableTypes()[0];
                case 1: return self::int();
                default: return $defaultCollectionKeyType;
            }
        }

        return $defaultCollectionKeyType;
    }

    public function getCollectionValueType(): Type
    {
        $defaultCollectionValueType = self::mixed();

        if ($this->type instanceof GenericType) {
            switch (\count($this->type->getVariableTypes())) {
                case 2: return $this->type->getVariableTypes()[1];
                case 1: return $this->type->getVariableTypes()[0];
                default: return $defaultCollectionValueType;
            }
        }

        return $defaultCollectionValueType;
    }

    public function wrappedTypeIsSatisfiedBy(callable $specification): bool
    {
        return $this->getWrappedType()->isSatisfiedBy($specification);
    }

    public function __toString(): string
    {
        return (string) $this->type;
    }
}
