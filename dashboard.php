<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
  <title>MiniChat - <?= htmlspecialchars($username) ?></title>
  <style>
    /* Basic reset & styles */
    * {
      box-sizing: border-box;
    }

    body,
    html {
      margin: 0;
      height: 100%;
      font-family: Arial, sans-serif;
    }

    #app {
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    header {
      padding: 10px;
      background: #007bff;
      color: white;
      font-weight: bold;
      font-size: 1.25em;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    main {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    #chat-list {
      width: 35%;
      max-width: 300px;
      border-right: 1px solid #ccc;
      overflow-y: auto;
    }

    #chat-list input {
      width: 90%;
      margin: 10px 5%;
      padding: 8px;
      font-size: 1em;
    }

    #chat-list ul {
      list-style: none;
      padding: 0;
      margin: 0 0 10px 0;
    }

    #chat-list li {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      cursor: pointer;
    }

    #chat-list li.active {
      background: #007bff;
      color: white;
    }

    #chat-box {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    #messages {
      flex: 1;
      padding: 10px;
      overflow-y: auto;
      border-bottom: 1px solid #ccc;
    }

    .message {
      margin-bottom: 10px;
      max-width: 70%;
      padding: 8px 12px;
      border-radius: 12px;
      clear: both;
      word-wrap: break-word;
      white-space: pre-wrap;
    }

    .message.you {
      background: #dcf8c6;
      float: right;
      text-align: right;
    }

    .message.them {
      background: #eee;
      float: left;
      text-align: left;
    }

    #send-form {
      display: flex;
      padding: 10px;
    }

    #send-form input {
      flex: 1;
      padding: 8px;
      font-size: 1em;
    }

    #send-form button {
      padding: 8px 16px;
      font-size: 1em;
    }

    @media(max-width: 700px) {
      main {
        flex-direction: column;
      }

      #chat-list {
        width: 100%;
        max-width: none;
        height: 150px;
        border-right: none;
        border-bottom: 1px solid #ccc;
      }

      #chat-list ul {
        display: flex;
        overflow-x: auto;
      }

      #chat-list li {
        flex: 0 0 auto;
        border-bottom: none;
        border-right: 1px solid #ddd;
        padding: 10px 15px;
      }

      #chat-box {
        height: calc(100vh - 210px);
      }

      #messages {
        height: 100%;
      }
    }
  </style>
</head>

<body>
  <div id="app">
    <header>
      <div>Logged in as <strong><?= htmlspecialchars($username) ?></strong></div>
      <a href="logout.php" style="color:white;">Logout</a>
    </header>
    <main>
      <div id="chat-list">
        <input type="text" id="target-username" placeholder="Enter username to chat" autocomplete="off" />
        <ul id="chat-users"></ul>
      </div>
      <div id="chat-box">
        <div id="messages">
          <p style="padding:10px;color:#666;">Select or enter a username to start chatting</p>
        </div>
        <form id="send-form">
          <input type="text" id="message-input" autocomplete="off" placeholder="Type a message..." />
          <button type="submit">Send</button>
        </form>
      </div>
    </main>
  </div>

  <script>
    const userId = <?= json_encode($user_id) ?>;
    const myUsername = <?= json_encode($username) ?>;

    let selectedUser = null;
    let myKeys = null;

    // Utility: UTF8 encoder/decoder
    const enc = new TextEncoder();
    const dec = new TextDecoder();

    async function loadOrGenerateKeys() {
      const saved = localStorage.getItem('myKeyPair');
      if (saved) {
        try {
          const {
            privateKeyJwk,
            publicKeyJwk
          } = JSON.parse(saved);
          const privateKey = await crypto.subtle.importKey(
            "jwk", privateKeyJwk, {
              name: "RSA-OAEP",
              hash: "SHA-256"
            },
            true, ["decrypt"]
          );
          const publicKey = await crypto.subtle.importKey(
            "jwk", publicKeyJwk, {
              name: "RSA-OAEP",
              hash: "SHA-256"
            },
            true, ["encrypt"]
          );
          myKeys = {
            privateKey,
            publicKey
          };
          return;
        } catch (e) {
          console.error('Failed to import keys:', e);
          localStorage.removeItem('myKeyPair');
        }
      }

      // Generate keys
      const keyPair = await crypto.subtle.generateKey({
          name: "RSA-OAEP",
          modulusLength: 2048,
          publicExponent: new Uint8Array([1, 0, 1]),
          hash: "SHA-256"
        },
        true,
        ["encrypt", "decrypt"]
      );
      const privateKeyJwk = await crypto.subtle.exportKey("jwk", keyPair.privateKey);
      const publicKeyJwk = await crypto.subtle.exportKey("jwk", keyPair.publicKey);
      localStorage.setItem("myKeyPair", JSON.stringify({
        privateKeyJwk,
        publicKeyJwk
      }));
      myKeys = keyPair;

      // Send public key to server
      try {
        const res = await fetch('save_key.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            publicKey: publicKeyJwk
          })
        });
        if (!res.ok) {
          console.error('Failed to save public key');
        }
      } catch (e) {
        console.error('Error saving public key:', e);
      }
    }

    async function fetchChatUsers() {
      try {
        const res = await fetch('fetch_chats.php');
        if (!res.ok) throw new Error('Failed to fetch chat users');
        const users = await res.json();
        return users;
      } catch (e) {
        console.error(e);
        return [];
      }
    }

    async function renderChatUsers() {
      const users = await fetchChatUsers();
      chatUsersEl.innerHTML = '';
      users.forEach(user => {
        const li = document.createElement('li');
        li.textContent = user.username;
        li.dataset.username = user.username;
        if (selectedUser === user.username) li.classList.add('active');
        li.onclick = () => {
          selectUser(user.username);
        };
        chatUsersEl.appendChild(li);
      });
    }

    async function fetchMessagesForUser(targetUsername) {
      try {
        const res = await fetch(`fetch_messages.php?target=${encodeURIComponent(targetUsername)}`);
        if (!res.ok) throw new Error('Failed to fetch messages');
        const messages = await res.json();
        return messages;
      } catch (e) {
        console.error(e);
        return [];
      }
    }

    async function getPublicKeyForUser(username) {
      try {
        const res = await fetch(`get_public_key.php?username=${encodeURIComponent(username)}`);
        if (!res.ok) throw new Error('Public key not found');
        const json = await res.json();
        if (!json.publicKey) throw new Error('Public key missing');
        return JSON.parse(json.publicKey);
      } catch (e) {
        console.error('Error fetching public key:', e);
        throw e;
      }
    }

    async function importPublicKey(jwk) {
      return crypto.subtle.importKey(
        "jwk",
        jwk, {
          name: "RSA-OAEP",
          hash: "SHA-256"
        },
        true,
        ["encrypt"]
      );
    }

    async function encryptMessage(message, targetUsername) {
      try {
        const publicKeyJwk = await getPublicKeyForUser(targetUsername);
        const publicKey = await importPublicKey(publicKeyJwk);
        const encoded = enc.encode(message);
        const ciphertext = await crypto.subtle.encrypt({
          name: "RSA-OAEP"
        }, publicKey, encoded);
        return btoa(String.fromCharCode(...new Uint8Array(ciphertext)));
      } catch (e) {
        alert(`Cannot send message: Public key for user "${targetUsername}" not found. Ask them to login first.`);
        throw e;
      }
    }

    async function decryptMessage(base64Ciphertext) {
      const binaryString = atob(base64Ciphertext);
      const len = binaryString.length;
      const bytes = new Uint8Array(len);
      for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
      }
      try {
        const decrypted = await crypto.subtle.decrypt({
          name: "RSA-OAEP"
        }, myKeys.privateKey, bytes);
        return dec.decode(decrypted);
      } catch (e) {
        console.warn('Decryption failed:', e);
        return "[Failed to decrypt message]";
      }
    }

    function selectUser(username) {
      selectedUser = username;
      // Highlight
      [...chatUsersEl.children].forEach(li => {
        li.classList.toggle('active', li.dataset.username === username);
      });
      targetInput.value = username;
      loadMessages();
    }

    async function loadMessages() {
      if (!selectedUser) {
        messagesEl.innerHTML = '<p style="padding:10px;color:#666;">Select or enter a username to start chatting</p>';
        return;
      }
      const messages = await fetchMessagesForUser(selectedUser);
      messagesEl.innerHTML = '';
      for (const m of messages) {
        const text = await decryptMessage(m.message);
        const div = document.createElement('div');
        div.className = 'message ' + (m.sender === myUsername ? 'you' : 'them');
        div.textContent = text;
        messagesEl.appendChild(div);
      }
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function sendMessage(message) {
      try {
        const encrypted = await encryptMessage(message, selectedUser);
        const res = await fetch('send_message.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            target: selectedUser,
            message: encrypted
          })
        });
        if (!res.ok) {
          const text = await res.text();
          alert('Failed to send message: ' + text);
          return false;
        }
        const json = await res.json();
        if (json.status !== 'ok') {
          alert('Failed to send message: ' + (json.error || 'Unknown error'));
          return false;
        }
        return true;
      } catch (e) {
        alert('Send message error: ' + e.message);
        return false;
      }
    }

    // DOM Elements
    const chatUsersEl = document.getElementById('chat-users');
    const messagesEl = document.getElementById('messages');
    const targetInput = document.getElementById('target-username');
    const sendForm = document.getElementById('send-form');
    const messageInput = document.getElementById('message-input');

    sendForm.onsubmit = async e => {
      e.preventDefault();
      const message = messageInput.value.trim();
      if (!message) return;
      if (!selectedUser) {
        alert('Select or enter a username to chat with first!');
        return;
      }
      sendForm.querySelector('button').disabled = true;
      const success = await sendMessage(message);
      sendForm.querySelector('button').disabled = false;
      if (success) {
        messageInput.value = '';
        loadMessages();
        renderChatUsers();
      }
    };

    targetInput.onchange = () => {
      const val = targetInput.value.trim();
      if (val && val !== selectedUser) {
        selectUser(val);
        renderChatUsers();
      }
    };

    // Periodic refresh
    setInterval(() => {
      if (selectedUser) loadMessages();
      renderChatUsers();
    }, 3000);

    // Initialize
    (async () => {
      await loadOrGenerateKeys();
      await renderChatUsers();
    })();
  </script>

</body>

</html>