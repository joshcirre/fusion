<php>
$name = prop('Aaron');
</php>

<template>
  Hello {{ name }} {{ last }}
</template>

<script>
import {ref} from "vue";

export default {
  setup() {
    return {
      last: ref('Francis')
    }
  }
}
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('sees data', async ({fusion}) => {
  await fusion.visit();
  await fusion.see('Aaron Francis');
});
</script>
