#!/usr/bin/env node
/**
 * scripts/lint_css_vars.cjs
 * Validates CSS custom properties across frontend source directories:
 * 1. Fails on direct self-referencing definitions (--x: ... var(--x) ...).
 * 2. Fails on cyclic dependency chains (--a -> --b -> ... -> --a).
 * 3. Fails on any var(--x) whose custom property is never defined.
 */

const fs = require('fs');
const path = require('path');

// Determine root directory
const rootDir = fs.existsSync(path.resolve(process.cwd(), 'laravel_api'))
  ? process.cwd()
  : path.resolve(process.cwd(), '..');

const scanDirs = [
  path.join(rootDir, 'laravel_api', 'src'),
  path.join(rootDir, 'src')
];

function getCssFiles(dir) {
  let results = [];
  if (!fs.existsSync(dir)) return results;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      results = results.concat(getCssFiles(full));
    } else if (entry.name.endsWith('.css')) {
      results.push(full);
    }
  }
  return results;
}

const allCssFiles = [];
for (const d of scanDirs) {
  for (const f of getCssFiles(d)) {
    if (!allCssFiles.includes(f)) {
      allCssFiles.push(f);
    }
  }
}

// Strip CSS comments helper
function stripComments(css) {
  return css.replace(/\/\*[\s\S]*?\*\//g, '');
}

const defRegex = /(--[a-zA-Z0-9_-]+)\s*:\s*([^;{}]+)[;}]/g;
const varRegex = /var\(\s*(--[a-zA-Z0-9_-]+)/g;

const definitions = new Map(); // propName -> [ { file, line, val } ]
const usages = new Map();      // propName -> [ { file, line, fallback } ]

for (const filePath of allCssFiles) {
  const raw = fs.readFileSync(filePath, 'utf8');
  const lines = raw.split(/\r?\n/);
  
  // Track line by line for accurate line reporting
  let inBlockComment = false;
  for (let i = 0; i < lines.length; i++) {
    let line = lines[i];
    
    // Simplistic line comment handling
    if (inBlockComment) {
      const endIdx = line.indexOf('*/');
      if (endIdx !== -1) {
        line = line.slice(endIdx + 2);
        inBlockComment = false;
      } else {
        continue;
      }
    }
    while (line.includes('/*')) {
      const startIdx = line.indexOf('/*');
      const endIdx = line.indexOf('*/', startIdx + 2);
      if (endIdx !== -1) {
        line = line.slice(0, startIdx) + line.slice(endIdx + 2);
      } else {
        line = line.slice(0, startIdx);
        inBlockComment = true;
        break;
      }
    }

    let match;
    const defLineRegex = /(--[a-zA-Z0-9_-]+)\s*:\s*([^;{}]+)[;}]/g;
    while ((match = defLineRegex.exec(line)) !== null) {
      const prop = match[1];
      const val = match[2].trim();
      if (!definitions.has(prop)) definitions.set(prop, []);
      definitions.get(prop).push({ file: filePath, line: i + 1, val });
    }

    const varLineRegex = /var\(\s*(--[a-zA-Z0-9_-]+)/g;
    while ((match = varLineRegex.exec(line)) !== null) {
      const prop = match[1];
      if (!usages.has(prop)) usages.set(prop, []);
      usages.get(prop).push({ file: filePath, line: i + 1 });
    }
  }
}

let hasError = false;

// 1. Direct Self-Reference Check
console.log('1. Checking for direct self-referencing variables...');
const selfRefs = [];
for (const [prop, defs] of definitions.entries()) {
  for (const def of defs) {
    const r = new RegExp(`var\\(\\s*${prop}[\\s,)]`);
    if (r.test(def.val)) {
      selfRefs.push({ prop, ...def });
    }
  }
}

if (selfRefs.length > 0) {
  hasError = true;
  console.error(`  FAIL: Found ${selfRefs.length} self-referencing variable(s):`);
  for (const s of selfRefs) {
    console.error(`    ${path.relative(rootDir, s.file)}:${s.line} -> ${s.prop}: ${s.val}`);
  }
} else {
  console.log('  PASS: 0 self-referencing variables found.');
}

// 2. Reference Cycle Check
console.log('\n2. Checking for dependency cycles...');
const graph = new Map();
for (const [prop, defs] of definitions.entries()) {
  if (!graph.has(prop)) graph.set(prop, new Set());
  for (const def of defs) {
    let m;
    const r = /var\(\s*(--[a-zA-Z0-9_-]+)/g;
    while ((m = r.exec(def.val)) !== null) {
      if (m[1] !== prop) { // direct self-ref handled separately
        graph.get(prop).add(m[1]);
      }
    }
  }
}

function findCycle(node, visited = new Set(), recStack = []) {
  visited.add(node);
  recStack.push(node);

  const neighbors = graph.get(node) || new Set();
  for (const next of neighbors) {
    if (!visited.has(next)) {
      const cycle = findCycle(next, visited, recStack);
      if (cycle) return cycle;
    } else if (recStack.includes(next)) {
      return [...recStack.slice(recStack.indexOf(next)), next];
    }
  }

  recStack.pop();
  return null;
}

const cycles = [];
const visited = new Set();
for (const prop of graph.keys()) {
  if (!visited.has(prop)) {
    const cycle = findCycle(prop, visited, []);
    if (cycle) {
      cycles.push(cycle);
    }
  }
}

if (cycles.length > 0) {
  hasError = true;
  console.error(`  FAIL: Found ${cycles.length} dependency cycle(s):`);
  for (const c of cycles) {
    console.error(`    Cycle: ${c.join(' -> ')}`);
  }
} else {
  console.log('  PASS: 0 dependency cycles found.');
}

// 3. Undefined Variables Check
console.log('\n3. Checking for undefined variables...');
const undefinedVars = [];
for (const [prop, useList] of usages.entries()) {
  if (!definitions.has(prop)) {
    undefinedVars.push({ prop, uses: useList });
  }
}

if (undefinedVars.length > 0) {
  hasError = true;
  console.error(`  FAIL: Found ${undefinedVars.length} undefined variable(s):`);
  for (const u of undefinedVars) {
    const locs = u.uses.slice(0, 3).map(x => `${path.relative(rootDir, x.file)}:${x.line}`).join(', ');
    console.error(`    ${u.prop} used at: ${locs}${u.uses.length > 3 ? ' (and more)' : ''}`);
  }
} else {
  console.log('  PASS: 0 undefined variables found.');
}

console.log('\n=======================================');
if (hasError) {
  console.error('LINT FAILED: CSS custom property errors found.');
  process.exit(1);
} else {
  console.log('LINT PASSED: All CSS custom properties are valid.');
  process.exit(0);
}
