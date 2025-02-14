import replaceBlock from "../lib/replaceBlock.js";
import {FusionHelpers} from "./utils.js";

export default function modifyScriptSetup(code, script, keys) {
  const {code: rewritten, remainingKeys} = FusionHelpers.processScriptSetup(
    script.content,
    keys,
    "__props.fusion"
  );
  return [replaceBlock(code, script, rewritten), remainingKeys];
}