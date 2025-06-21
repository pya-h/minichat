const Chat = (() => {
  const userListEl = document.getElementById("chat-users");
  const messageInput = document.getElementById("message-input");
  const chatBoxEl = document.getElementById("chat-box");
  const sendForm = document.getElementById("send-form");
  const targetInput = document.getElementById("target-input");
  const noChatMsg = document.getElementById("no-chat-msg");

  let selectedUser = null;
  let messages = [];

  async function fetchChats() {
    const res = await fetch("api/fetch_chats.php");
    const users = await res.json();
    userListEl.innerHTML = "";
    users.forEach((user) => {
      const li = document.createElement("li");
      li.className = "list-group-item list-group-item-action";
      li.textContent = user.username;
      li.onclick = () => selectUser(user.username);
      if (user.username === selectedUser) li.classList.add("active");
      userListEl.appendChild(li);
    });
  }

  async function selectUser(username) {
    selectedUser = username;
    targetInput.value = username;
    await fetchChats(); // re-highlight
    loadMessages();
  }

  async function loadMessages() {
    if (!selectedUser) return;
    const res = await fetch(
      `api/fetch_messages.php?target=${encodeURIComponent(selectedUser)}`
    );
    const data = await res.json();

    chatBoxEl.innerHTML = "";
    if (data.length === 0) {
      chatBoxEl.innerHTML = `<p class="text-muted">🕊️ No messages yet</p>`;
      return;
    }

    for (const msg of data) {
      const div = document.createElement("div");
      div.className = `alert ${
        msg.sender === CURRENT_USER
          ? "alert-primary text-end ms-auto"
          : "alert-secondary text-start me-auto"
      } fade show mb-2`;
      div.style.maxWidth = "75%";
      div.setAttribute("role", "alert");

      const decrypted = await Crypto.decrypt(msg.encrypted_message);
      div.textContent = decrypted;
      chatBoxEl.appendChild(div);
    }
    chatBoxEl.scrollTop = chatBoxEl.scrollHeight;
  }

  async function sendMessage(message) {
    if (!selectedUser || !message) return;
    try {
      const encryptedForRecipient = await Crypto.encrypt(
        message,
        selectedUser
      );
      const encryptedForSender = await Crypto.encrypt(message, CURRENT_USER);

      const res = await fetch("api/send_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          target: selectedUser,
          message: encryptedForRecipient,
          message_for_sender: encryptedForSender,
        }),
      });

      const result = await res.json();
      if (result.status === "ok") {
        messageInput.value = "";
        await loadMessages();
        await fetchChats();
      } else {
        alert(result.error || "Failed to send message");
      }
    } catch (err) {
      alert("Encryption/send error: " + err.message);
    }
  }

  sendForm.onsubmit = (e) => {
    e.preventDefault();
    const text = messageInput.value.trim();
    if (!text || !selectedUser) return;
    sendMessage(text);
  };

  targetInput.onchange = () => {
    const target = targetInput.value.trim();
    if (target && target !== selectedUser) {
      selectUser(target);
    }
  };

  return {
    init: () => {
      fetchChats();
      setInterval(() => {
        fetchChats();
        if (selectedUser) loadMessages();
      }, 3000);
    },
  };
})();
