<?php

namespace Fusion\Conformity\Transformers;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;

class PropValueTransformer extends Transformer
{
    public function shouldHandle(array $ast): bool
    {
        return $this->findFirst($ast, fn(Node $node) => $this->isThisPropCall($node)
        ) !== null;
    }

    /**
     * Find all method call chains that start with $this->prop()
     * and ensure they end with ->value()
     */
    public function afterTraverse(array $nodes): ?array
    {
        // Find all outermost method calls
        $outermostCalls = $this->find($nodes, fn(Node $n) => $n instanceof MethodCall &&
            !($n->getAttribute('parent') instanceof MethodCall)
        );

        foreach ($outermostCalls as $call) {
            $this->processOutermostCall($call);
        }

        return $nodes;
    }

    protected function processOutermostCall(MethodCall $outermostCall): void
    {
        // Get the chain from outermost to innermost
        $chain = $this->gatherChain($outermostCall);
        if (empty($chain)) {
            return;
        }

        // Check the innermost call is $this->prop()
        $innermost = end($chain);
        if (!$this->isThisPropCall($innermost)) {
            return;
        }

        // Skip if ->value() already exists
        if ($this->chainHasValueCall($chain)) {
            return;
        }

        // Add ->value() to the chain
        $newCall = $this->addValueToChain($chain);

        // Replace the old call in the AST
        $this->replaceNode($outermostCall, $newCall);
    }

    protected function isThisPropCall(Node $node): bool
    {
        return $node instanceof MethodCall &&
            $node->name instanceof Identifier &&
            $node->name->name === 'prop' &&
            $node->var instanceof Variable &&
            $node->var->name === 'this';
    }

    protected function gatherChain(MethodCall $start): array
    {
        $chain = [];
        $current = $start;

        while ($current instanceof MethodCall) {
            $chain[] = $current;
            $current = $current->var;
        }

        return $chain;
    }

    protected function chainHasValueCall(array $chain): bool
    {
        foreach ($chain as $call) {
            if ($call->name instanceof Identifier &&
                $call->name->name === 'value') {
                return true;
            }
        }

        return false;
    }

    protected function addValueToChain(array $chain): MethodCall
    {
        // Rebuild chain in same order and add value() at end
        $rebuilt = end($chain);

        for ($i = count($chain) - 2; $i >= 0; $i--) {
            $call = $chain[$i];
            $rebuilt = new MethodCall(
                $rebuilt,
                $call->name,
                $call->args,
                $call->getAttributes()
            );
        }

        return new MethodCall($rebuilt, 'value');
    }
}
