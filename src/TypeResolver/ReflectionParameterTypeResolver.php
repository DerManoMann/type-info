<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\TypeResolver;

use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;

/**
 * Resolves type for a given parameter reflection.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ReflectionParameterTypeResolver implements TypeResolverInterface
{
    private ReflectionTypeResolver $reflectionTypeResolver;
    private TypeContextFactory $typeContextFactory;

    public function __construct(
        ReflectionTypeResolver $reflectionTypeResolver,
        TypeContextFactory $typeContextFactory
    ) {
        $this->reflectionTypeResolver = $reflectionTypeResolver;
        $this->typeContextFactory = $typeContextFactory;
    }

    public function resolve(mixed $subject, ?TypeContext $typeContext = null): Type
    {
        if (!$subject instanceof \ReflectionParameter) {
            throw new UnsupportedException(\sprintf('Expected subject to be a "ReflectionParameter", "%s" given.', get_debug_type($subject)), $subject);
        }

        $typeContext ??= $this->typeContextFactory->createFromReflection($subject);

        try {
            return $this->reflectionTypeResolver->resolve($subject->getType(), $typeContext);
        } catch (UnsupportedException $e) {
            $path = null !== $typeContext
                ? \sprintf('%s::%s($%s)', $typeContext->calledClassName, $subject->getDeclaringFunction()->getName(), $subject->getName())
                : \sprintf('%s($%s)', $subject->getDeclaringFunction()->getName(), $subject->getName());

            throw new UnsupportedException(\sprintf('Cannot resolve type for "%s".', $path), $subject, 0, $e);
        }
    }
}
