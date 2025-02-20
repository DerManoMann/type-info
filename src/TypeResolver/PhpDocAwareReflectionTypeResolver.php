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

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeContext\TypeContextFactory;
use function sprintf;

/**
 * Resolves type on reflection prioriziting PHP documentation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class PhpDocAwareReflectionTypeResolver implements TypeResolverInterface
{
    private PhpDocParser $phpDocParser;
    private Lexer $lexer;

    private TypeResolverInterface $reflectionTypeResolver;
    private TypeResolverInterface $stringTypeResolver;
    private TypeContextFactory $typeContextFactory;

    public function __construct(
        TypeResolverInterface $reflectionTypeResolver,
        TypeResolverInterface $stringTypeResolver,
        TypeContextFactory $typeContextFactory,
        ?PhpDocParser $phpDocParser = null,
        ?Lexer $lexer = null
    ) {
        $this->reflectionTypeResolver = $reflectionTypeResolver;
        $this->stringTypeResolver = $stringTypeResolver;
        $this->typeContextFactory = $typeContextFactory;

        $this->lexer = $lexer ?? new Lexer(new ParserConfig([]));
        $this->phpDocParser = $phpDocParser ?? new PhpDocParser(
            $config = new ParserConfig([]),
            new TypeParser($config, $constExprParser = new ConstExprParser($config)),
            $constExprParser,
        );
    }

    public function resolve($subject, ?TypeContext $typeContext = null): Type
    {
        if (!$subject instanceof \ReflectionProperty && !$subject instanceof \ReflectionParameter && !$subject instanceof \ReflectionFunctionAbstract) {
            throw new UnsupportedException(sprintf('Expected subject to be a "ReflectionProperty", a "ReflectionParameter" or a "ReflectionFunctionAbstract", "%s" given.', get_debug_type($subject)), $subject);
        }

        switch (true) {
            case $subject instanceof \ReflectionProperty: $docComment =  $subject->getDocComment();
                break;
            case $subject instanceof \ReflectionParameter: $docComment =  $subject->getDeclaringFunction()->getDocComment();
                break;
            case $subject instanceof \ReflectionFunctionAbstract: $docComment =  $subject->getDocComment();
                break;
        }

        if (!$docComment) {
            return $this->reflectionTypeResolver->resolve($subject);
        }

        $typeContext ??= $this->typeContextFactory->createFromReflection($subject);

        switch (true) {
            case $subject instanceof \ReflectionProperty: $tagName =  '@var';
                break;
            case $subject instanceof \ReflectionParameter: $tagName =  '@param';
                break;
            case $subject instanceof \ReflectionFunctionAbstract: $tagName =  '@return';
                break;
        };

        $tokens = new TokenIterator($this->lexer->tokenize($docComment));
        $docNode = $this->phpDocParser->parse($tokens);

        foreach ($docNode->getTagsByName($tagName) as $tag) {
            $tagValue = $tag->value;

            if (
                $tagValue instanceof VarTagValueNode
                || $tagValue instanceof ParamTagValueNode && $tagName && '$' . $subject->getName() === $tagValue->parameterName
                || $tagValue instanceof ReturnTagValueNode
            ) {
                return $this->stringTypeResolver->resolve((string) $tagValue, $typeContext);
            }
        }

        return $this->reflectionTypeResolver->resolve($subject);
    }
}
