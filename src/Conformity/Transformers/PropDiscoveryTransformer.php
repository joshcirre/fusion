<?php

namespace Fusion\Conformity\Transformers;

use Fusion\Attributes\ServerOnly;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

class PropDiscoveryTransformer extends Transformer
{
    /**
     * Props we've discovered while traversing
     */
    protected array $discoveries = [];

    public function shouldHandle(array $ast): bool
    {
        return $this->hasProceduralTrait($ast);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->discoveries = [];

        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($this->isPropCall($node)) {
            $this->extractPropName($node);
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        if (empty($this->discoveries)) {
            return null;
        }

        $class = $this->findClass($nodes);
        if (!$class) {
            return null;
        }

        // Create property values array with discovered prop names
        $values = array_map(
            fn($discovery) => new Node\Expr\ArrayItem(new String_($discovery)),
            array_unique($this->discoveries)
        );

        // Create the property with ServerOnly attribute
        $property = $this->createClassProperty(
            'discoveredProps',
            new Array_($values),
            modifiers: Modifiers::PUBLIC,
            attributes: [ServerOnly::class]
        );

        $this->addPropertyToClass($class, $property);

        return $nodes;
    }

    protected function isPropCall(Node $node): bool
    {
        return $this->isThisMethodCall($node, 'prop');
    }

    protected function extractPropName(Node\Expr\MethodCall $node): void
    {
        foreach ($node->args as $arg) {
            if (isset($arg->name) &&
                $arg->name->name === 'name' &&
                $arg->value instanceof String_) {
                $this->discoveries[] = $arg->value->value;
            }
        }
    }
}
