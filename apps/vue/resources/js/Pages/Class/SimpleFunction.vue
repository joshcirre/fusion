<php>
new class {
    public function mount() {
    //
    }

    public function favorite() {
        return 'favorite worked';
    }
}
</php>

<template>
  <button @click.prevent='() => favorite().then((resp) => this.response = resp)'>Favorite</button>

  {{ response }}
</template>

<script>
export default {
  data() {
    return {
      response: 'no response'
    }
  }
}
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('function works', async ({fusion}) => {
  await fusion.visit();

  // Click the favorite button - using the proxy's automatic forwarding
  await fusion.click('button');

  // Assert the response text is present
  await fusion.see('favorite worked');
});
</script>
