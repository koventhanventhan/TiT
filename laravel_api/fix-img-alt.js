const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(file));
        } else {
            if (file.endsWith('.jsx')) {
                results.push(file);
            }
        }
    });
    return results;
}

const files = walk('src');
let fixed = 0;
files.forEach(f => {
    let content = fs.readFileSync(f, 'utf8');
    // Replace <img without alt= with <img alt=""
    let newContent = content.replace(/<img(?![^>]*\balt=)/g, '<img alt=""');
    if (content !== newContent) {
        fs.writeFileSync(f, newContent);
        fixed++;
        console.log('Fixed:', f);
    }
});
console.log('Total fixed:', fixed);
