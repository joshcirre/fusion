<?php

namespace Fusion\Conformity\Transformers;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;

class PropTransformer extends Transformer
{
    public function shouldHandle(array $ast): bool
    {
        return $this->findFirst($ast, fn(Node $node) => $this->isNamedFunction($node, 'prop')
        ) !== null;
    }

    public function enterNode(Node $node): ?Node
    {
        // Handle standalone or chained calls
        if ($this->isPropNode($node)) {
            return $this->transformStandaloneProp($node);
        }

        // Handle assignments like `$variable = prop(...)`
        if ($this->isAssignmentNode($node) && $this->isPropNode($node->expr->expr)) {
            return $this->transformPropAssignment($node);
        }

        return null;
    }

    protected function isPropNode(Node $node): bool
    {
        // Direct prop() call
        if ($this->isNamedFunction($node, 'prop')) {
            return true;
        }

        // Method chain starting with prop()
        if ($node instanceof MethodCall) {
            $var = $node->var;
            while ($var instanceof MethodCall) {
                $var = $var->var;
            }

            return $this->isNamedFunction($var, 'prop');
        }

        return false;
    }

    protected function transformStandaloneProp(Node $node): MethodCall
    {
        return $this->buildPropMethodCall($node, null);
    }

    protected function transformPropAssignment(Expression $node): Node
    {
        $assignment = $node->expr;
        $propExpr = $assignment->expr;
        $varName = $assignment->var->name;

        // Build call with assigned variable name
        $assignment->expr = $this->buildPropMethodCall($propExpr, $varName);

        return $node;
    }

    protected function buildPropMethodCall(Node $node, ?string $assignedVarName): MethodCall
    {
        // Extract base call and method chain
        [$basePropCall, $methodChains] = $this->extractMethodChains($node);

        // Build final prop call
        $propCall = $this->transformPropBase($basePropCall, $assignedVarName);

        // Re-apply any method chains
        foreach ($methodChains as $chain) {
            $propCall = new MethodCall($propCall, $chain['name'], $chain['args']);
        }

        return $propCall;
    }

    protected function extractMethodChains(Node $node): array
    {
        $methodChains = [];
        $basePropCall = $node;

        while ($basePropCall instanceof MethodCall) {
            if ($this->isNamedFunction($basePropCall, 'prop')) {
                break;
            }

            $methodChains[] = [
                'name' => $basePropCall->name->name,
                'args' => $basePropCall->args,
            ];

            $basePropCall = $basePropCall->var;
        }

        return [$basePropCall, array_reverse($methodChains)];
    }

    protected function transformPropBase(Node $basePropCall, ?string $assignedVarName): MethodCall
    {
        $originalArgs = $basePropCall->args;

        // Keep original args if explicitly named prop/default with no assigned name
        if ($assignedVarName === null &&
            count($originalArgs) === 2 &&
            !isset($originalArgs[0]->name) &&
            !isset($originalArgs[1]->name)) {
            return new MethodCall(
                new Variable('this'),
                'prop',
                $originalArgs
            );
        }

        return new MethodCall(
            new Variable('this'),
            'prop',
            $this->buildPropArguments($originalArgs, $assignedVarName)
        );
    }

    protected function buildPropArguments(array $originalArgs, ?string $assignedVarName): array
    {
        $nameArg = null;
        $defaultValue = null;

        // Extract name and default from original args
        foreach ($originalArgs as $arg) {
            if (isset($arg->name) && $arg->name->name === 'name') {
                $nameArg = $arg;
            } elseif (!isset($arg->name) && $defaultValue === null) {
                $defaultValue = $arg->value;
            } elseif (isset($arg->name) && $arg->name->name === 'default') {
                $defaultValue = $arg->value;
            }
        }

        $newArgs = [];

        // Build name argument
        if ($nameArg) {
            $newArgs[] = $nameArg;
        } elseif ($assignedVarName) {
            $newArgs[] = $this->createNamedArgument(new String_($assignedVarName), 'name');
        } elseif ($defaultValue instanceof Variable) {
            $newArgs[] = $this->createNamedArgument(new String_($defaultValue->name), 'name');
        }

        // Add default argument
        if ($defaultValue) {
            $newArgs[] = $this->createNamedArgument($defaultValue, 'default');
        }

        // Add any additional named args
        foreach ($originalArgs as $arg) {
            if (isset($arg->name) && !in_array($arg->name->name, ['name', 'default'])) {
                $newArgs[] = $arg;
            }
        }

        return $newArgs;
    }
}
