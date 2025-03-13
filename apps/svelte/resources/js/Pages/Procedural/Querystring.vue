<php>
use function Fusion\prop;

$name = prop('Aaron')->syncQueryString();
</php>

<template>
  Hello {{ name }}
</template>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('querystring works', async ({fusion}) => {
  await fusion.visit();
  await fusion.waitForTimeout(500);
  await fusion.hasQuery('name', 'Aaron');
  await fusion.visit({name: 'Buzzzzzz'});
  await fusion.see('Buzzzzzz')
});
</script>




