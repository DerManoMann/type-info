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

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use Radebatz\TypeInfo\SubTypeIdentifier;
use Radebatz\TypeInfo\Exception\InvalidArgumentException;
use Radebatz\TypeInfo\Exception\UnsupportedException;
use Radebatz\TypeInfo\Type;
use Radebatz\TypeInfo\Type\BuiltinType;
use Radebatz\TypeInfo\Type\CollectionType;
use Radebatz\TypeInfo\Type\GenericType;
use Radebatz\TypeInfo\Type\SubType;
use Radebatz\TypeInfo\TypeContext\TypeContext;
use Radebatz\TypeInfo\TypeIdentifier;
use function count;
use function is_string;
use function sprintf;

/**
 * Resolves type for a given string.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class StringTypeResolver implements TypeResolverInterface
{
    /**
     * @var array<string, bool>
     */
    private static array $classExistCache = [];

    private Lexer $lexer;
    private TypeParser $parser;

    public function __construct(?Lexer $lexer = null, ?TypeParser $parser = null)
    {
        $this->lexer = $lexer ?? new Lexer(new ParserConfig([]));
        $this->parser = $parser ?? new TypeParser($config = new ParserConfig([]), new ConstExprParser($config));
    }

    public function resolve($subject, ?TypeContext $typeContext = null): Type
    {
        if ($subject instanceof \Stringable) {
            $subject = (string) $subject;
        } elseif (!is_string($subject)) {
            throw new UnsupportedException(sprintf('Expected subject to be a "string", "%s" given.', get_debug_type($subject)), $subject);
        }

        try {
            $tokens = new TokenIterator($this->lexer->tokenize($subject));
            $node = $this->parser->parse($tokens);

            return $this->getTypeFromNode($node, $typeContext);
        } catch (\DomainException $e) {
            throw new UnsupportedException(sprintf('Cannot resolve "%s".', $subject), $subject, 0, $e);
        }
    }

    private function getTypeFromNode(TypeNode $node, ?TypeContext $typeContext): Type
    {
        $typeIsCollectionObject = fn (Type $type): bool => $type->isIdentifiedBy(\Traversable::class) || $type->isIdentifiedBy(\ArrayAccess::class);

        if ($node instanceof CallableTypeNode) {
            return Type::callable();
        }

        if ($node instanceof ArrayTypeNode) {
            return Type::list($this->getTypeFromNode($node->type, $typeContext));
        }

        if ($node instanceof ArrayShapeNode) {
            return Type::array();
        }

        if ($node instanceof ObjectShapeNode) {
            return Type::object();
        }

        if ($node instanceof ThisTypeNode) {
            if (null === $typeContext) {
                throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "$this".', TypeContext::class));
            }

            return Type::object($typeContext->getCalledClass());
        }

        if ($node instanceof ConstTypeNode) {
            switch (get_class($node->constExpr)) {
                case ConstExprArrayNode::class: return Type::array();
                case ConstExprFalseNode::class: return Type::false();
                case ConstExprFloatNode::class: return Type::float();
                case ConstExprIntegerNode::class: return Type::int();
                case ConstExprNullNode::class: return Type::null();
                case ConstExprStringNode::class: return Type::string();
                case ConstExprTrueNode::class: return Type::true();
                default: throw new \DomainException(sprintf('Unhandled "%s" constant expression.', get_class($node->constExpr)));
            }
        }

        if ($node instanceof IdentifierTypeNode) {
            switch ($node->name) {
                case 'bool': case 'boolean': $type = Type::bool();
                    break;
                case 'true': $type = Type::true();
                    break;
                case 'false': $type = Type::false();
                    break;
                case 'int': case 'integer': $type = Type::int();
                    break;
                case SubTypeIdentifier::POSITIVE_INT:
                case SubTypeIdentifier::NEGATIVE_INT:
                case SubTypeIdentifier::NON_POSITIVE_INT:
                case SubTypeIdentifier::NON_NEGATIVE_INT:
                case SubTypeIdentifier::NON_ZERO_INT:
                    $type = Type::int(new SubType($node->name));
                    break;
                case 'float': case 'double': $type = Type::float();
                    break;
                case 'string':  $type = Type::string();
                    break;
                case SubTypeIdentifier::CLASS_STRING:
                case SubTypeIdentifier::TRAIT_STRING:
                case SubTypeIdentifier::INTERFACE_STRING:
                case SubTypeIdentifier::CALLABLE_STRING:
                case SubTypeIdentifier::NUMERIC_STRING:
                case SubTypeIdentifier::LOWERCASE_STRING:
                case SubTypeIdentifier::NON_EMPTY_LOWERCASE_STRING:
                case SubTypeIdentifier::NON_EMPTY_STRING:
                case SubTypeIdentifier::NON_FALSY_STRING:
                case SubTypeIdentifier::TRUTHY_STRING:
                case SubTypeIdentifier::LITERAL_STRING:
                case SubTypeIdentifier::HTML_ESCAPED_STRING:
                    $type = Type::string(new SubType($node->name));
                    break;
                case 'resource': $type = Type::resource();
                    break;
                case 'object': $type = Type::object();
                    break;
                case 'callable': $type = Type::callable();
                    break;
                case 'array': $type = Type::array();
                    break;
                case 'non-empty-array': $type = Type::array();
                    break;
                case 'list': case 'non-empty-list': $type = Type::list();
                    break;
                case 'iterable': $type = Type::iterable();
                    break;
                case 'mixed': $type = Type::mixed();
                    break;
                case 'null': $type = Type::null();
                    break;
                case 'array-key': $type = Type::union(Type::int(), Type::string());
                    break;
                case 'scalar': $type = Type::union(Type::int(), Type::float(), Type::string(), Type::bool());
                    break;
                case 'number': $type = Type::union(Type::int(), Type::float());
                    break;
                case 'numeric': $type = Type::union(Type::int(), Type::float(), Type::string());
                    break;
                case 'self':
                    if (!$typeContext) {
                        throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "self".', TypeContext::class));
                    }
                    $type =Type::object($typeContext->getDeclaringClass());
                    break;
                case 'static':
                    if (!$typeContext) {
                        throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "static".', TypeContext::class));
                    }
                    $type = Type::object($typeContext->getCalledClass());
                    break;
                case 'parent':
                    if (!$typeContext) {
                        throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "parent".', TypeContext::class));
                    }
                    $type = Type::object($typeContext->getParentClass());
                    break;
                case 'void': $type = Type::void();
                    break;
                case 'never': $type = Type::never();
                    break;
                case SubTypeIdentifier::NEVER_RETURN:
                case SubTypeIdentifier::NEVER_RETURNS:
                case SubTypeIdentifier::NO_RETURN:
                    $type = Type::never(new SubType($node->name));
                    break;
                default: $type = $this->resolveCustomIdentifier($node->name, $typeContext);
                    break;
            };

            if ($typeIsCollectionObject($type)) {
                return Type::collection($type);
            }

            return $type;
        }

        if ($node instanceof NullableTypeNode) {
            return Type::nullable($this->getTypeFromNode($node->type, $typeContext));
        }

        if ($node instanceof GenericTypeNode) {
            $type = $this->getTypeFromNode($node->type, $typeContext);

            // handle integer ranges as simple integers
            if ($type->isIdentifiedBy(TypeIdentifier::INT)) {
                return $type;
            }

            $variableTypes = array_map(fn (TypeNode $t): Type => $this->getTypeFromNode($t, $typeContext), $node->genericTypes);

            if ($type instanceof CollectionType) {
                $asList = $type->isList();
                $keyType = $type->getCollectionKeyType();
                $type = $type->getWrappedType();

                if ($type instanceof GenericType) {
                    $type = $type->getWrappedType();
                }

                if (1 === count($variableTypes)) {
                    return new CollectionType(Type::generic($type, $keyType, $variableTypes[0]), $asList);
                } elseif (2 === count($variableTypes)) {
                    return Type::collection($type, $variableTypes[1], $variableTypes[0], $asList);
                }
            }

            if ($typeIsCollectionObject($type)) {
                switch (count($variableTypes)) {
                    case 1: return Type::collection($type, $variableTypes[0]);
                    case 2: return Type::collection($type, $variableTypes[1], $variableTypes[0]);
                    default: return Type::collection($type);
                };
            }

            if ($type instanceof BuiltinType && TypeIdentifier::ARRAY !== $type->getTypeIdentifier() && TypeIdentifier::ITERABLE !== $type->getTypeIdentifier()) {
                return $type;
            }

            return Type::generic($type, ...$variableTypes);
        }

        if ($node instanceof UnionTypeNode) {
            $types = [];

            foreach ($node->types as $nodeType) {
                $type = $this->getTypeFromNode($nodeType, $typeContext);

                if ($type instanceof BuiltinType && TypeIdentifier::MIXED === $type->getTypeIdentifier()) {
                    return Type::mixed();
                }

                $types[] = $type;
            }

            return Type::union(...$types);
        }

        if ($node instanceof IntersectionTypeNode) {
            return Type::intersection(...array_map(fn (TypeNode $t): Type => $this->getTypeFromNode($t, $typeContext), $node->types));
        }

        throw new \DomainException(sprintf('Unhandled "%s" node.', get_class($node)));
    }

    private function resolveCustomIdentifier(string $identifier, ?TypeContext $typeContext): Type
    {
        $className = $typeContext ? $typeContext->normalize($identifier) : $identifier;

        if (!isset(self::$classExistCache[$className])) {
            self::$classExistCache[$className] = false;

            if (class_exists($className) || interface_exists($className)) {
                self::$classExistCache[$className] = true;
            } else {
                try {
                    new \ReflectionClass($className);
                    self::$classExistCache[$className] = true;
                } catch (\Throwable $throwable) {
                }
            }
        }

        if (self::$classExistCache[$className]) {
            if (is_subclass_of($className, \UnitEnum::class)) {
                return Type::enum($className);
            }

            return Type::object($className);
        }

        if ($typeContext && isset($typeContext->templates[$identifier])) {
            return Type::template($identifier, $typeContext->templates[$identifier]);
        }

        throw new \DomainException(sprintf('Unhandled "%s" identifier.', $identifier));
    }
}
