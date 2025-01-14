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

use PHPStan\PhpDocParser\Parser\PhpDocParser;
use Psr\Container\ContainerInterface;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;

/**
 * Resolves type for a given subject by delegating resolving to nested type resolvers.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class TypeResolver implements TypeResolverInterface
{
    private ContainerInterface $resolvers;

    /**
     * @param ContainerInterface $resolvers Locator of type resolvers, keyed by supported subject type
     */
    public function __construct(
        ContainerInterface $resolvers
    ) {
        $this->resolvers = $resolvers;
    }

    public function resolve($subject, ?TypeContext $typeContext = null): Type
    {
        switch (\is_object($subject)) {
            case true:
                switch (true) {
                    case is_subclass_of(get_class($subject), \ReflectionType::class): $subjectType = \ReflectionType::class;
                        break;
                    case is_subclass_of(get_class($subject), \ReflectionFunctionAbstract::class): $subjectType = \ReflectionFunctionAbstract::class;
                        break;
                    default: $subjectType = get_class($subject);
                        break;
                }
                break;
            case false: $subjectType = get_debug_type($subject);
                break;
        };

        if (!$this->resolvers->has($subjectType)) {
            if ('string' === $subjectType) {
                throw new UnsupportedException('Cannot find any resolver for "string" type. Try running "composer require phpstan/phpdoc-parser".', $subject);
            }

            throw new UnsupportedException(\sprintf('Cannot find any resolver for "%s" type.', $subjectType), $subject);
        }

        /** @param TypeResolverInterface $resolver */
        $resolver = $this->resolvers->get($subjectType);

        return $resolver->resolve($subject, $typeContext);
    }

    /**
     * @param array<string, TypeResolverInterface>|null $resolvers
     */
    public static function create(?array $resolvers = null): self
    {
        if (null === $resolvers) {
            $stringTypeResolver = class_exists(PhpDocParser::class) ? new StringTypeResolver() : null;
            $typeContextFactory = new TypeContextFactory($stringTypeResolver);
            $reflectionTypeResolver = new ReflectionTypeResolver();

            $resolvers = [
                \ReflectionType::class => $reflectionTypeResolver,
                \ReflectionParameter::class => new ReflectionParameterTypeResolver($reflectionTypeResolver, $typeContextFactory),
                \ReflectionProperty::class => new ReflectionPropertyTypeResolver($reflectionTypeResolver, $typeContextFactory),
                \ReflectionFunctionAbstract::class => new ReflectionReturnTypeResolver($reflectionTypeResolver, $typeContextFactory),
            ];

            if (null !== $stringTypeResolver) {
                $resolvers['string'] = $stringTypeResolver;
                $resolvers[\ReflectionParameter::class] = new PhpDocAwareReflectionTypeResolver($resolvers[\ReflectionParameter::class], $stringTypeResolver, $typeContextFactory);
                $resolvers[\ReflectionProperty::class] = new PhpDocAwareReflectionTypeResolver($resolvers[\ReflectionProperty::class], $stringTypeResolver, $typeContextFactory);
                $resolvers[\ReflectionFunctionAbstract::class] = new PhpDocAwareReflectionTypeResolver($resolvers[\ReflectionFunctionAbstract::class], $stringTypeResolver, $typeContextFactory);
            }
        }

        $resolversContainer = new class($resolvers) implements ContainerInterface {
            private array $resolvers;

            public function __construct(
                array $resolvers
            ) {
                $this->resolvers = $resolvers;
            }

            public function has(string $id): bool
            {
                return isset($this->resolvers[$id]);
            }

            public function get(string $id): TypeResolverInterface
            {
                return $this->resolvers[$id];
            }
        };

        return new self($resolversContainer);
    }
}
