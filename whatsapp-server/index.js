/**
 * WhatsApp Server - Express API
 * Bridge between whatsapp-web.js and Laravel Chatbot Plugin API.
 * 
 * Endpoints:
 *   POST /session/start    - Start a WA session for a client (returns QR or status)
 *   GET  /session/status   - Get current session status + QR
 *   POST /session/stop     - Disconnect and destroy a session
 *   GET  /sessions         - List all active sessions
 *   GET  /health           - Health check
 */

const express = require('express');
const cors = require('cors');
const SessionManager = require('./SessionManager');

const app = express();
const PORT = process.env.WA_SERVER_PORT || 3100;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8081';
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'futurecloud-wa-secret';
const API_KEY = process.env.WA_API_KEY || 'futurecloud-wa-api-key';

// Middleware
app.use(cors());
app.use(express.json());

// Simple API key auth middleware
function authMiddleware(req, res, next) {
    const apiKey = req.headers['x-wa-api-key'] || req.query.api_key;
    if (apiKey !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    next();
}

// Initialize Session Manager
const manager = new SessionManager({
    laravelApiUrl: LARAVEL_API_URL,
    webhookSecret: WEBHOOK_SECRET
});

// ============================================================
// ROUTES
// ============================================================

/**
 * POST /session/start
 * Body: { client_id: number }
 * Start or restart a WhatsApp session for the given client.
 */
app.post('/session/start', authMiddleware, async (req, res) => {
    const { client_id } = req.body;
    if (!client_id) {
        return res.status(400).json({ error: 'client_id is required' });
    }

    try {
        console.log(`Starting session for client ${client_id}...`);
        // Start the session (non-blocking, QR will come via polling)
        manager.startSession(client_id);

        // Wait a bit for QR to generate (up to 15s)
        let attempts = 0;
        const maxAttempts = 30;
        while (attempts < maxAttempts) {
            await new Promise(resolve => setTimeout(resolve, 500));
            const status = manager.getSessionStatus(client_id);
            if (status.status === 'qr' || status.status === 'ready') {
                return res.json(status);
            }
            if (status.status === 'error') {
                return res.status(500).json({ error: 'Failed to initialize WhatsApp session', status: status.status });
            }
            attempts++;
        }

        // Timeout waiting for QR
        const finalStatus = manager.getSessionStatus(client_id);
        return res.json(finalStatus);
    } catch (err) {
        console.error('Start session error:', err);
        return res.status(500).json({ error: err.message });
    }
});

/**
 * GET /session/status?client_id=X
 * Get current session status (qr, ready, disconnected, etc.)
 */
app.get('/session/status', authMiddleware, (req, res) => {
    const { client_id } = req.query;
    if (!client_id) {
        return res.status(400).json({ error: 'client_id is required' });
    }

    const status = manager.getSessionStatus(client_id);
    return res.json(status);
});

/**
 * POST /session/stop
 * Body: { client_id: number }
 * Disconnect and destroy a session.
 */
app.post('/session/stop', authMiddleware, async (req, res) => {
    const { client_id } = req.body;
    if (!client_id) {
        return res.status(400).json({ error: 'client_id is required' });
    }

    try {
        await manager.destroySession(client_id);
        return res.json({ success: true, message: 'Session disconnected' });
    } catch (err) {
        console.error('Stop session error:', err);
        return res.status(500).json({ error: err.message });
    }
});

/**
 * GET /sessions
 * List all active sessions.
 */
app.get('/sessions', authMiddleware, (req, res) => {
    return res.json(manager.getAllSessions());
});

/**
 * GET /health
 * Health check (no auth required).
 */
app.get('/health', (req, res) => {
    return res.json({
        status: 'ok',
        uptime: process.uptime(),
        activeSessions: manager.sessions.size
    });
});

// ============================================================
// START SERVER & AUTO RESTORE
// ============================================================

const fs = require('fs');
const path = require('path');

app.listen(PORT, () => {
    console.log(`🟢 WhatsApp Server running on port ${PORT}`);
    console.log(`   Laravel API: ${LARAVEL_API_URL}`);
    console.log(`   Active sessions: ${manager.sessions.size}`);

    // Auto-restore existing sessions
    const authDir = path.join(__dirname, '.wwebjs_auth');
    if (fs.existsSync(authDir)) {
        const folders = fs.readdirSync(authDir);
        for (const folder of folders) {
            if (folder.startsWith('session-client_')) {
                const clientId = folder.replace('session-client_', '');
                console.log(`Auto-restoring session for client ${clientId} from disk...`);
                manager.startSession(clientId);
            }
        }
    }
});
