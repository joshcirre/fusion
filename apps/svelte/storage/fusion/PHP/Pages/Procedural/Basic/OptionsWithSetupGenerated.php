<?php

/**
 * This file was automatically generated by Fusion.
 * You should not edit it.
 */
namespace Fusion\Generated\Pages\Procedural\Basic;

class OptionsWithSetupGenerated extends \Fusion\FusionPage
{
    #[\Fusion\Attributes\ServerOnly]
    public array $discoveredProps = ['name'];
    use \Fusion\Concerns\IsProceduralPage;
    public function runProceduralCode()
    {
        $name = $this->prop(name: 'name', default: 'Aaron')->value();
        $this->syncProps(get_defined_vars());
    }
}