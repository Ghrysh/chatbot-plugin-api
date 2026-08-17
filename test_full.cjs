const http = require('https');

function request(method, path, body = null) {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'api-chatbot.futurecloud.id',
            port: 443,
            path: path,
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        const req = http.request(options, res => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        });
        req.on('error', reject);
        if (body) req.write(JSON.stringify(body));
        req.end();
    });
}

async function run() {
    console.log("1. Simulating Widget sending first message (User -> Bot)");
    let res1 = await request('POST', '/api/chatbot/send', {
        license: 'FC-LIC-0055-8BLA8O',
        message: 'HALO INI TEST AUTO',
        chat_history: []
    });
    console.log(res1);
    let leadId = res1.lead_id;
    console.log("Lead ID created:", leadId);

    // Wait 2 seconds
    await new Promise(r => setTimeout(r, 2000));

    // Admin taking over is done manually via dashboard. 
    // We can't easily simulate admin auth here, but we can simulate the widget polling.
    console.log("2. Simulating Widget polling...");
    let res2 = await request('GET', `/api/chatbot/live/poll/${leadId}?t=${Date.now()}`);
    console.log(JSON.stringify(res2, null, 2));

    console.log("3. Simulating User sending message DURING LIVE CHAT...");
    let res3 = await request('POST', '/api/chatbot/live/send', {
        lead_id: leadId,
        message: 'HALO DARI USER SAAT LIVE'
    });
    console.log(res3);

    console.log("4. Simulating Widget polling again...");
    let res4 = await request('GET', `/api/chatbot/live/poll/${leadId}?t=${Date.now()}`);
    console.log(JSON.stringify(res4, null, 2));
}

run();
