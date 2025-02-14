import {execSync} from 'child_process';

try {
  console.log('\x1b[36m%s\x1b[0m', '📦 Installing Fusion dependencies...');

  execSync('npm install', {
    stdio: 'inherit' // Show output in real time
  });

  console.log('\x1b[32m%s\x1b[0m', '✅ Fusion dependencies installed successfully');
} catch (error) {
  console.error('\x1b[31m%s\x1b[0m', '❌ Error installing Fusion dependencies:');
  console.error('\x1b[31m%s\x1b[0m', error.message);
  process.exit(1);
}