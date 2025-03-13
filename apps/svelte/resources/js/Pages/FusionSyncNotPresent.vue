<php>
new class {
    //
}
</php>

<template>
  <div>
    Obj: {{ typeof fusion }}
  </div>

  <div>
    Sync: {{ typeof fusionSync }}
  </div>
</template>

<script test>
import {test} from '@pw/playwright.extension.js'
import {expect} from "@playwright/test";

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('import works', async ({fusion}) => {
  await fusion.visit();
  await fusion.see('Obj: object');
  await fusion.see('Sync: undefined');
});
</script>
