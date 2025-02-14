// fusionHelpers.js
import {rewriteDefault} from "@vue/compiler-sfc";

const checkAliasedImport = (code) => {
  const regex = /import\s*{([^}]+)}/gm;
  let match;
  while ((match = regex.exec(code)) !== null) {
    const imports = match[1].split(",");
    for (const imp of imports) {
      if (/^useFusion\s+as\s+\w+$/i.test(imp.trim())) {
        throw new Error("Aliased useFusion import is not allowed.");
      }
    }
  }
};

const removeUseFusionImport = (code) => {
  const regex = /^.*import\s*{[^}]*useFusion[^}]*}.*$/gm;
  return code.replace(regex, "");
};

const extractImports = (code) => {
  const importRegex = /^.*import.*$/gm;
  const imports = [];
  let remainingCode = code.replace(importRegex, (match) => {
    imports.push(match);
    return '';
  });
  return {imports, remainingCode: remainingCode.trim()};
};

const extractUseFusionKeys = (code) => {
  const regex = /useFusion\s*\(\s*\[([\s\S]*?)\]\s*\)/g;
  let handledKeys = [];
  let match;
  let isMultiline = false;

  // Check for parameterless call first
  if (/useFusion\s*\(\s*\)/.test(code)) {
    return {handledKeys: ["*"], isParameterless: true, isMultiline: false};
  }

  while ((match = regex.exec(code)) !== null) {
    if (!match[1]) continue;
    if (match[1].includes('\n')) {
      isMultiline = true;
    }
    const keysInCall = match[1]
      .replace(/\n/g, " ")
      .split(",")
      .map((k) => k.trim().replace(/['"]/g, ""))
      .filter(Boolean);
    handledKeys.push(...keysInCall);
  }

  return {
    handledKeys,
    isParameterless: false,
    isMultiline
  };
};

const validateSingleUseFusion = (code) => {
  const callCount = (code.match(/useFusion\s*\(/g) || []).length;
  if (callCount > 1) {
    throw new Error("Multiple useFusion calls are not allowed.");
  }
  return callCount > 0;
};

const rewriteUseFusionCall = (code, fusionSource) => {
  const regex = /(useFusion\s*\()(\s*\[[\s\S]*?\])?\s*\)/g;
  return code.replace(regex, (match, prefix, params) => {
    if (!params) {
      return `${prefix}[__exportedKeysAsQuotedCsv__], ${fusionSource}, true)`;
    }
    return `${prefix}${params}, ${fusionSource}, true)`;
  });
};

const calculateRemainingKeys = (allKeys, handledKeys) => {
  if (handledKeys.includes("*")) return [];
  return allKeys.filter(key => !handledKeys.includes(key));
};

const processScriptSetup = (code, keys, fusionSource = "__props.fusion") => {
  checkAliasedImport(code);
  const cleanCode = removeUseFusionImport(code);

  // Extract all imports first
  const {imports, remainingCode} = extractImports(cleanCode);

  const hasCall = validateSingleUseFusion(remainingCode);
  const {handledKeys, isParameterless, isMultiline} = extractUseFusionKeys(remainingCode);
  const remainingKeys = calculateRemainingKeys(keys, handledKeys);

  let processedCode = remainingCode;
  if (hasCall) {
    processedCode = rewriteUseFusionCall(remainingCode, fusionSource);
  }

  // Only inject extra useFusion call if we have remaining keys and it's not a multiline call
  const shouldInjectExtra = remainingKeys.length > 0 && !isMultiline;
  const injectedUseFusion = shouldInjectExtra ?
    `const {__exportedKeysAsCsv__} = useFusion([__exportedKeysAsQuotedCsv__], ${fusionSource});\n` :
    '';

  // Combine in order: our import, original imports, injected call, processed code
  const allImports = [
    'import { useFusion } from "__aliasedFusionPath__";',
    ...imports
  ].join('\n');

  return {
    code: `${allImports}\n${injectedUseFusion}${processedCode}`,
    remainingKeys: isParameterless ? [] : remainingKeys
  };
};

const processOptionsSetup = (code, keys) => {
  checkAliasedImport(code);
  const cleanCode = removeUseFusionImport(code);
  const hasCall = validateSingleUseFusion(cleanCode);

  const {handledKeys, isParameterless} = extractUseFusionKeys(cleanCode);
  let remainingKeys = calculateRemainingKeys(keys, handledKeys);

  const rewrittenCode = hasCall ?
    rewriteUseFusionCall(cleanCode, "__fusionProvidedProps.fusion || {}") :
    cleanCode;
  remainingKeys = isParameterless ? [] : remainingKeys;

  const withDefaultRewritten = rewriteDefault(rewrittenCode, "__default__");

  const wrapped = `let __fusionProvidedProps;

${withDefaultRewritten}

import { useFusion } from "__aliasedFusionPath__";

const userSetup = __default__.setup;

__default__.setup = function(props, ctx) {
  __fusionProvidedProps = props;
  const fusionData = ${remainingKeys.length > 0 ?
    `useFusion([__exportedKeysAsQuotedCsv__], props.fusion || {})` :
    '{}'};
  let userReturns = typeof userSetup === 'function' ? userSetup(props, ctx) : {};
  return { ...fusionData, ...userReturns };
};

export default __default__;`;

  return {code: wrapped, remainingKeys};
};

export const FusionHelpers = {
  processScriptSetup,
  processOptionsSetup
};