<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace Fusion\Console\Commands;

use Exception;
use Fusion\Conformity\Conformer;
use Fusion\Models\Component;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class Conform extends Command
{
    protected $signature = 'fusion:conform {src}';

    protected $description = 'Conform a user file to the Fusion standard.';

    protected Conformer $conformer;

    protected string $code;

    protected string $hash;

    protected string $destination;

    protected string $tmp;

    protected Component $component;

    public function handle()
    {
        $this->component = Component::where('src', $this->argument('src'))->firstOrFail();

        // Vite writes this on its side.
        $this->destination = base_path($this->component->php_path);

        // But the user's code actually gets written here.
        $this->tmp = $this->destination . '.tmp';

        try {
            $this->code = $this->getUserCode();
        } catch (Exception $e) {
            // I'm not sure we actually care if the tmp file is gone. In all
            // likelihood that just means that it's already been conformed.
            return 0;
        }

        $this->code = str($this->code)->prepend('<?php ')->value();

        $this->conformer = Conformer::make($this->code)->setFilename($this->destination);

        $result = $this->conformUserCode();

        if (is_int($result)) {
            return $result;
        }

        $this->component->update([
            'php_class' => $this->conformer->getFullyQualifiedName()
        ]);

        file_put_contents($this->destination, $result);

        // We need to invalidate the opcache in the HTTP context, so we send a request.
        $invalidate = route('hmr.invalidate', [
            'file' => $this->destination
        ]);

        try {

            Http::get($invalidate);
        } catch (Throwable $e) {
            //
        }

        echo "Conformed $this->destination\n";

        return 0;
    }

    protected function getUserCode(): string
    {
        if (!file_exists($this->tmp)) {
            throw new Exception("PHP tmp file not found for [{$this->component->src}], looked for [$this->tmp].");
        }

        $code = file_get_contents($this->tmp);

        if ($code === false) {
            throw new Exception("Unable to read file [$this->tmp].");
        }

        return $code;
    }

    protected function conformUserCode(): string|int
    {
        try {
            $result = $this->conformer->conform();
        } catch (\PhpParser\Error $exception) {
            $exception->setStartLine(
                $exception->getStartLine()
            );

            $formatted = [
                'message' => $exception->getMessage(),
                'loc' => [
                    'file' => $this->argument('src'),
                    'line' => $exception->getStartLine(),
                ]
            ];

            if ($exception->hasColumnInfo()) {
                $formatted['loc']['column'] = $exception->getStartColumn($this->code);
            }

            // For Vite
            echo json_encode($formatted);

            // Proper(ish) code to alert Vite.
            return 65;
        } catch (Throwable $exception) {
            return 1;
        } finally {
            @unlink($this->tmp);
        }

        return $result;
    }
}
