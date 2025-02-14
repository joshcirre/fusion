<?php

namespace Fusion\Conformity\Transformers;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

class MountTransformer extends Transformer
{
    public function shouldHandle(array $ast): bool
    {
        return $this->findFirst($ast, fn(Node $node) => $this->isNamedFunction($node, 'mount')
        ) !== null;
    }

    public function enterNode(Node $node): ?Node
    {
        // Transform assignment of mount() result
        if ($this->isAssignmentNode($node)) {
            if ($this->isNamedFunction($node->expr->expr, 'mount')) {
                return $this->transformMountAssignment($node);
            }

            return null;
        }

        // Transform standalone mount() call
        if ($node instanceof Expression && $this->isNamedFunction($node->expr, 'mount')) {
            return new Expression($this->transformMountCall($node->expr));
        }

        return null;
    }

    protected function transformMountAssignment(Expression $node): Node
    {
        $assignment = $node->expr;
        $mountCall = $assignment->expr;

        $this->validateMountArguments($mountCall->args);
        $assignment->expr = $this->transformMountCall($mountCall);

        return $node;
    }

    protected function transformMountCall(Node $node): MethodCall
    {
        $this->validateMountArguments($node->args);

        return new MethodCall(
            new Variable('this'),
            'mount',
            $node->args
        );
    }

    protected function validateMountArguments(array $args): void
    {
        if (count($args) !== 1) {
            throw new Exception('Mount function must have exactly one argument.');
        }

        $arg = $args[0]->value;
        if (!$arg instanceof Node\Expr\Closure && !$arg instanceof Node\Expr\ArrowFunction) {
            throw new Exception(
                'Mount function argument must be an anonymous function or arrow function.'
            );
        }
    }
}
