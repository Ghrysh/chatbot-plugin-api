const fs = require('fs');
const jsdom = require('jsdom');
const { JSDOM } = jsdom;

const html = `
<!DOCTYPE html>
<html>
<body>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <div id="test-container"></div>
    <script>
        const license = "c61b4ff8-a4a3-4a00-ab19-354316dce7df";
        const SAAS_URL = "https://api-chatbot.futurecloud.id/api";
        const SAAS_DOMAIN = "https://api-chatbot.futurecloud.id";
    </script>
</body>
</html>
`;

const dom = new JSDOM(html, { runScripts: "dangerously", url: "http://localhost/" });
const window = dom.window;
global.window = window;
global.document = window.document;
global.localStorage = window.localStorage;
global.Date = window.Date;
global.setTimeout = window.setTimeout;
global.setInterval = window.setInterval;
global.clearInterval = window.clearInterval;

const widgetCode = fs.readFileSync('/home/ype_/projects/chatbot-plugin-api/public/chat-widget.js', 'utf8');
// Mock SAAS_URL correctly inside the script
const mockCode = widgetCode.replace(/const SAAS_DOMAIN = .*;/g, 'const SAAS_DOMAIN = "https://api-chatbot.futurecloud.id";');

const script = window.document.createElement('script');
script.textContent = mockCode;
window.document.body.appendChild(script);

setTimeout(() => {
    console.log("Component initialized.");
    const comp = window.ChatbotWidget();
    console.log(comp);
}, 1000);
