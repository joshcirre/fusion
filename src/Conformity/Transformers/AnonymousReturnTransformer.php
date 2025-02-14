<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Conformity\Transformers;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;

class AnonymousReturnTransformer extends Transformer
{
    public function shouldHandle(array $ast): bool
    {
        return $this->findFirst($ast, fn(Node $node) => $node instanceof Expression &&
                $node->expr instanceof New_ &&
                $node->expr->class instanceof Node\Stmt\Class_ &&
                $node->expr->class->name === null &&
                !($node->getAttribute('parent') instanceof Return_)
        ) !== null;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Expression ||
            !$node->expr instanceof New_ ||
            !$node->expr->class instanceof Node\Stmt\Class_ ||
            $node->expr->class->name !== null ||
            $node->getAttribute('parent') instanceof Return_) {
            return null;
        }

        return new Return_($node->expr);
    }
}
