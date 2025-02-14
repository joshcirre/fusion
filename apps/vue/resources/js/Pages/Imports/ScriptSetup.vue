<php>
new class {
    public string $fname = 'Aaron';
    public string $lname = 'Francis';
}
</php>

<template>
  first name is: {{ fname }}
  <br>
  last name is: {{ lname }}
</template>

<script setup>
import {useFusion} from "$fusion/Pages/Imports/ScriptSetup.js";

// Only import lname. That means that fname should get auto injected by our plugin.
const {lname} = useFusion(['lname']);

lname.value = 'Smith';
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('import works', async ({fusion}) => {
  await fusion.visit();
  await fusion.see('Aaron');
  await fusion.see('Smith');
});
</script>
