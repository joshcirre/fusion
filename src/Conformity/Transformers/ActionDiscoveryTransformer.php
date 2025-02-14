<?php

namespace Fusion\Conformity\Transformers;

use Fusion\Attributes\Expose;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Function_ as FunctionName;
use PhpParser\Node\Stmt\ClassMethod;

class ActionDiscoveryTransformer extends Transformer
{
    /**
     * Information about methods we need to create
     */
    protected array $methodsToCreate = [];

    public function shouldHandle(array $ast): bool
    {
        // We need both an expose call and the procedural trait
        return $this->findFirst($ast, fn(Node $node) => $this->isExposeCall($node)) !== null
            && $this->hasProceduralTrait($ast);
    }

    public function enterNode(Node $node): ?Node
    {
        if ($this->isExposeCall($node)) {
            $this->extractMethodInfo($node);
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        if (empty($this->methodsToCreate)) {
            return null;
        }

        $class = $this->findClass($nodes);
        if (!$class) {
            return null;
        }

        foreach ($this->methodsToCreate as $methodInfo) {
            $class->stmts[] = $this->createClassMethod($methodInfo);
        }

        return $nodes;
    }

    protected function isExposeCall(Node $node): bool
    {
        return $this->isThisMethodCall($node, 'expose');
    }

    protected function extractMethodInfo(Node\Expr\MethodCall $node): void
    {
        foreach ($node->args as $arg) {
            $name = $arg->name->name;
            $handler = $arg->value;

            $params = [];
            if ($handler instanceof Node\Expr\Closure || $handler instanceof Node\Expr\ArrowFunction) {
                $params = $handler->params;
            }

            $this->methodsToCreate[] = [
                'name' => $name,
                'params' => $params
            ];
        }
    }

    protected function createClassMethod(array $methodInfo): ClassMethod
    {
        // Create call_user_func to invoke the stored action handler
        $callUserFunc = new FuncCall(
            new Name('call_user_func'),
            [
                new Arg(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\PropertyFetch(
                            new Variable('this'),
                            'actions'
                        ),
                        new FunctionName
                    )
                ),
                new Arg(
                    new FuncCall(
                        new Name('func_get_args')
                    ),
                    false,
                    true
                )
            ]
        );

        return new ClassMethod(
            $methodInfo['name'],
            [
                'flags' => Modifiers::PUBLIC,
                'params' => $methodInfo['params'],
                'stmts' => [new Node\Stmt\Return_($callUserFunc)],
                'attrGroups' => [
                    new AttributeGroup([
                        new Attribute(
                            new Name\FullyQualified(Expose::class)
                        )
                    ])
                ]
            ]
        );
    }
}
