const postcss = require('postcss');
const fs = require('fs');

const plugin = postcss.plugin('unwrap-layers', () => {
    return (root) => {
        root.walkAtRules('layer', (rule) => {
            rule.replaceWith(rule.nodes);
        });
    };
});

async function run() {
    const widgetPath = '/home/ype_/projects/chatbot-plugin-api/public/widget.js';
    let content = fs.readFileSync(widgetPath, 'utf8');
    
    const cssStart = content.indexOf('const cssContent = `') + 'const cssContent = `'.length;
    const cssEnd = content.indexOf('`;\n    \n    // Load Tailwind CSS');
    
    if (cssStart < 0 || cssEnd < 0) {
        console.error("Failed to find CSS");
        return;
    }
    
    let rawCss = content.slice(cssStart, cssEnd);
    
    const result = await postcss([plugin()]).process(rawCss, { from: undefined });
    
    const newContent = content.slice(0, cssStart) + result.css + content.slice(cssEnd);
    fs.writeFileSync(widgetPath, newContent, 'utf8');
    console.log("Unwrapped layers successfully!");
}

run();
