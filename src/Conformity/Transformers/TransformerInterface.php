<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Fusion\Conformity\Transformers;

interface TransformerInterface
{
    /**
     * Determine if this transformer should handle the given AST
     *
     * @param  array  $ast  The PHP Parser AST
     */
    public function shouldHandle(array $ast): bool;
}
