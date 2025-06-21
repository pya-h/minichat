const chatListElem = document.getElementById("chatList");
const chatMessagesElem = document.getElementById("chatMessages");
const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatWithElem = document.getElementById("chatWith");
const searchUserInput = document.getElementById("searchUser");

let currentChatUser = null;
let recentMessage = null;
const chatUsers = new Set();

function addUserToChatList(username) {
  if (chatUsers.has(username) || username === CURRENT_USER) return;
  chatUsers.add(username);

  const li = document.createElement("li");
  li.tabIndex = 0; // for keyboard accessibility

  // Generate initials from username
  const initials = username
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase();

  li.innerHTML = `<span class="avatar">${initials}</span> <span>${username}</span><span id='user_${username}_loading' style="display:none" class="spinner-border spinner-border-sm text-primary ms-2" role="status" aria-hidden="true"></span>`;
  li.classList.add("chat-user");
  li.addEventListener("click", () => selectChatUser(username));
  chatListElem.appendChild(li);
}

function updateLoadingSpinnerState(username, show = false) {
  const loadingSpinnerElement = document.getElementById(
    `user_${username}_loading`
  );
  loadingSpinnerElement.style = `display: ${show ? "inline" : "none"}`;
}

function selectChatUser(username) {
  recentMessage = null;
  if (currentChatUser?.length) {
    updateLoadingSpinnerState(currentChatUser, false);
  }
  currentChatUser = username;
  chatWithElem.textContent = `Chat with ${username}`;
  chatInput.disabled = false;
  chatInput.value = "";
  chatMessagesElem.innerHTML = "";

  [...chatListElem.children].forEach((li) => {
    li.classList.toggle("active", li.textContent === username);
  });

  loadMessages(username, true);
}

async function loadMessages(username, showLoading = false) {
  try {
    const loadingSpinnerElement = document.getElementById(
      `user_${username}_loading`
    );
    if (showLoading) {
      loadingSpinnerElement.style = "display: inline";
    }
    const res = await fetch(
      `api/fetch_messages.php?with=${encodeURIComponent(username)}`
    );
    if (!res.ok) throw new Error("Failed to load messages");
    const data = await res.json();
    if (!data.messages.length) {
      chatMessagesElem.innerHTML = "";
      chatMessagesElem.textContent = "No messages yet.";
      loadingSpinnerElement.style = "display: none";
      return;
    }

    if (recentMessage?.created_at) {
      const lastMessage = data.messages[data.messages.length - 1];
      lastMessage.created_at = new Date(lastMessage.created_at);
      if (lastMessage.created_at <= recentMessage.created_at) {
        loadingSpinnerElement.style = "display: none";
        return;
      }
    }

    chatMessagesElem.innerHTML = "";
    for (const msg of data.messages) {
      let decryptedText = "[Unable to decrypt message]";

      try {
        // If current user sent the message, decrypt message_for_sender
        if (msg.sender_id == CURRENT_USER_ID) {
          decryptedText = await decryptMessage(msg.message_for_sender);
        }
        // Else current user received message, decrypt message
        else {
          decryptedText = await decryptMessage(msg.message);
        }
      } catch (e) {
        decryptedText = "[Decryption error]";
      }

      const div = document.createElement("div");
      div.classList.add("message");
      div.classList.add(msg.sender_id == CURRENT_USER_ID ? "sent" : "received");
      div.textContent = decryptedText;
      chatMessagesElem.appendChild(div);
    }

    chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
    recentMessage = data.messages?.[data.messages.length - 1];
  } catch (err) {
    chatMessagesElem.textContent = "Error loading messages";
  }
  loadingSpinnerElement.style = "display: none";
}

chatForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!currentChatUser) {
    alert("Select a user to chat with first");
    return;
  }
  const text = chatInput.value.trim();
  if (!text) return;

  // Animate send button
  const sendBtn = chatForm.querySelector('button[type="submit"]');
  sendBtn.disabled = true;
  sendBtn.classList.add("btn-pressed");

  try {
    const recipientKey = await getPublicKey(currentChatUser);
    const senderKey = await getPublicKey(CURRENT_USER);

    const encryptedForRecipient = await encryptMessage(text, recipientKey);
    const encryptedForSender = await encryptMessage(text, senderKey);

    const formData = new FormData();
    formData.append("target", currentChatUser);
    formData.append("message", encryptedForRecipient);
    formData.append("message_for_sender", encryptedForSender);

    const res = await fetch("api/send_message.php", {
      method: "POST",
      body: formData,
    });
    const json = await res.json();
    if (json.status !== "ok") throw new Error(json.error || "Send failed");

    addUserToChatList(currentChatUser);
    chatInput.value = "";
    loadMessages(currentChatUser);
  } catch (err) {
    alert("Encryption/send error: " + err.message);
  } finally {
    sendBtn.disabled = false;
    sendBtn.classList.remove("btn-pressed");
  }
});

searchUserInput.addEventListener("change", () => {
  const val = searchUserInput.value.trim();
  if (!val || val === CURRENT_USER) return;
  addUserToChatList(val);
  searchUserInput.value = "";
});

addUserToChatList(CURRENT_USER);
chatInput.disabled = true;

// On page load: fetch private key for decryption
fetchAndImportPrivateKey().catch((err) => {
  alert("Error loading private key: " + err.message);
});

async function loadChatList() {
  try {
    const res = await fetch("api/fetch_chats.php");
    if (!res.ok) throw new Error("Failed to load chat list");
    const data = await res.json();
    if (chatUsers?.size === data.chatUsers.length) {
      return;
    }
    if (data.chatUsers && Array.isArray(data.chatUsers)) {
      data.chatUsers.forEach(addUserToChatList);
    }
  } catch (e) {
    console.error("Error loading chat list:", e);
  }
}

// Call on page load
loadChatList();

setInterval(() => {
  if (!currentChatUser?.length) return;
  loadMessages(currentChatUser);
}, 1000);

setInterval(() => {
  loadChatList();
}, 5000);
