const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const distDir = path.join(rootDir, 'laravel_api', 'dist');
const distAssetsDir = path.join(distDir, 'assets');
const rootAssetsDir = path.join(rootDir, 'assets');
const distIndex = path.join(distDir, 'index.html');
const rootIndex = path.join(rootDir, 'index.html');

if (!fs.existsSync(distIndex) || !fs.existsSync(distAssetsDir)) {
  console.error('Error: laravel_api/dist build does not exist. Run npm run build in laravel_api first.');
  process.exit(1);
}

// 1. Read dist assets
const distFiles = fs.readdirSync(distAssetsDir);
console.log(`Found ${distFiles.length} files in laravel_api/dist/assets`);

// Map dist prefixes
const distPrefixMap = new Map();
for (const f of distFiles) {
  const m = f.match(/^(.+?)-[A-Za-z0-9_-]+\.(js|css)$/);
  if (m) {
    const key = `${m[1]}.${m[2]}`;
    distPrefixMap.set(key, f);
  }
}

// 2. Read existing root assets and remove stale versions that are replaced
const rootFiles = fs.readdirSync(rootAssetsDir);
let removedCount = 0;
for (const f of rootFiles) {
  const m = f.match(/^(.+?)-[A-Za-z0-9_-]+\.(js|css)$/);
  if (m) {
    const key = `${m[1]}.${m[2]}`;
    if (distPrefixMap.has(key)) {
      const newFile = distPrefixMap.get(key);
      if (f !== newFile) {
        fs.unlinkSync(path.join(rootAssetsDir, f));
        console.log(`Removed stale asset: ${f} (replaced by ${newFile})`);
        removedCount++;
      }
    }
  }
}

// 3. Copy all dist assets to root assets
for (const f of distFiles) {
  const src = path.join(distAssetsDir, f);
  const dest = path.join(rootAssetsDir, f);
  if (fs.statSync(src).isDirectory()) continue;
  fs.copyFileSync(src, dest);
}
console.log(`Copied ${distFiles.length} assets to root/assets/`);

// 4. Copy dist index.html to root index.html
fs.copyFileSync(distIndex, rootIndex);
console.log('Copied dist/index.html to root/index.html');

// 5. Verification Walker: Walk from root index.html and recursively follow all JS imports
const visited = new Set();
const missing = new Set();
const allReferenced = new Set();

function extractReferences(filePath, content) {
  const refs = new Set();
  // Find script / link tags or import() statements or standard static imports
  // Matches "assets/filename" or "./filename" or "/assets/filename"
  const regex = /["'](?:(?:\.\/|\/)?assets\/)?([A-Za-z0-9_\-\.]+\.(?:js|css))["']/g;
  let match;
  while ((match = regex.exec(content)) !== null) {
    const ref = match[1];
    if (ref.includes('-') && (ref.endsWith('.js') || ref.endsWith('.css'))) {
      refs.add(ref);
    }
  }
  return Array.from(refs);
}

const indexContent = fs.readFileSync(rootIndex, 'utf8');
const initialRefs = extractReferences(rootIndex, indexContent);

function walk(fileName) {
  if (visited.has(fileName)) return;
  visited.add(fileName);
  allReferenced.add(fileName);

  const fullPath = path.join(rootAssetsDir, fileName);
  if (!fs.existsSync(fullPath)) {
    missing.add(fileName);
    return;
  }

  if (fileName.endsWith('.js')) {
    const code = fs.readFileSync(fullPath, 'utf8');
    const childRefs = extractReferences(fullPath, code);
    for (const child of childRefs) {
      walk(child);
    }
  }
}

for (const ref of initialRefs) {
  walk(ref);
}

console.log(`Walker traversed ${visited.size} assets.`);
console.log(`Walker missing count: ${missing.size}`);
if (missing.size > 0) {
  console.error('Missing files:', Array.from(missing));
  process.exit(1);
} else {
  console.log('0 missing');
}

// 6. Check for multi-hash names in root assets
const updatedRootFiles = fs.readdirSync(rootAssetsDir);
const prefixCounts = {};
for (const f of updatedRootFiles) {
  const m = f.match(/^(.+?)-[A-Za-z0-9_-]+\.(js|css)$/);
  if (m) {
    const key = `${m[1]}.${m[2]}`;
    prefixCounts[key] = (prefixCounts[key] || 0) + 1;
  }
}
const multiHashes = Object.entries(prefixCounts).filter(([k, v]) => v > 1);
if (multiHashes.length > 0) {
  console.warn('Warning: found multi-hash names:', multiHashes);
} else {
  console.log('no multi-hash names');
}
