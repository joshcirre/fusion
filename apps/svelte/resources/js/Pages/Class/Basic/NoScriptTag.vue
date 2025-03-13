<php>
new class {
    public string $name = 'Aaron';
}
</php>

<template>
  Hello {{ name }}
</template>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('sees data', async ({fusion}) => {
  await fusion.visit();
  await fusion.see('Aaron');
});
</script>
