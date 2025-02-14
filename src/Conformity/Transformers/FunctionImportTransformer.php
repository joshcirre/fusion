<?php

namespace Fusion\Conformity\Transformers;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;

class FunctionImportTransformer extends Transformer
{
    /**
     * Known function names that should be removed from imports
     */
    protected array $functionNames = [];

    public function __construct(?string $filename = null)
    {
        parent::__construct($filename);
        $this->loadFunctionNames();
    }

    public function shouldHandle(array $ast): bool
    {
        if (empty($this->functionNames)) {
            return false;
        }

        return $this->findFirst($ast, fn(Node $node) => $this->isFunctionUseStatement($node) ||
                $this->hasNamedFunctionImport($node)
        ) !== null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($this->isFunctionUseStatement($node)) {
            return $this->handleFunctionUse($node);
        }

        if ($node instanceof Node\Stmt\GroupUse) {
            return $this->handleGroupUse($node);
        }

        return null;
    }

    protected function loadFunctionNames(): void
    {
        $functionsPath = __DIR__ . '/../../../functions.php';

        if (!file_exists($functionsPath)) {
            return;
        }

        try {
            $parser = (new \PhpParser\ParserFactory)->createForHostVersion();
            $ast = $parser->parse(file_get_contents($functionsPath));

            $functions = $this->find($ast, fn(Node $node) => $node instanceof Node\Stmt\Function_
            );

            $this->functionNames = array_map(
                fn(Node\Stmt\Function_ $function) => $function->name->toString(),
                $functions
            );

        } catch (\Throwable) {
            $this->functionNames = [];
        }
    }

    protected function isFunctionUseStatement(Node $node): bool
    {
        return $node instanceof Use_ &&
            $node->type === Use_::TYPE_FUNCTION &&
            !empty($node->uses);
    }

    protected function hasNamedFunctionImport(Node $node): bool
    {
        if (!$node instanceof Node\Stmt\GroupUse) {
            return false;
        }

        if ($node->type === Use_::TYPE_FUNCTION) {
            return true;
        }

        foreach ($node->uses as $use) {
            if ($use->type === Use_::TYPE_FUNCTION ||
                in_array($use->name->getLast(), $this->functionNames)) {
                return true;
            }
        }

        return false;
    }

    protected function handleFunctionUse(Use_ $node): ?int
    {
        $hasRemainingImports = false;

        foreach ($node->uses as $key => $use) {
            if (in_array($use->name->getLast(), $this->functionNames)) {
                unset($node->uses[$key]);
            } else {
                $hasRemainingImports = true;
            }
        }

        if ($hasRemainingImports) {
            $node->uses = array_values($node->uses);

            return null;
        }

        return NodeVisitor::REMOVE_NODE;
    }

    protected function handleGroupUse(Node\Stmt\GroupUse $node): ?int
    {
        $hasRemainingImports = false;

        foreach ($node->uses as $key => $use) {
            if ($use->type === Use_::TYPE_FUNCTION ||
                in_array($use->name->getLast(), $this->functionNames)) {
                unset($node->uses[$key]);
            } else {
                $hasRemainingImports = true;
            }
        }

        if ($hasRemainingImports) {
            $node->uses = array_values($node->uses);

            return null;
        }

        return NodeVisitor::REMOVE_NODE;
    }
}
