import replaceBlock from "../lib/replaceBlock.js";
import {FusionHelpers} from "./utils.js";

export default function optionsWithSetup(code, script, keys) {
  const {code: rewritten, remainingKeys} = FusionHelpers.processOptionsSetup(
    script.content,
    keys,
    "(typeof props !== 'undefined' && props.fusion) || (typeof arguments !== 'undefined' && arguments.length > 0 && arguments[0]?.fusion) || {}"
  );
  return [replaceBlock(code, script, rewritten), remainingKeys];
}