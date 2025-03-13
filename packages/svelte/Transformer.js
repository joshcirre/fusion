import {parse, rewriteDefault} from '@vue/compiler-sfc'
import fs from 'fs';
import path from 'path';
import * as crypto from "node:crypto";
import {spawn} from 'child_process';
import {createLogger} from 'vite'
import pc from 'picocolors';
import injectors from './injectors/index.js';
import replaceBlock from "./lib/replaceBlock.js";
import Database from "./Database.js";

export default class Transformer {
  constructor({config, code, filename, isProd}) {

    /** @type {import('@vue/compiler-sfc').SFCDescriptor} */
    this.descriptor = parse(code, {filename}).descriptor;

    /** @type {import('@vue/compiler-sfc').SFCBlock} */
    this.php = this.descriptor.customBlocks.find(block => block.type === 'php')

    // No PHP, so just return a fake transform that's a noop.
    if (!this.php) {
      return {
        transform: () => code
      };
    }

    /** @type {Object} */
    this.config = config;

    /** @type {string} */
    this.code = code;

    /** @type {string} */
    this.filename = filename;

    this.relativeFilename = filename.replace(config.paths.base, '');

    /** @type {boolean} */
    this.isProd = isProd;

    this.database = new Database(config.paths.database);

    /** @type {import('@vue/compiler-sfc').SFCBlock} */
    this.template = this.descriptor.template;

    this.logger = createLogger();

    this.hotId = 'hot_' + this.md5(this.filename);
    this.phpHash = 'php_' + this.md5(this.php.content);

    this.ensureDirectoryForFile(this.phpClassDestination());
  }

  phpClassDestination(relative = false) {
    const name = this.filename.replace(/.*\/resources\/js/, '').replace('.vue', '');

    // Replace [...ids] with DynamicIds
    let className = name.replace(/\[\.\.\.(\w+)]/g, 'Dynamic_$1');
    // Replace [id] with DynamicId
    className = className.replace(/\[(\w+)]/g, 'Dynamic_$1');
    // Replace any remaining invalid characters with underscores
    const dirSep = path.sep;
    // Escape the directory separator for regex if it's a backslash
    const escapedDirSep = dirSep === '\\' ? '\\\\' : dirSep;
    // Modify the regex to allow the directory separator
    const regex = new RegExp(`[^A-Za-z0-9${escapedDirSep}]`, 'g');
    className = className.replace(regex, '_');
    // Split the string by underscores
    const parts = className.split('_');
    // Capitalize the first letter of each part and join them
    className = parts.map(part => part.charAt(0).toUpperCase() + part.slice(1)).join('');
    // There are lots of reserved words in PHP that can't be class
    // names, so we just tack `Generated` on there. Problem solved.
    className = `${className}Generated.php`;

    let classPath = path.join(this.config.paths.phpStorage, className);

    if (relative) {
      classPath = classPath.replace(this.config.paths.base, '');
    }

    return classPath;
  }

  async transform() {
    // Temporarily pull the template so we don't accidentally replace anything out of it.
    this.code = replaceBlock(this.code, this.template, '__restoreUntouchedTemplateHere__');

    // Fully remove the PHP from the Vue SFC.
    this.code = replaceBlock(this.code, this.php, '__php__').replace('<php>__php__</php>', '');

    // Re-parse the descriptor now that the code has changed, otherwise it will be out of date.
    this.descriptor = parse(this.code).descriptor

    if (this.phpNeedsPersisting()) {
      const tmp = this.phpClassDestination() + '.tmp';

      fs.writeFileSync(tmp, this.php.content, 'utf-8');

      this.database.upsert(this.relativeFilename, {
        'php_hash': this.phpHash,
        'php_path': this.phpClassDestination(true),
      });

      try {
        await this.runPhp(['fusion:conform', this.relativeFilename]);
      } catch (e) {
        throw e;
      }

      await this.runPhp(['fusion:shim', this.relativeFilename]);
    }

    await this.injectFusion()
    this.ensureHotFusion();

    return replaceMap(this.code, {
      '__restoreUntouchedTemplateHere__': this.template.content
    })
  }

  async injectFusion() {
    const hasScriptSetup = !!this.descriptor.scriptSetup
    const hasScript = !!this.descriptor.script
    const hasSetupFunction = hasScript && /(^|\s)setup\s*\([^)]*\)\s*\{/.test(this.descriptor.script.content);

    const fullFusionPath = this.filename
      .replace(this.config.paths.js, this.config.paths.jsStorage)
      .replace('.vue', `.js?v=${Date.now()}`)

    const aliasedFusionPath = fullFusionPath
      .replace(this.config.paths.jsStorage, '$fusion');

    const {state, actions} = await import(fullFusionPath);

    // Always include the magic `fusion` key, which holds `sync`, amongst other things.
    let exportedKeys = [...state, ...actions, 'fusion'];

    let keys;
    if (hasScriptSetup) {
      [this.code, keys] = injectors.modifyScriptSetup(this.code, this.descriptor.scriptSetup, exportedKeys);
    } else if (hasScript && hasSetupFunction) {
      [this.code, keys] = injectors.optionsWithSetup(this.code, this.descriptor.script, exportedKeys);
    } else if (hasScript) {
      [this.code, keys] = injectors.optionsWithoutSetup(this.code, this.descriptor.script, exportedKeys);
    } else {
      this.code = injectors.createScriptSetup(this.code);
      keys = exportedKeys;
    }

    this.code = replaceMap(this.code, {
      "__aliasedFusionPath__": aliasedFusionPath,
      "__exportedKeysAsCsv__": keys.join(','),
      "__exportedKeysAsQuotedCsv__": keys.map(k => `"${k}"`).join(',')
    });
  }

  ensureHotFusion() {
    // We need them to define the fusion variable name, so we capture it here.
    const regex = /useHotFusion\(\s*([^)]+)\s*\)/;

    // Then we can fill in the rest of the function call.
    this.code = this.code.replace(regex, (match, paramName) => `useHotFusion(${paramName}, {id: "${this.hotId}", hot: import.meta?.hot})`);

    // And change the function to a noop.
    if (this.isProd) {
      this.code = this.code.replace('import useHotFusion from ', 'import { noop as useHotFusion } from ')
    }
  }

  ensureDirectoryForFile(path) {
    this.ensureDirectory(path, true);
  }

  ensureDirectory(dir, isFile = false) {
    if (isFile) {
      dir = path.dirname(dir);
    }

    fs.mkdirSync(dir, {
      recursive: true
    });
  }

  phpNeedsPersisting() {
    const component = this.database.get(this.relativeFilename);

    // First check if component exists in database and has required paths
    if (!component || !component.php_path || !component.shim_path) {
      return true;
    }

    // Create absolute paths by prepending the base path
    const absolutePhpPath = path.join(this.config.paths.base, component.php_path);
    const absoluteShimPath = path.join(this.config.paths.base, component.shim_path);

    // Check if the files exist at their absolute paths
    if (!fs.existsSync(absolutePhpPath) || !fs.existsSync(absoluteShimPath)) {
      return true;
    }

    // Only compare hashes if both database records and files exist
    return component.php_hash !== this.phpHash;
  }

  async runPhp(args) {

    return new Promise((resolve, reject) => {
      const [php, ...rest] = this.config.artisan.split(' ');

      const process = spawn(php, [...rest, ...args]);

      let stdoutData = '';
      let stderrData = '';

      process.stdout.on('data', (data) => {
        stdoutData += data.toString();
      });

      process.stderr.on('data', (data) => {
        stderrData += data.toString();
      });

      process.on('close', (code) => {
        // 65 means there was a PHP parse error. We'll do our best
        // to show the user exactly where it happened.
        if (code === 65) {
          const parseError = JSON.parse(stdoutData);
          const error = new Error('[PHP Parse Error] ' + parseError.message);

          error.loc = {
            ...parseError.loc,
            filename: this.filename
          };

          // Account for where the <php> block shows up in the SFC.
          error.loc.line += (this.php.loc.start.line - 1);

          // The PHP parser is 1-based, Vite is 0.
          error.loc.column -= 1;

          return reject(error);
        }

        if (code !== 0) {
          this.error(`PHP process exited with code: ${code}`);
          this.error(`PHP stdErr output: ${stderrData}`);
          this.error(`PHP stdOut output: ${stdoutData}`);

          return reject(new Error(stderrData));
        }

        if (stdoutData) {
          this.info(stdoutData.trim());
        }

        resolve();
      });
    });
  }

  md5(string) {
    return crypto.createHash('md5').update(string).digest('hex');
  }

  info(msg) {
    this.log(msg, 'info');
  }

  error(msg) {
    this.log(msg, 'error');
  }

  log(msg, type = 'info') {
    if (typeof msg !== 'string') {
      msg = JSON.stringify(msg);
    }

    this.logger[type](pc.blueBright(`[fusion] `) + msg.replace(this.config.paths.base, ''), {timestamp: true});
  }
}

function replaceMap(str, map) {
  for (const [find, replace] of Object.entries(map)) {
    str = str.replaceAll(find, replace);
  }

  return str;
}