<php>
new class {
    public string $name = 'Aaron';
}
</php>

<template>
  <div>
    Name: {{ name }}
  </div>
  <div>
    Fusion: {{ typeof fusion }}
  </div>
</template>

<script setup>
import {useFusion} from "$fusion/Pages/FusionPresent.js";
const {name} = useFusion(['name']);
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('import works', async ({fusion}) => {
  await fusion.visit();
  await fusion.see('Name: Aaron');
  await fusion.see('Fusion: object');
});
</script>
