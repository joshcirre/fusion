<php>
new class {
    public function hello() {
        sleep(1);
    }
}
</php>

<template>
  <button @click='hello'>Click</button>
  <div v-if='hello.processing'>
    Loading 1
  </div>

  <div v-if='hello.getStatus().processing'>
    Loading 2
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
  await fusion.dontSee('Loading');
  await fusion.click('button');
  await fusion.see('Loading 1');
  await fusion.see('Loading 2');
});
</script>
