const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

async function processImages() {
    const pubDir = path.join(__dirname, 'public');
    
    // Process asian student png
    await sharp(path.join(pubDir, 'asian-student-with-laptop-smiles-cut-out-transparent-png.png'))
        .resize({ width: 800 })
        .webp({ quality: 80 })
        .toFile(path.join(pubDir, 'asian-student.webp'));
        
    // Process venthan1
    await sharp(path.join(pubDir, 'venthan1.jpg'))
        .resize({ width: 800 })
        .webp({ quality: 80 })
        .toFile(path.join(pubDir, 'venthan1.webp'));
        
    // Process venthan2
    await sharp(path.join(pubDir, 'venthan2.jpg'))
        .resize({ width: 800 })
        .webp({ quality: 80 })
        .toFile(path.join(pubDir, 'venthan2.webp'));
        
    // Process Thesis-amico.png if it exists
    const thesisAmicoPath = path.join(pubDir, 'Thesis-amico.png');
    if (fs.existsSync(thesisAmicoPath)) {
        await sharp(thesisAmicoPath)
            .resize({ width: 800 })
            .webp({ quality: 80 })
            .toFile(path.join(pubDir, 'Thesis-amico.webp'));
    }
    
    console.log('Images converted to WebP');
}

processImages().catch(console.error);
