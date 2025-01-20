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
 * @template T string
 */
final class BuiltinType extends Type
{
    private string $typeIdentifier;
    private ?SubType $subtype;

    /**
     * @param T $typeIdentifier
     */
    public function __construct(
        string $typeIdentifier,
        ?SubType $subtype = null
    ) {
        $this->typeIdentifier = $typeIdentifier;
        $this->subtype = $subtype;
    }

    /**
     * @return T
     */
    public function getTypeIdentifier(): string
    {
        return $this->typeIdentifier;
    }

    /**
     * @param string $identifiers
     */
    public function isIdentifiedBy(...$identifiers): bool
    {
        foreach ($identifiers as $identifier) {
            if ($identifier === $this->typeIdentifier) {
                return true;
            }
        }

        return false;
    }

    public function isNullable(): bool
    {
        return \in_array($this->typeIdentifier, [TypeIdentifier::NULL, TypeIdentifier::MIXED]);
    }

    public function __toString(): string
    {
        return $this->typeIdentifier;
    }
}
