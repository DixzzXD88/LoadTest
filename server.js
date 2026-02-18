// ============================================

// FILE: server.js - WEB MONITOR DDOS

// RUN: node server.js

// PORT: 1234 (default)

// ============================================

const express = require('express');

const http = require('http');

const socketIo = require('socket.io');

const path = require('path');

const app = express();

const server = http.createServer(app);

const io = socketIo(server);

// Database sederhana (pake memory)

let stats = {

    totalRequests: 0,

    uniqueIPs: new Set(),

    requestsPerSecond: 0,

    history: [],

    startTime: Date.now()

};

// Hitung requests per detik

let lastSecond = Date.now();

let requestsThisSecond = 0;

setInterval(() => {

    const now = Date.now();

    if (now - lastSecond >= 1000) {

        stats.requestsPerSecond = requestsThisSecond;

        stats.history.push({

            time: new Date().toLocaleTimeString(),

            count: requestsThisSecond

        });

        

        // Keep last 60 data points (1 menit)

        if (stats.history.length > 60) {

            stats.history.shift();

        }

        

        requestsThisSecond = 0;

        lastSecond = now;

        

        // Broadcast update ke semua client

        io.emit('stats', {

            total: stats.totalRequests,

            uniqueIPs: stats.uniqueIPs.size,

            rps: stats.requestsPerSecond,

            history: stats.history,

            uptime: Math.floor((Date.now() - stats.startTime) / 1000)

        });

    }

}, 100);

// Middleware

app.use(express.static(path.join(__dirname, 'public')));

app.use(express.json());

// Endpoint buat nerima request (target DDOS)

app.get('/attack', (req, res) => {

    const clientIP = req.headers['x-forwarded-for'] || req.socket.remoteAddress;

    

    // Update stats

    stats.totalRequests++;

    stats.uniqueIPs.add(clientIP);

    requestsThisSecond++;

    

    // Kirim response minimal

    res.status(200).send('OK');

});

// Endpoint buat dashboard

app.get('/stats', (req, res) => {

    res.json({

        total: stats.totalRequests,

        uniqueIPs: stats.uniqueIPs.size,

        rps: stats.requestsPerSecond,

        uptime: Math.floor((Date.now() - stats.startTime) / 1000)

    });

});

// Reset stats

app.post('/reset', (req, res) => {

    stats = {

        totalRequests: 0,

        uniqueIPs: new Set(),

        requestsPerSecond: 0,

        history: [],

        startTime: Date.now()

    };

    lastSecond = Date.now();

    requestsThisSecond = 0;

    res.json({ status: 'reset' });

});

// Jalankan server

const PORT = process.env.PORT || 3000;

server.listen(PORT, '0.0.0.0', () => {

    console.log(`

╔════════════════════════════════╗

║   WEB MONITOR DDOS AKTIF!      ║

║   http://localhost:${PORT}        ║

║   Target URL:                  ║

║   http://IP:${PORT}/attack       ║

╚════════════════════════════════╝

    `);

});