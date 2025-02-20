<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Radebatz\TypeInfo\TypeContext;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Radebatz\TypeInfo\Exception\RuntimeException;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeResolver\StringTypeResolver;
use function count;
use function is_string;
use function sprintf;

/**
 * Creates a type resolving context.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class TypeContextFactory
{
    /**
     * @var array<class-string, \ReflectionClass>
     */
    private static array $reflectionClassCache = [];

    private ?Lexer $phpstanLexer = null;
    private ?PhpDocParser $phpstanParser = null;
    private ?StringTypeResolver $stringTypeResolver = null;

    public function __construct(
        ?StringTypeResolver $stringTypeResolver = null
    ) {
        $this->stringTypeResolver = $stringTypeResolver;
    }

    public function createFromClassName(string $calledClassName, ?string $declaringClassName = null): TypeContext
    {
        $declaringClassName ??= $calledClassName;

        $calledClassPath = explode('\\', $calledClassName);
        $declaringClassPath = explode('\\', $declaringClassName);

        $declaringClassReflection = self::$reflectionClassCache[$declaringClassName] ??= new \ReflectionClass($declaringClassName);

        $typeContext = new TypeContext(
            end($calledClassPath),
            end($declaringClassPath),
            trim($declaringClassReflection->getNamespaceName(), '\\'),
            $this->collectUses($declaringClassReflection),
        );

        return new TypeContext(
            $typeContext->calledClassName,
            $typeContext->declaringClassName,
            $typeContext->namespace,
            $typeContext->uses,
            $this->collectTemplates($declaringClassReflection, $typeContext),
        );
    }

    public function createFromReflection(\Reflector $reflection): ?TypeContext
    {
        switch (true) {
            case $reflection instanceof \ReflectionClass:
                $declaringClassReflection = $reflection;
                break;
            case $reflection instanceof \ReflectionMethod:
                $declaringClassReflection = $reflection->getDeclaringClass();
                break;
            case $reflection instanceof \ReflectionProperty:
                $declaringClassReflection = $reflection->getDeclaringClass();
                break;
            case $reflection instanceof \ReflectionParameter:
                $declaringClassReflection = $reflection->getDeclaringClass();
                break;
            case $reflection instanceof \ReflectionFunctionAbstract:
                $declaringClassReflection = $reflection->getClosureScopeClass();
                break;
            default:
                $declaringClassReflection = null;
                break;
        }

        if (null === $declaringClassReflection) {
            return null;
        }

        $typeContext = new TypeContext(
            $declaringClassReflection->getShortName(),
            $declaringClassReflection->getShortName(),
            $declaringClassReflection->getNamespaceName(),
            $this->collectUses($declaringClassReflection),
        );

        switch (true) {
            case $reflection instanceof \ReflectionFunctionAbstract:
                $templates = $this->collectTemplates($reflection, $typeContext) + $this->collectTemplates($declaringClassReflection, $typeContext);
                break;
            case $reflection instanceof \ReflectionParameter:
                $templates = $this->collectTemplates($reflection->getDeclaringFunction(), $typeContext) + $this->collectTemplates($declaringClassReflection, $typeContext);
                break;
            default:
                $templates = $this->collectTemplates($declaringClassReflection, $typeContext);
                break;
        }

        return new TypeContext(
            $typeContext->calledClassName,
            $typeContext->declaringClassName,
            $typeContext->namespace,
            $typeContext->uses,
            $templates,
        );
    }

    /**
     * @return array<string, string>
     */
    private function collectUses(\ReflectionClass $reflection): array
    {
        $fileName = $reflection->getFileName();
        if (!is_string($fileName) || !is_file($fileName)) {
            return [];
        }

        if (false === $lines = @file($fileName)) {
            throw new RuntimeException(sprintf('Unable to read file "%s".', $fileName));
        }

        $uses = [];
        $inUseSection = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, 'use ')) {
                $inUseSection = true;
                $use = explode(' as ', substr($line, 4, -2), 2);

                $alias = 1 === count($use) ? substr($use[0], false !== ($p = strrpos($use[0], '\\')) ? 1 + $p : 0) : $use[1];
                $uses[$alias] = $use[0];
            } elseif ($inUseSection) {
                break;
            }
        }

        $traitUses = [];
        foreach ($reflection->getTraits() as $traitReflection) {
            $traitUses[] = $this->collectUses($traitReflection);
        }

        return array_merge($uses, ...$traitUses);
    }

    /**
     * @param  \ReflectionClass|\ReflectionFunctionAbstract $reflection
     * @return array<string, Type>
     */
    private function collectTemplates($reflection, TypeContext $typeContext): array
    {
        if (!$this->stringTypeResolver || !class_exists(PhpDocParser::class)) {
            return [];
        }

        if (!$rawDocNode = $reflection->getDocComment()) {
            return [];
        }

        $config = new ParserConfig([]);
        $this->phpstanLexer ??= new Lexer($config);
        $this->phpstanParser ??= new PhpDocParser($config, new TypeParser($config, new ConstExprParser($config)), new ConstExprParser($config));

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));

        $templates = [];
        foreach ($this->phpstanParser->parse($tokens)->getTagsByName('@template') as $tag) {
            if (!$tag->value instanceof TemplateTagValueNode) {
                continue;
            }

            $type = Type::mixed();
            $typeString = ((string) $tag->value->bound) ?: null;

            try {
                if (null !== $typeString) {
                    $type = $this->stringTypeResolver->resolve($typeString, $typeContext);
                }
            } catch (UnsupportedException $exception) {
            }

            $templates[$tag->value->name] = $type;
        }

        return $templates;
    }
}
