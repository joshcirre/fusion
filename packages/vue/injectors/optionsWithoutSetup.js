import {rewriteDefault} from "@vue/compiler-sfc";
import replaceBlock from "../lib/replaceBlock.js";

export default function injector(code, script, keys) {
  const rewritten = rewriteDefault(script.content, '__default__') + `
    import {useFusion as __useFusion} from "__aliasedFusionPath__";
    
    __default__.setup = function(props) {
      return __useFusion([__exportedKeysAsQuotedCsv__], props.fusion);
    };
    
    export default __default__;
    `

  // Swap out the original script block with our new one.
  return [replaceBlock(code, script, rewritten), keys];
}
