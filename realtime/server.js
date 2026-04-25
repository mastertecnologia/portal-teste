/**
 * Socket.io fan-out for ticket message relay.
 * Set SECURITY_SALT to the same value as config/app.php Security.salt.
 * Listen: PGM_SERVICE_DESK_SOCKET_PORT (default 3331).
 */
import { createServer } from 'http';
import crypto from 'crypto';
import { Server } from 'socket.io';

const salt = process.env.SECURITY_SALT || '';
const port = parseInt(process.env.PGM_SERVICE_DESK_SOCKET_PORT || '3331', 10);

function verifyToken(raw) {
  if (!raw || typeof raw !== 'string' || !salt) {
    return null;
  }
  const i = raw.lastIndexOf('.');
  if (i < 0) {
    return null;
  }
  const payloadB64 = raw.slice(0, i);
  const sig = raw.slice(i + 1);
  const h = crypto.createHmac('sha256', salt).update(payloadB64).digest('hex');
  if (h.length !== sig.length) {
    return null;
  }
  if (!crypto.timingSafeEqual(Buffer.from(h, 'utf8'), Buffer.from(String(sig), 'utf8'))) {
    return null;
  }
  let j;
  try {
    j = JSON.parse(Buffer.from(payloadB64, 'base64').toString('utf8'));
  } catch {
    return null;
  }
  if (!j || typeof j.exp !== 'number' || j.exp < Date.now() / 1000) {
    return null;
  }
  return j;
}

const http = createServer();
const io = new Server(http, {
  cors: { origin: true, methods: ['GET', 'POST'] },
});

io.use((socket, next) => {
  const t = socket.handshake.auth?.token || socket.handshake.query?.token;
  const v = verifyToken(t);
  if (!v) {
    return next(new Error('unauthorized'));
  }
  socket.data.claims = v;
  return next();
});

io.on('connection', (socket) => {
  const claims = socket.data.claims;

  socket.on('join_ticket', (p) => {
    const tid = p?.ticketId;
    if (String(tid) !== String(claims.tid)) {
      return;
    }
    socket.join(`ticket_${tid}`);
  });

  socket.on('ticket_message_relay', (p) => {
    if (String(p?.ticketId) !== String(claims.tid)) {
      return;
    }
    io.to(`ticket_${claims.tid}`).emit('ticket_message', p);
  });
});

http.listen(port, () => {
  // eslint-disable-next-line no-console
  console.log(`[pgm-sd-socket] listening on ${port}`);
});
