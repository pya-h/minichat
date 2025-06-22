## 🛡️ MiniChat

* A lightweight, secure, responsive chat application built with native PHP and vanilla JavaScript, featuring client-side RSA end‑to‑end encryption and support for text, voice, and image messages.
* Native PHP/Html/JS/Css Web App with no use of external packages/modules, runnable even on Limitted hosts/servers.
* This is designed for hard days when IR limits global internet access for iranians.
---

## 🚀 Features

- **Secure**: Client-side generation & import of RSA key-pairs; all text messages encrypted end‑to‑end.
- **Media Support**: Send voice messages and images (optional encryption).
- **Modern UI**: Responsive Bootstrap-based design with polished animations.
- **Real-time Chat**: Dynamic chat list and message loading with intelligent polling.
- **Multi-format Messages**: Supports text, audio, images, and includes waveforms for voice playback.
- **Authentication**: Simple username/password flow; new users auto-registered.
- **Peer-to-Peer Encryption**: Only public keys (text) are shared; private keys remain client-side.

---

## 🧰 Tech Stack

- **Backend**: PHP 7.4+, PDO + MySQL
- **Frontend**: Bootstrap 5, Web Crypto API (RSA-OAEP)
- **Media Handling**: Web Audio API, Recorder API
- **File Storage**: Voice/image message uploads (e.g., `send_voice_message.php`, `send_image_message.php`)
- **Client-Side Storage**: Private key stored in memory/session

---

## 📂 Project Structure

```

minichat/
├─ api/
│   ├─ login.php
│   ├─ logout.php
│   ├─ get\_public\_key.php
│   ├─ get\_private\_key.php
│   ├─ send\_message.php
│   ├─ fetch\_messages.php
│   ├─ fetch\_chats.php
│   ├─ send\_voice\_message.php
│   ├─ get\_voice\_message.php
│   ├─ send\_image\_message.php
│   └─ get\_image.php
├─ includes/
│   ├─ db.php
│   └─ crypto\_helper.php
├─ assets/
│   ├─ css/bootstrap.min.css
│   └─ js/
│       ├─ bootstrap.bundle.min.js
│       ├─ crypto.js
│       └─ chat.js
├─ index.php
├─ dashboard.php
└─ .env

```

---

## 📝 Setup Guide

1. **Clone or download** the repo.
2. Create a `.env` in the project root (same folder as `index.php`):
```

DB\_HOST=localhost
DB\_NAME=minichat
DB\_USER=root
DB\_PASS=your\_password

````
3. **Initialize database**:

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  public_key TEXT NOT NULL,
  private_key TEXT NOT NULL
);
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  message TEXT,
  message_for_sender TEXT,
  message_type ENUM('text','voice','image') NOT NULL DEFAULT 'text',
  voice_file_path VARCHAR(255),
  image_file_path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id)
);
````

4. Place project in your PHP 7.4+ server (e.g., Apache or PHP built-in).
5. Visit `index.php` to log in or register.
6. Use **Dashboard UI** to select chat partners, send encrypted text, upload images, and record voice messages.

---

## 🔐 Encryption Flow

* Upon login/register:

  1. PHP generates RSA key pair (2048 bits).
  2. Stores public + private PEM strings in DB.
  3. Returns private key PEM to client (TLS-secure).
* In browser:

  * Public keys are fetched + imported for encryption.
  * Private key is imported once and kept in memory.
* Sending:

  * Message is encrypted with both sender and recipient public keys.
* Receiving:

  * Messages are decrypted with private key, preserving confidentiality.

---

## 🎨 UI & UX Features

* **Chat List**: Shows avatar initials, live spinner during load.
* **Message Bubbles**: Animated fade-in, sender/receiver differentiation, RTL support for Arabic.
* **Voice Messages**: Waveform animation while playing, play/pause toggle.
* **Image Messages**: Auto-scaling with lazy load and fallback handling.
* **Mobile Friendly**: Responsive design, swipe/draggable if needed, elegant input controls.

---

## 🛠️ Optional Enhancements

* 🔒 Encrypt voice/image files for consistent E2EE.
* 📦 Store private key encrypted with user password or browser's secure storage.
* 📡 Use WebSocket or SSE for real real‑time updates.
* 🖼️ Add chat avatars or static colors per user.
* 📊 Add typing indicators, message timestamps, read receipts.
* 🧩 Allow message editing, deletion, or reactions with emoji.
* 🔄 Infinite scroll for chat history.

---

## 📱 Deployment Tips

* Use HTTPS to secure key transmission (required by Web Crypto API).
* Set PHP `upload_max_filesize` and `post_max_size` to support 5MB+ media uploads.
* For production, disable `.env` parsing on public directory or secure it.
* If hosting on shared hosting, ensure file permissions are correct and outgoing access to Web APIs is allowed.

---

## 🧪 FAQ

**Q: Are voice/images encrypted end-to-end?**
**A:** Text messages are always encrypted. Voice/images are optionally encrypted with the same RSA workflow or can be sent unencrypted based on your desired implementation.

**Q: Isn't sending private keys over TLS risky?**
**A:** It introduces some risk. For higher security, consider client-side generation or encrypting private PEM with a passphrase in local secure storage.

**Q: How big of a chat history can it handle?**
**A:** Load is limited by browser memory and server response time. For larger history, implement pagination or infinite-scroll.

---

## 🧭 Support & Contributions

* 🧠 Found a bug? Open an issue.
* ✨ Want a feature? Send a PR.
* 📦 Use, learn, and enjoy!