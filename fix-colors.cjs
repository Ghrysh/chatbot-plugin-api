const fs = require('fs');
const path = require('path');

const viewsDir = '/home/ype_/projects/chatbot-plugin-api/resources/views';

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = dir + '/' + file;
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            results = results.concat(walk(file));
        } else { 
            if (file.endsWith('.blade.php')) {
                results.push(file);
            }
        }
    });
    return results;
}

const files = walk(viewsDir);

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    let original = content;
    
    // Replace teal with blue
    content = content.replace(/teal/g, 'blue');
    // Replace indigo with blue
    content = content.replace(/indigo/g, 'blue');
    
    if (content !== original) {
        fs.writeFileSync(file, content, 'utf8');
        console.log('Updated:', file);
    }
});
console.log('Done');
