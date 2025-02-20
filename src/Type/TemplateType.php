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

use Radebatz\TypeInfo\Type;

/**
 * Represents a template placeholder, such as "T" in "Collection<T>".
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @template T of Type
 *
 * @implements WrappingTypeInterface<T>
 */
final class TemplateType extends Type implements WrappingTypeInterface
{
    private string $name;
    private Type $bound;

    /**
     * @param T $bound
     */
    public function __construct(
        string $name,
        Type $bound
    ) {
        $this->name = $name;
        $this->bound = $bound;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return T
     */
    public function getBound(): Type
    {
        return $this->bound;
    }

    public function getWrappedType(): Type
    {
        return $this->bound;
    }

    public function wrappedTypeIsSatisfiedBy(callable $specification): bool
    {
        return $this->getWrappedType()->isSatisfiedBy($specification);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
