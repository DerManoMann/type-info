<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo;

/**
 * Identifier of a PHP native type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class TypeIdentifier
{
    public const ARRAY = 'array';
    public const BOOL = 'bool';
    public const CALLABLE = 'callable';
    public const FALSE = 'false';
    public const FLOAT = 'float';
    public const INT = 'int';
    public const ITERABLE = 'iterable';
    public const MIXED = 'mixed';
    public const NULL = 'null';
    public const OBJECT = 'object';
    public const RESOURCE = 'resource';
    public const STRING = 'string';
    public const TRUE = 'true';
    public const NEVER = 'never';
    public const VOID = 'void';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }

    public static function isStandalone(string $identifier): bool
    {
        return \in_array($identifier, [self::MIXED, self::NEVER, self::VOID], true);
    }

    public static function isScalar(string $identifier): bool
    {
        return \in_array($identifier, [self::STRING, self::FLOAT, self::INT, self::BOOL, self::FALSE, self::TRUE], true);
    }

    public static function isBool(string $identifier): bool
    {
        return \in_array($identifier, [self::BOOL, self::FALSE, self::TRUE], true);
    }
}
