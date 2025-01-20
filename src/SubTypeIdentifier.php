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
 * Builtin subtypes.
 *
 * @author Martin Rademacher <mano@radebatz.net>
 */
class SubTypeIdentifier
{
    public const POSITIVE_INT = 'positive-int';
    public const NEGATIVE_INT = 'negative-int';
    public const NON_POSITIVE_INT = 'non-positive-int';
    public const NON_NEGATIVE_INT = 'non-negative-int';
    public const NON_ZERO_INT = 'non-zero-int';
    public const RANGE_INT = 'range-int';
    public const CLASS_STRING = 'class-string';
    public const TRAIT_STRING = 'trait-string';
    public const INTERFACE_STRING = 'interface-string';
    public const CALLABLE_STRING = 'callable-string';
    public const NUMERIC_STRING = 'numeric-string';
    public const LOWERCASE_STRING = 'lowercase-string';
    public const NON_EMPTY_LOWERCASE_STRING = 'non-empty-lowercase-string';
    public const NON_EMPTY_STRING = 'non-empty-string';
    public const NON_FALSY_STRING = 'non-falsy-string';
    public const TRUTHY_STRING = 'truthy-string';
    public const LITERAL_STRING = 'literal-string';
    public const HTML_ESCAPED_STRING = 'html-escaped-string';
    public const NEVER_RETURN = 'never-return';
    public const NEVER_RETURNS = 'never-returns';
    public const NO_RETURN = 'no-return';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }
}
