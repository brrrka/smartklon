import { WebSocket } from 'ws';

const ws = new WebSocket('ws://127.0.0.1:8080/app/smartklon-key-local?protocol=7&client=js&version=8.5.0&flash=false');

ws.on('open', function open() {
  console.log('Connected to WS');
  ws.send(JSON.stringify({
    event: 'pusher:subscribe',
    data: { auth: "", channel: 'rfid-scanner' }
  }));
});

ws.on('message', function incoming(data) {
  console.log('Received:', data.toString());
});
