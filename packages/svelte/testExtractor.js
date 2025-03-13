import fs from 'fs';
import path from 'path';

class TestExtractor {
  constructor(outputDir) {
    this.outputDir = outputDir;
    this.fullOutputDir = path.join(process.cwd(), outputDir);
  }

  clearOutputDirectory() {
    if (fs.existsSync(this.fullOutputDir)) {
      fs.rmSync(this.fullOutputDir, {recursive: true, force: true});
    }
  }

  isVueFile(id) {
    return id.endsWith('.vue') || id.includes('.vue?');
  }

  processFile(code, id) {
    const filename = id.split('?')[0];

    // Match either <script test> or <test> blocks
    const scriptTestRegex = /<script\s+test\s*>([^]*?)<\/script>/;
    const testBlockRegex = /<test>([^]*?)<\/test>/;

    let match = code.match(scriptTestRegex) || code.match(testBlockRegex);

    if (!match) {
      return null;
    }

    const testContent = match[1].trim();
    const relative = filename.split('resources/js/Pages/')[1];
    const destination = path.join(this.fullOutputDir, relative.replace('.vue', '.spec.js'));

    fs.mkdirSync(path.dirname(destination), {recursive: true});
    fs.writeFileSync(destination, testContent, 'utf8');

    return {
      content: match[0],
      start: match.index,
      end: match.index + match[0].length
    };
  }

  // New method to handle file transformations
  async transformFile(filePath) {
    if (!this.isVueFile(filePath)) {
      return;
    }

    try {
      const code = await fs.promises.readFile(filePath, 'utf-8');
      return this.processFile(code, filePath);
    } catch (error) {
      console.error(`Error transforming file ${filePath}:`, error);
    }
  }
}

export default function testExtractionPlugin(options = {}) {
  const extractor = new TestExtractor(options.output);

  return {
    name: 'vite-plugin-test-extractor',
    enforce: 'pre',

    configureServer(server) {
      return () => {
        // Handle file deletion
        server.watcher.on('unlink', (filePath) => {
          if (extractor.isVueFile(filePath)) {
            // Only clear directory if a Vue file was deleted
            extractor.clearOutputDirectory();
          }
        });

        // Handle new files
        server.watcher.on('add', async (filePath) => {
          if (extractor.isVueFile(filePath)) {
            await extractor.transformFile(filePath);
          }
        });

        // Handle file changes
        server.watcher.on('change', async (filePath) => {
          if (extractor.isVueFile(filePath)) {
            await extractor.transformFile(filePath);
          }
        });
      };
    },

    // Initialize in dev mode
    configResolved(config) {
      if (config.command === 'serve') {
        extractor.clearOutputDirectory();
      }
    },

    // We still need buildStart for production builds
    buildStart() {
      // Only clear if we're in build mode (not dev)
      if (this.meta.watchMode !== true) {
        extractor.clearOutputDirectory();
      }
    },

    // Handle both build and dev transformations
    async transform(code, id) {
      if (!extractor.isVueFile(id)) {
        return null;
      }

      const testBlock = extractor.processFile(code, id);

      if (!testBlock) {
        return null;
      }

      // Remove the test block from the original code
      return {
        code: code.slice(0, testBlock.start) + code.slice(testBlock.end),
        map: null
      };
    }
  };
}