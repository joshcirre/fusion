<php>
$value = prop('Cant change me!')->readonly();
</php>

<template>
  {{ value }}
</template>

<script setup>
import {useFusion} from "$fusion/Pages/Procedural/SyncState.js";
import {onMounted} from "vue";

const {fusion, value} = useFusion(['fusion', 'value']);

onMounted(function () {
  value.value = 'Oh yes I can';

  fusion.sync();
})
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('readonly', async ({fusion}) => {
  await fusion.visit();
  await fusion.waitForTimeout(500);
  await fusion.see('Cant change me!');
});
</script>
