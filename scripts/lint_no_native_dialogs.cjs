#!/usr/bin/env node
/**
 * Linter to ensure zero native browser dialogs (alert, confirm, prompt)
 * exist across the React frontend and Blade admin templates.
 */

const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');

const SCAN_DIRS = [
  path.join(ROOT_DIR, 'src'),
  path.join(ROOT_DIR, 'laravel_api', 'src'),
  path.join(ROOT_DIR, 'laravel_api', 'backend', 'resources', 'views'),
  path.join(ROOT_DIR, 'laravel_api', 'backend', 'public', 'admin-theme', 'js'),
];

// Patterns that identify native dialog calls
const FORBIDDEN_PATTERNS = [
  { name: 'window.alert', regex: /\bwindow\.alert\s*\(/ },
  { name: 'window.confirm', regex: /\bwindow\.confirm\s*\(/ },
  { name: 'window.prompt', regex: /\bwindow\.prompt\s*\(/ },
  { name: 'alert()', regex: /(?<!\.)\balert\s*\(/ },
  { name: 'prompt()', regex: /(?<!\.)\bprompt\s*\(/ },
  // Native confirm() - matches confirm(...) unless preceded by `await ` or `TitNotify.`
  { name: 'confirm()', regex: /(?<!await\s+)(?<!TitNotify\.)(?<!\.)\bconfirm\s*\(/ },
];

function getFiles(dir, extensions) {
  let results = [];
  if (!fs.existsSync(dir)) return results;

  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (['node_modules', 'vendor', 'dist', '.git', 'coverage', 'plugins-init', 'filament'].includes(entry.name)) continue;
      results = results.concat(getFiles(fullPath, extensions));
    } else if (entry.isFile()) {
      const ext = path.extname(entry.name).toLowerCase();
      if (extensions.includes(ext) || entry.name.endsWith('.blade.php')) {
        results.push(fullPath);
      }
    }
  }
  return results;
}

function lintFiles() {
  console.log('=== LINT: NO NATIVE DIALOGS (alert, confirm, prompt) ===');
  let files = [];
  for (const dir of SCAN_DIRS) {
    files = files.concat(getFiles(dir, ['.js', '.jsx', '.ts', '.tsx', '.php']));
  }

  // Deduplicate
  files = Array.from(new Set(files));
  let violations = [];

  for (const file of files) {
    const content = fs.readFileSync(file, 'utf-8');
    const lines = content.split('\n');

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const trimmed = line.trim();

      // Skip comments
      if (trimmed.startsWith('//') || trimmed.startsWith('/*') || trimmed.startsWith('*') || trimmed.startsWith('{{--')) {
        continue;
      }

      for (const pattern of FORBIDDEN_PATTERNS) {
        if (pattern.regex.test(line)) {
          // Allow explicit false-positive exclusions if any
          const relPath = path.relative(ROOT_DIR, file).replace(/\\/g, '/');
          violations.push({
            file: relPath,
            line: i + 1,
            pattern: pattern.name,
            content: trimmed,
          });
        }
      }
    }
  }

  if (violations.length > 0) {
    console.error(`\nFAILED: Found ${violations.length} native dialog violations:`);
    for (const v of violations) {
      console.error(`  - ${v.file}:${v.line} [${v.pattern}]: ${v.content}`);
    }
    process.exit(1);
  } else {
    console.log(`PASS: Scanned ${files.length} files. Zero native browser dialogs found.`);
    process.exit(0);
  }
}

if (require.main === module) {
  lintFiles();
}

module.exports = { lintFiles };
