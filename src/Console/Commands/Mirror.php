<?php

namespace Fusion\Console\Commands;

use Fusion\Concerns\IsProceduralPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpParser\Comment;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use ReflectionClass;

class Mirror extends Command
{
    protected $signature = 'fusion:mirror';

    protected $description = 'Mirror method signatures from IsProceduralPage to functions.php';

    protected array $mirroredMethods = [
        'prop',
        'expose',
        'mount'
    ];

    protected array $typeMap = [];

    protected ?string $namespace = null;

    public function handle()
    {
        $parser = (new ParserFactory)->createForHostVersion();
        $prettyPrinter = new PrettyPrinter\Standard;

        try {
            // Get the trait content
            $code = file_get_contents((new ReflectionClass(IsProceduralPage::class))->getFileName());
            $ast = $parser->parse($code);

            // Parse the code
            $ast = $parser->parse($code);

            // Extract namespace and build type map
            $this->buildTypeMap($ast);

            // Find all methods
            $nodeFinder = new NodeFinder;
            $methods = $nodeFinder->findInstanceOf($ast, ClassMethod::class);

            // Filter and transform methods
            $functions = [];
            foreach ($methods as $method) {
                if (!in_array($method->name->toString(), $this->mirroredMethods)) {
                    continue;
                }

                // Create function signature
                $function = $this->methodToFunction($method);
                $functions[] = $prettyPrinter->prettyPrint([$function]) . "\n";
            }

            // Create the output
            $output = "<?php\n\n";
            $output .= "namespace Fusion;\n\n";
            $output .= implode("\n", $functions);

            // Write to functions.php
            File::put(__DIR__ . '/../../../functions.php', $output);

            $this->info('Successfully mirrored method signatures to functions.php');

        } catch (Error $error) {
            $this->error("Parse error: {$error->getMessage()}");

            return 1;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    protected function buildTypeMap(array $ast): void
    {
        $nodeFinder = new NodeFinder;

        // Find namespace
        $namespaceNodes = $nodeFinder->findInstanceOf($ast, Node\Stmt\Namespace_::class);
        if (count($namespaceNodes) > 0) {
            $this->namespace = $namespaceNodes[0]->name->toString();
        }

        // Find use statements
        $useNodes = $nodeFinder->findInstanceOf($ast, Use_::class);

        foreach ($useNodes as $use) {
            foreach ($use->uses as $useUse) {
                $shortName = $useUse->name->getLast();
                $fullName = $useUse->name->toString();

                // If there's an alias, use it instead of the last part
                if ($useUse->alias) {
                    $shortName = $useUse->alias->toString();
                }

                $this->typeMap[$shortName] = $fullName;
            }
        }

        // Add some PHP built-in types that might not be in use statements
        $this->typeMap['Closure'] = 'Closure';
        $this->typeMap['string'] = 'string';
        $this->typeMap['int'] = 'int';
        $this->typeMap['float'] = 'float';
        $this->typeMap['bool'] = 'bool';
        $this->typeMap['array'] = 'array';
        $this->typeMap['callable'] = 'callable';
        $this->typeMap['void'] = 'void';
        $this->typeMap['mixed'] = 'mixed';
    }

    protected function resolveType(Node\Name $type): Node\Name
    {
        $typeName = $type->toString();

        // If it's already fully qualified, return as is
        if ($type instanceof FullyQualified) {
            return $type;
        }

        // Check if we have this type in our map
        if (isset($this->typeMap[$typeName])) {
            return new FullyQualified($this->typeMap[$typeName]);
        }

        // If not in map but we have a namespace, assume it's in current namespace
        if ($this->namespace) {
            return new FullyQualified($this->namespace . '\\' . $typeName);
        }

        return $type;
    }

    protected function methodToFunction(ClassMethod $method): Node\Stmt\Function_
    {
        // Process parameters to ensure types are fully qualified
        $params = array_map(function ($param) {
            if ($param->type instanceof Node\Name) {
                $param->type = $this->resolveType($param->type);
            }

            return $param;
        }, $method->params);

        // Process return type if it exists
        $returnType = $method->returnType;
        if ($returnType instanceof Node\Name) {
            $returnType = $this->resolveType($returnType);
        }

        // Create docblock
        $docComment = new Comment\Doc(sprintf(
            "/**\n * @see %s::%s\n */",
            '\\' . IsProceduralPage::class,
            $method->name->toString()
        ));

        // Create implementation comment
        $implementationComment = new Node\Stmt\Nop;
        $implementationComment->setAttribute('comments', [
            new Comment('// This function is implemented in IsProceduralPage')
        ]);

        // Create function with fully qualified types
        $function = new Node\Stmt\Function_(
            $method->name,
            [
                'params' => $params,
                'returnType' => $returnType,
                'stmts' => [$implementationComment],
                'attrGroups' => [],
                'byRef' => false,
            ]
        );

        // Set the docblock as an attribute
        $function->setAttribute('comments', [$docComment]);

        return $function;
    }
}
