<?php

namespace Fusion\Conformity\Transformers;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

class ExposeTransformer extends Transformer
{
    public function shouldHandle(array $ast): bool
    {
        return $this->findFirst($ast, fn(Node $node) => $this->isNamedFunction($node, 'expose')
        ) !== null;
    }

    public function enterNode(Node $node): ?Node
    {
        // Prevent assigning expose result to variable
        if ($this->isAssignmentNode($node) && $this->isNamedFunction($node->expr->expr, 'expose')) {
            throw new Exception('Cannot assign the result of `expose` to a variable.');
        }

        // Only transform standalone expose() calls
        if (!$node instanceof Expression || !$this->isNamedFunction($node->expr, 'expose')) {
            return null;
        }

        foreach ($node->expr->args as $arg) {
            /** @var Node\Arg $arg */
            if (is_null($arg->name)) {
                throw new Exception('Cannot expose an unnamed function.');
            }
        }

        return new Expression(new MethodCall(
            new Variable('this'), 'expose', $node->expr->args
        ));
    }
}
