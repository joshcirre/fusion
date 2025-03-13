<php>
use \Fusion\Attributes\IsReadOnly;

new class {
    public string $name = 'Aaron';

    #[IsReadOnly]
    public string $computed = '';

    public function mount()
    {
        $this->computed= 'Name is: '. $this->name;
    }
}
</php>

<template>
  Prop: {{ name }}
  <br>
  {{ computed }}
</template>

<script setup>
import {useFusion} from "$fusion/Pages/Procedural/SyncState.js";
import {onMounted} from "vue";

const {fusion, name} = useFusion(['fusion', 'name']);

onMounted(function () {
  name.value = 'Foobar';

  fusion.sync();
})
</script>

<script test>
import {test} from '@pw/playwright.extension.js'

/**
 * @param {{ fusion: import('./FusionPage.js').FusionPage }} fixtures
 */
test('sync state works', async ({fusion}) => {
  await fusion.visit();
  await fusion.waitForTimeout(500);
  await fusion.see('Name is: Foobar');
});
</script>
