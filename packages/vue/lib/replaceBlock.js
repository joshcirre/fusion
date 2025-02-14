export default function replaceBlock(code, block, replacement = '') {
  if (!block) {
    return code;
  }

  return code.slice(0, block.loc.start.offset) + replacement + code.slice(block.loc.end.offset)
}
