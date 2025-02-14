<?php

namespace Fusion\Conformity\Transformers;

use Fusion\Concerns\IsProceduralPage;
use InvalidArgumentException;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;

abstract class Transformer extends NodeVisitorAbstract implements TransformerInterface
{
    protected const PROCEDURAL_TRAIT_FQCN = IsProceduralPage::class;

    protected NodeFinder $finder;

    public function __construct(protected ?string $filename = null)
    {
        $this->finder = new NodeFinder;
    }

    /**
     * Helper to find nodes in the AST that match a condition
     */
    protected function find(array $ast, callable $filter): array
    {
        return $this->finder->find($ast, $filter);
    }

    /**
     * Helper to find a single node in the AST that matches a condition
     */
    protected function findFirst(array $ast, callable $filter): ?Node
    {
        return $this->finder->findFirst($ast, $filter);
    }

    /**
     * Check if a node matches a specific function name
     */
    protected function isNamedFunction(Node $node, string $name): bool
    {
        return $node instanceof FuncCall
            && $node->name instanceof Name
            && $node->name->toString() === $name;
    }

    /**
     * Check if a node is a method call with a specific name
     */
    protected function isMethodCall(Node $node, string $method): bool
    {
        return $node instanceof MethodCall
            && $node->name instanceof Identifier
            && $node->name->name === $method;
    }

    /**
     * Check if a node is a method call on $this with a specific name
     */
    protected function isThisMethodCall(Node $node, string $method): bool
    {
        return $this->isMethodCall($node, $method)
            && $node->var instanceof Variable
            && $node->var->name === 'this';
    }

    /**
     * Check if class uses the procedural trait
     */
    protected function hasProceduralTrait(array $ast): bool
    {
        return $this->findFirst($ast, function (Node $node) use ($ast) {
            if (!$node instanceof TraitUse) {
                return false;
            }

            foreach ($node->traits as $trait) {
                $name = ltrim($trait->toString(), '\\');

                if ($name === self::PROCEDURAL_TRAIT_FQCN) {
                    return true;
                }

                if ($name === 'IsProceduralPage') {
                    return collect($ast)
                        ->filter(fn($n) => $n instanceof Node\Stmt\Use_)
                        ->contains(fn($use) => $use->uses[0]->name->toString() === self::PROCEDURAL_TRAIT_FQCN);
                }
            }

            return false;
        }) !== null;
    }

    /**
     * Create a property with attributes on a class
     */
    protected function createClassProperty(
        string $name,
        mixed $value,
        int $modifiers = Modifiers::PUBLIC,
        array $attributes = []
    ): Property {
        return new Property(
            $modifiers,
            [
                new Node\Stmt\PropertyProperty(
                    $name,
                    $value
                )
            ],
            [],
            new Node\Identifier('array'),
            array_map(
                fn($attribute) => new AttributeGroup([
                    new Attribute(new Name("\\$attribute"))
                ]),
                $attributes
            )
        );
    }

    /**
     * Find the main class in the AST
     */
    protected function findClass(array $nodes): ?Class_
    {
        return $this->findFirst($nodes, fn($n) => $n instanceof Class_);
    }

    /**
     * Add a property to the start of a class
     */
    protected function addPropertyToClass(Class_ $class, Property $property): void
    {
        array_unshift($class->stmts, $property);
    }

    /**
     * Extract a literal value from a node
     */
    protected function extractValueFromNode(Node $node): mixed
    {
        $nodeTypeHandlers = [
            Node\Scalar\String_::class => fn($n) => $n->value,
            Node\Scalar\LNumber::class => fn($n) => $n->value,
            Node\Scalar\DNumber::class => fn($n) => $n->value,
            Node\Expr\Array_::class => fn($n) => $this->convertArrayNodeToPhp($n),
            Node\Expr\ConstFetch::class => fn($n) => $this->handleConstFetch($n),
        ];

        $nodeType = get_class($node);

        if (!isset($nodeTypeHandlers[$nodeType])) {
            throw new InvalidArgumentException("Unsupported node type: {$nodeType}");
        }

        return $nodeTypeHandlers[$nodeType]($node);
    }

    /**
     * Convert an array node to a PHP array
     */
    protected function convertArrayNodeToPhp(Node\Expr\Array_ $node): array
    {
        $result = [];

        foreach ($node->items as $item) {
            $value = $this->extractValueFromNode($item->value);

            if ($item->key !== null) {
                $key = $this->extractValueFromNode($item->key);
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Handle constant fetch nodes
     */
    protected function handleConstFetch(Node\Expr\ConstFetch $node): mixed
    {
        $constMap = [
            'true' => true,
            'false' => false,
            'null' => null,
        ];

        $name = $node->name->toLowerString();

        if (!isset($constMap[$name])) {
            throw new InvalidArgumentException("Unsupported constant: {$name}");
        }

        return $constMap[$name];
    }

    /**
     * Create a named argument for a method call
     */
    protected function createNamedArgument(Node $value, string $name): Node\Arg
    {
        return new Node\Arg($value, false, false, [], new Identifier($name));
    }

    /**
     * Replace a node in the AST
     */
    protected function replaceNode(Node $oldNode, Node $newNode): void
    {
        $parent = $oldNode->getAttribute('parent');
        if (!$parent) {
            return;
        }

        foreach ($parent->getSubNodeNames() as $subName) {
            $subNode = $parent->{$subName};

            if ($subNode === $oldNode) {
                $parent->{$subName} = $newNode;

                return;
            }

            if (is_array($subNode)) {
                foreach ($subNode as $k => $item) {
                    if ($item === $oldNode) {
                        $subNode[$k] = $newNode;
                        $parent->{$subName} = $subNode;

                        return;
                    }
                }
            }
        }
    }

    /**
     * Determine if a node type matches the expected type
     */
    protected function isNodeType(Node $node, string $type): bool
    {
        return $node instanceof $type;
    }

    /**
     * Check if a node is an assignment, optionally to a specific variable name
     */
    protected function isAssignmentNode(Node $node, ?string $variableName = null): bool
    {
        $isAssignment = $node instanceof Node\Stmt\Expression
            && $node->expr instanceof Node\Expr\Assign;

        if (!$isAssignment || $variableName === null) {
            return $isAssignment;
        }

        return $isAssignment
            && $node->expr->var instanceof Node\Expr\Variable
            && $node->expr->var->name === $variableName;
    }
}
