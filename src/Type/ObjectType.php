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
use Radebatz\TypeInfo\TypeIdentifier;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @template T of class-string
 */
class ObjectType extends Type
{
    private string $className;

    /**
     * @param T $className
     */
    public function __construct(
        string $className
    ) {
        $this->className = $className;
    }

    public function getTypeIdentifier(): string
    {
        return TypeIdentifier::OBJECT;
    }

    /**
     * @return T
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param TypeIdentifier|string $identifiers
     */
    public function isIdentifiedBy(...$identifiers): bool
    {
        foreach ($identifiers as $identifier) {
            if (TypeIdentifier::OBJECT === $identifier) {
                return true;
            }

            if (is_a($this->className, $identifier, true)) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return $this->className;
    }
}
