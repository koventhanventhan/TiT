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
    
    let newContent = content
        .replace(/asian-student-with-laptop-smiles-cut-out-transparent-png\.png/g, 'asian-student.webp')
        .replace(/venthan1\.jpg/g, 'venthan1.webp')
        .replace(/venthan2\.jpg/g, 'venthan2.webp')
        .replace(/Thesis-amico\.png/g, 'Thesis-amico.webp');
    
    if (content !== newContent) {
        fs.writeFileSync(f, newContent);
        fixed++;
        console.log('Updated references in:', f);
    }
});

console.log('Total files updated:', fixed);
