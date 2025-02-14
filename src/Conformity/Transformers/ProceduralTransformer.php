<?php

namespace Fusion\Conformity\Transformers;

use Fusion\Concerns\IsProceduralPage;
use Fusion\FusionPage;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;

class ProceduralTransformer extends Transformer
{
    /**
     * Statements we'll collect to put in runProceduralCode
     */
    protected array $statements = [];

    /**
     * Use statements we'll preserve at the top level
     */
    protected array $useStatements = [];

    public function shouldHandle(array $ast): bool
    {
        return $this->findClass($ast) === null;
    }

    public function enterNode(Node $node): ?int
    {
        // Collect use statements separately
        if ($node instanceof Use_) {
            $this->useStatements[] = $node;

            return NodeVisitor::REMOVE_NODE;
        }

        // Collect all statement nodes for our method body
        if ($node instanceof Node\Stmt) {
            $this->statements[] = $node;

            return NodeVisitor::REMOVE_NODE;
        }

        return null;
    }

    public function afterTraverse(array $nodes): array
    {
        // Create the sync props call that needs to be at the end
        $syncProps = new Expression(
            new MethodCall(
                new Variable('this'),
                'syncProps',
                [
                    new Node\Arg(
                        new Node\Expr\FuncCall(
                            new Node\Name('get_defined_vars')
                        )
                    )
                ]
            )
        );

        // Create the runProceduralCode method with collected statements
        $method = new ClassMethod(
            'runProceduralCode',
            [
                'flags' => Modifiers::PUBLIC,
                'stmts' => [...$this->statements, $syncProps]
            ]
        );

        // Create the trait use statement
        $traitUse = new TraitUse([
            new Name\FullyQualified(IsProceduralPage::class)
        ]);

        // Create the anonymous class extending FusionPage
        $class = new Class_(
            null,
            [
                'extends' => new Name(FusionPage::class),
                'stmts' => [$traitUse, $method]
            ]
        );

        // Return the use statements followed by class instantiation
        return [
            ...$this->useStatements,
            new Return_(new New_($class))
        ];
    }
}
