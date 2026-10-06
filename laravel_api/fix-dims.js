const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        file = path.join(dir, file);
        if (fs.statSync(file).isDirectory()) {
            results = results.concat(walk(file));
        } else if (file.endsWith('.jsx')) {
            results.push(file);
        }
    });
    return results;
}

const files = walk('src');
let fixed = 0;

files.forEach(f => {
    let content = fs.readFileSync(f, 'utf8');
    
    // Add width and height if missing
    let newContent = content.replace(/<img(?![^>]*\bwidth=)/g, '<img width="800" height="600"');
    
    if (content !== newContent) {
        fs.writeFileSync(f, newContent);
        fixed++;
        console.log('Added width/height to:', f);
    }
});

console.log('Total fixed images:', fixed);
