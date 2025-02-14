<?php

namespace Fusion\Conformity;

use PhpParser\Error;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Str;

class Conformer
{
    public ?string $filename = null;

    protected array $transformers;

    protected ?string $fqcn = null;

    public static function make(string $content): static
    {
        return new static($content);
    }

    public function __construct(protected string $content, ?array $transformers = null)
    {
        if (!Str::startsWith($this->content, '<?php')) {
            $this->content = "<?php\n" . $this->content;
        }

        $this->rehabMissingSemicolon($this->content);

        $this->transformers = $transformers ?? $this->defaultTransformers();
    }

    protected function rehabMissingSemicolon(string $content): void
    {
        try {
            (new ParserFactory)->createForHostVersion()->parse($this->content);
        } catch (Error $e) {
            if (Str::startsWith($e->getMessage(), 'Syntax error, unexpected EOF')) {
                $pos = strrpos($this->content, '}');

                if ($pos !== false) {
                    $this->content = substr_replace($this->content, ';', $pos + 1, 0);
                }
            }
        }
    }

    public function getFullyQualifiedName(): ?string
    {
        return $this->fqcn;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    protected function defaultTransformers(): array
    {
        return [
            Transformers\ProceduralTransformer::class,
            Transformers\PropTransformer::class,
            Transformers\FunctionImportTransformer::class,
            Transformers\PropValueTransformer::class,
            Transformers\ExposeTransformer::class,
            Transformers\MountTransformer::class,
            Transformers\PropDiscoveryTransformer::class,
            Transformers\ActionDiscoveryTransformer::class,
            Transformers\AnonymousReturnTransformer::class,
            Transformers\AnonymousClassTransformer::class,
        ];
    }

    protected function setFullyQualifiedName(array $ast): void
    {
        $namespace = '';
        $className = '';

        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                $namespace = $node->name->toString();

                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        $className = $stmt->name->toString();
                        break;
                    }
                }
            } elseif ($node instanceof Class_) {
                $className = $node->name->toString();
            }
        }

        if (empty($className)) {
            return;
        }

        $this->fqcn = $namespace ? "\\{$namespace}\\{$className}" : $className;
    }

    public function conform(): string
    {
        $parser = (new ParserFactory)->createForHostVersion();

        $ast = $parser->parse($this->content);
        $ast = $this->applyTransformers($ast);

        $this->setFullyQualifiedName($ast);

        return (new Standard)->prettyPrintFile($ast);
    }

    protected function applyTransformers(array $ast): array
    {
        foreach ($this->transformers as $transformer) {
            $ast = $this->applyTransformer($ast, $transformer);
        }

        return $ast;
    }

    protected function applyTransformer($ast, $transformer): array
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NodeConnectingVisitor);
        $transformer = new $transformer($this->filename);
        $traverser->addVisitor($transformer);

        return $transformer->shouldHandle($ast) ? $traverser->traverse($ast) : $ast;
    }
}
