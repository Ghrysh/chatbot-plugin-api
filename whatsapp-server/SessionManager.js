/**
 * SessionManager.js
 * Manages multiple WhatsApp sessions (one per client_id).
 * Each session runs its own whatsapp-web.js Client instance.
 */

const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const axios = require('axios');
const fs = require('fs');
const path = require('path');

class SessionManager {
    constructor(config) {
        this.sessions = new Map(); // clientId -> { client, status, qr, info }
        this.laravelApiUrl = config.laravelApiUrl || 'http://localhost:8081';
        this.webhookSecret = config.webhookSecret || '';
        this.puppeteerArgs = config.puppeteerArgs || [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ];
    }

    /**
     * Start or restart a session for a given client.
     */
    async startSession(clientId) {
        clientId = String(clientId);
        // If session already exists, destroy it first
        if (this.sessions.has(clientId)) {
            await this.destroySession(clientId);
        }

        const sessionData = {
            client: null,
            status: 'initializing', // initializing | qr | ready | disconnected | error
            qr: null,
            qrDataUrl: null,
            info: null
        };

        this.sessions.set(clientId, sessionData);

        const client = new Client({
            authStrategy: new LocalAuth({ clientId: `client_${clientId}` }),
            puppeteer: {
                headless: true,
                args: this.puppeteerArgs
            }
        });

        sessionData.client = client;

        // QR Code event
        client.on('qr', async (qr) => {
            console.log(`[Client ${clientId}] QR received`);
            sessionData.status = 'qr';
            sessionData.qr = qr;
            try {
                sessionData.qrDataUrl = await qrcode.toDataURL(qr, { width: 300 });
            } catch (err) {
                console.error(`[Client ${clientId}] QR generation error:`, err.message);
            }
        });

        // Ready event
        client.on('ready', async () => {
            console.log(`[Client ${clientId}] WhatsApp ready!`);
            sessionData.status = 'ready';
            sessionData.qr = null;
            sessionData.qrDataUrl = null;
            sessionData.info = client.info;

            // Notify Laravel that this client is connected
            await this._notifyLaravel(clientId, 'connected', {
                phone: client.info?.wid?.user || null,
                name: client.info?.pushname || null
            });
        });

        // Disconnected event
        client.on('disconnected', async (reason) => {
            console.log(`[Client ${clientId}] Disconnected:`, reason);
            sessionData.status = 'disconnected';
            sessionData.info = null;

            await this._notifyLaravel(clientId, 'disconnected', { reason });
        });

        // Auth failure
        client.on('auth_failure', async (msg) => {
            console.error(`[Client ${clientId}] Auth failure:`, msg);
            sessionData.status = 'error';
        });

        // Incoming message
        client.on('message', async (msg) => {
            // Abaikan pesan dari grup, channel (newsletter), atau status
            if (msg.from === 'status@broadcast' || msg.from.includes('@g.us') || msg.from.includes('@newsletter')) {
                return;
            }

            // Skip messages from self
            if (msg.fromMe) return;

            console.log(`[Client ${clientId}] Message from ${msg.from}: ${msg.body}`);

            try {
                const response = await axios.post(`${this.laravelApiUrl}/api/whatsapp/incoming`, {
                    client_id: clientId,
                    from: msg.from,
                    sender_name: msg._data?.notifyName || '',
                    message: msg.body,
                    timestamp: msg.timestamp,
                    webhook_secret: this.webhookSecret
                }, {
                    timeout: 45000  // 45s timeout for AI processing
                });

                if (response.data && response.data.reply) {
                    await msg.reply(response.data.reply);
                    console.log(`[Client ${clientId}] Replied to ${msg.from}`);
                }
            } catch (err) {
                console.error(`[Client ${clientId}] Error processing message:`, err.message);
                // Send fallback message
                try {
                    await msg.reply('Maaf, sistem sedang mengalami gangguan. Silakan coba lagi nanti.');
                } catch (replyErr) {
                    console.error(`[Client ${clientId}] Failed to send fallback:`, replyErr.message);
                }
            }
        });

        // Initialize
        try {
            await client.initialize();
        } catch (err) {
            console.error(`[Client ${clientId}] Initialize error:`, err.message);
            sessionData.status = 'error';
        }

        return sessionData;
    }

    /**
     * Get session status for a client.
     */
    getSession(clientId) {
        return this.sessions.get(clientId) || null;
    }

    /**
     * Get session status info (safe to send to frontend).
     */
    getSessionStatus(clientId) {
        clientId = String(clientId);
        const session = this.sessions.get(clientId);
        if (!session) {
            return { status: 'not_started', qr: null, info: null };
        }
        return {
            status: session.status,
            qrDataUrl: session.qrDataUrl || null,
            info: session.info ? {
                phone: session.info.wid?.user || null,
                name: session.info.pushname || null,
                platform: session.info.platform || null
            } : null
        };
    }

    /**
     * Stop and destroy a session completely
     */
    async destroySession(clientId) {
        clientId = String(clientId);
        const sessionData = this.sessions.get(clientId);
        if (sessionData && sessionData.client) {
            try {
                await sessionData.client.destroy();
            } catch (e) {
                console.error(`[Client ${clientId}] Destroy error:`, e.message);
            }
        }
        this.sessions.delete(clientId);
        console.log(`[Client ${clientId}] Session destroyed`);
    }

    /**
     * Stop session gracefully (logout)
     */
    async stopSession(clientId) {
        clientId = String(clientId);
        const sessionData = this.sessions.get(clientId);
        if (sessionData && sessionData.client) {
            try {
                await sessionData.client.logout();
                await sessionData.client.destroy();
            } catch (err) {
                console.error(`[Client ${clientId}] Logout/Destroy error:`, err.message);
            }
        }
        this.sessions.delete(clientId);
        
        // Ensure folder is permanently deleted so next connect requires QR
        const authPath = path.join(process.cwd(), '.wwebjs_auth', `session-client_${clientId}`);
        if (fs.existsSync(authPath)) {
            try {
                fs.rmSync(authPath, { recursive: true, force: true });
            } catch (e) {
                console.error(`[Client ${clientId}] Error deleting session folder:`, e.message);
            }
        }
        
        console.log(`[Client ${clientId}] Session destroyed`);

        await this._notifyLaravel(clientId, 'disconnected', { reason: 'manual_disconnect' });
    }

    /**
     * Notify Laravel about connection status changes.
     */
    async _notifyLaravel(clientId, event, data = {}) {
        try {
            await axios.post(`${this.laravelApiUrl}/api/whatsapp/status`, {
                client_id: clientId,
                event: event,
                data: data,
                webhook_secret: this.webhookSecret
            });
        } catch (err) {
            console.error(`[Client ${clientId}] Failed to notify Laravel:`, err.message);
        }
    }

    /**
     * Get all active sessions summary.
     */
    getAllSessions() {
        const result = {};
        for (const [clientId, session] of this.sessions) {
            result[clientId] = {
                status: session.status,
                phone: session.info?.wid?.user || null,
                name: session.info?.pushname || null
            };
        }
        return result;
    }
}

module.exports = SessionManager;
