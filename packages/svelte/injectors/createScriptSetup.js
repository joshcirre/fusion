/**
 * This one is super easy, all we have to do is create a new
 * script setup block and put it below the user's code.
 *
 * @param code
 * @returns {string}
 */
export default function injector(code) {
  return `
${code}
<script setup>
    import { useFusion } from "__aliasedFusionPath__";
    import useHotFusion from "@fusion/vue/hmr";
    
    // __props is a magic variable provided to script setup by Vue.
    const __fusionData = useFusion([__exportedKeysAsQuotedCsv__], __props.fusion);
    
    const {__exportedKeysAsCsv__} = __fusionData;
    
    useHotFusion(__fusionData);
</script>
`
}