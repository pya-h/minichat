const chatListElem = document.getElementById("chatList");
const chatMessagesElem = document.getElementById("chatMessages");
const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatWithElem = document.getElementById("chatWith");
const searchUserInput = document.getElementById("searchUser");

let currentChatUser = null;
let recentMessage = null;
const chatUsers = new Set();

// --- Voice Message Logic ---
let mediaRecorder = null;
let audioChunks = [];
const voiceBtn = document.getElementById('voiceBtn');
let isRecording = false;
let recordingStartTime = null;
let shouldSendRecording = true; // Flag to control whether to send or cancel

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
      loadingSpinnerElement.style = 'display: inline';
    }
    const res = await fetch(
      `api/fetch_messages.php?with=${encodeURIComponent(username)}`
    );
    if (!res.ok) throw new Error('Failed to load messages');
    const data = await res.json();
    if (!data.messages.length) {
      chatMessagesElem.innerHTML = '';
      chatMessagesElem.textContent = 'No messages yet.';
      loadingSpinnerElement.style = 'display: none';
      return;
    }

    if (recentMessage?.created_at) {
      const lastMessage = data.messages[data.messages.length - 1];
      lastMessage.created_at = new Date(lastMessage.created_at);
      if (lastMessage.created_at <= recentMessage.created_at) {
        loadingSpinnerElement.style = 'display: none';
        return;
      }
    }

    chatMessagesElem.innerHTML = '';
    for (const msg of data.messages) {
      let div = document.createElement('div');
      div.classList.add('message');
      div.classList.add(msg.sender_id == CURRENT_USER_ID ? 'sent' : 'received');
      
      if (msg.message_type === 'voice' && msg.voice_file_path) {
        // Voice message with improved UI
        div.innerHTML = `
          <div class="voice-message">
            <div class="voice-message-header">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mic-fill me-2" viewBox="0 0 16 16">
                <path d="M5 3.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 .5.5v5a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-5z"/>
                <path d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5v-8zM1.5 4a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-13z"/>
              </svg>
              <span class="voice-message-label">Voice Message</span>
            </div>
            <audio controls class="voice-audio-player" preload="metadata">
              <source src="api/get_voice_message.php?id=${msg.id}" type="audio/webm">
              Your browser does not support the audio element.
            </audio>
          </div>
        `;
        
        // Add duration display when audio loads
        const audio = div.querySelector('audio');
        audio.addEventListener('loadedmetadata', function() {
          const duration = Math.round(audio.duration);
          const durationSpan = document.createElement('span');
          durationSpan.className = 'voice-duration';
          durationSpan.textContent = ` (${duration}s)`;
          div.querySelector('.voice-message-label').appendChild(durationSpan);
        });
        
      } else {
        // Text message - check if it's a voice message placeholder
        let decryptedText = '[Unable to decrypt message]';
        try {
          if (msg.sender_id == CURRENT_USER_ID) {
            decryptedText = await decryptMessage(msg.message_for_sender);
          } else {
            decryptedText = await decryptMessage(msg.message);
          }
        } catch (e) {
          decryptedText = '[Decryption error]';
        }
        
        // Check if this is a voice message placeholder
        if (decryptedText === '[Voice message]' || decryptedText.includes('[Voice message]')) {
          // Only create voice player if this message actually has a voice file
          if (msg.message_type === 'voice' && msg.voice_file_path) {
            // Create a voice message player for this message
            div.innerHTML = `
              <div class="voice-message">
                <div class="voice-message-header">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mic-fill me-2" viewBox="0 0 16 16">
                    <path d="M5 3.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 .5.5v5a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-5z"/>
                    <path d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5v-8zM1.5 4a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-13z"/>
                  </svg>
                  <span class="voice-message-label">Voice Message</span>
                </div>
                <div class="voice-player-container">
                  <button class="voice-play-btn" onclick="playVoiceMessage(${msg.id})">
                    <svg class="play-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                      <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
                    </svg>
                  </button>
                  <div class="voice-waveform">
                    <div class="waveform-bars">
                      ${generateWaveformBars()}
                    </div>
                  </div>
                  <div class="voice-duration-display">--:--</div>
                </div>
              </div>
            `;
            
            // Store message ID for audio playback
            div.setAttribute('data-message-id', msg.id);
          } else {
            // Show placeholder text for voice messages without files
            div.innerHTML = `
              <div class="voice-message-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mic-fill me-2" viewBox="0 0 16 16">
                  <path d="M5 3.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 .5.5v5a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-5z"/>
                  <path d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5v-8zM1.5 4a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-13z"/>
                </svg>
                <span>Voice message (unavailable)</span>
              </div>
            `;
          }
        } else {
          // Regular text message
          div.textContent = decryptedText;
        }
      }
      chatMessagesElem.appendChild(div);
    }

    chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
    recentMessage = data.messages?.[data.messages.length - 1];
  } catch (err) {
    chatMessagesElem.textContent = 'Error loading messages';
  }
  const loadingSpinnerElement = document.getElementById(
    `user_${username}_loading`
  );
  if (loadingSpinnerElement) loadingSpinnerElement.style = 'display: none';
}

// Generate waveform bars for voice messages
function generateWaveformBars() {
  const bars = [];
  for (let i = 0; i < 20; i++) {
    const height = Math.random() * 60 + 20; // Random height between 20-80px
    bars.push(`<div class="waveform-bar" style="height: ${height}%"></div>`);
  }
  return bars.join('');
}

// Global function to play voice messages
window.playVoiceMessage = function(messageId) {
  const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
  if (!messageDiv) return;
  
  const playBtn = messageDiv.querySelector('.voice-play-btn');
  const playIcon = playBtn.querySelector('.play-icon');
  const durationDisplay = messageDiv.querySelector('.voice-duration-display');
  
  // Create audio element if it doesn't exist
  let audio = messageDiv.querySelector('audio');
  if (!audio) {
    audio = document.createElement('audio');
    audio.src = `api/get_voice_message.php?id=${messageId}`;
    audio.preload = 'metadata';
    messageDiv.appendChild(audio);
    
    // Add event listeners
    audio.addEventListener('loadedmetadata', function() {
      const duration = Math.round(audio.duration);
      const minutes = Math.floor(duration / 60);
      const seconds = duration % 60;
      durationDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    });
    
    audio.addEventListener('timeupdate', function() {
      const current = Math.round(audio.currentTime);
      const duration = Math.round(audio.duration);
      const minutes = Math.floor(current / 60);
      const seconds = current % 60;
      durationDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    });
    
    audio.addEventListener('ended', function() {
      playIcon.innerHTML = `
        <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
      `;
      playBtn.classList.remove('playing');
    });
    
    // Add error handling
    audio.addEventListener('error', function(e) {
      console.error('Audio error:', e);
      alert('Unable to load voice message. The audio file may be missing or corrupted.');
      playBtn.disabled = true;
      playBtn.style.opacity = '0.5';
    });
  }
  
  if (audio.paused) {
    // Play audio
    audio.play().catch(function(error) {
      console.error('Playback error:', error);
      alert('Unable to play voice message. Please try again.');
    });
    playIcon.innerHTML = `
      <path d="M6 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5H4a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5h2zm3 0a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5H7a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5h2z"/>
    `;
    playBtn.classList.add('playing');
  } else {
    // Pause audio
    audio.pause();
    playIcon.innerHTML = `
      <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
    `;
    playBtn.classList.remove('playing');
  }
};

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

// --- Voice Message Logic ---
voiceBtn.addEventListener('click', async () => {
  if (!currentChatUser) {
    alert('Select a user to chat with first');
    return;
  }
  if (!isRecording) {
    // Start recording
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder = new MediaRecorder(stream);
      audioChunks = [];
      recordingStartTime = Date.now();
      shouldSendRecording = true; // Reset flag
      
      mediaRecorder.ondataavailable = (e) => {
        if (e.data.size > 0) audioChunks.push(e.data);
      };
      
      mediaRecorder.onstop = async () => {
        if (shouldSendRecording && audioChunks.length > 0) {
          const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
          await sendVoiceMessage(audioBlob);
        }
        // Stop all tracks to release microphone
        stream.getTracks().forEach(track => track.stop());
        // Reset recording state
        resetRecordingState();
      };
      
      mediaRecorder.start();
      isRecording = true;
      setRecordingState(true);
      
      // Add recording indicator to chat
      addRecordingIndicator();
      
    } catch (err) {
      alert('Microphone access denied or not available.');
    }
  } else {
    // Stop recording
    stopRecording();
  }
});

function setRecordingState(recording) {
  if (recording) {
    voiceBtn.classList.add('btn-danger');
    voiceBtn.classList.remove('btn-secondary');
    voiceBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-stop-circle" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
        <path d="M6.5 5.5A.5.5 0 0 1 7 6v4a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 0A.5.5 0 0 1 10 6v4a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5z"/>
      </svg>
    `;
    voiceBtn.title = 'Stop recording (click to stop)';
  } else {
    voiceBtn.classList.remove('btn-danger');
    voiceBtn.classList.add('btn-secondary');
    voiceBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-mic" viewBox="0 0 16 16">
        <path d="M8 12a3 3 0 0 0 3-3V4a3 3 0 0 0-6 0v5a3 3 0 0 0 3 3z"/>
        <path d="M5 10a5 5 0 0 0 6 0v1a4 4 0 0 1-8 0v-1a.5.5 0 0 1 1 0v1a3 3 0 0 0 6 0v-1a.5.5 0 0 1 1 0v1a4 4 0 0 1-8 0v-1a.5.5 0 0 1 1 0z"/>
      </svg>
    `;
    voiceBtn.title = 'Record voice message';
  }
}

function resetRecordingState() {
  isRecording = false;
  setRecordingState(false);
  removeRecordingIndicator();
}

function addRecordingIndicator() {
  const indicator = document.createElement('div');
  indicator.id = 'recordingIndicator';
  indicator.className = 'recording-indicator';
  indicator.innerHTML = `
    <div class="recording-content">
      <div class="recording-dot"></div>
      <span class="px-1 px-lg-5 pg-md-5">Recording...</span>
      <button type="button" class="btn btn-sm btn-outline-light me-2" onclick="stopRecording()">Stop</button>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelRecording()">Cancel</button>
    </div>
  `;
  chatMessagesElem.appendChild(indicator);
  chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
}

function removeRecordingIndicator() {
  const indicator = document.getElementById('recordingIndicator');
  if (indicator) {
    indicator.remove();
  }
}

function stopRecording() {
  if (isRecording && mediaRecorder) {
    shouldSendRecording = true; // Ensure we send the recording
    mediaRecorder.stop();
  }
}

function cancelRecording() {
  if (isRecording && mediaRecorder) {
    shouldSendRecording = false; // Prevent sending the recording
    mediaRecorder.stop();
  }
}

// Make functions globally accessible
window.stopRecording = stopRecording;
window.cancelRecording = cancelRecording;

async function sendVoiceMessage(audioBlob) {
  try {
    // Show sending indicator
    const sendingIndicator = document.createElement('div');
    sendingIndicator.className = 'message sent sending-indicator';
    sendingIndicator.innerHTML = `
      <div class="voice-message-sending">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <span>Sending voice message...</span>
      </div>
    `;
    chatMessagesElem.appendChild(sendingIndicator);
    chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
    
    const recipientKey = await getPublicKey(currentChatUser);
    const senderKey = await getPublicKey(CURRENT_USER);
    
    // Encrypt a placeholder text for voice messages
    const encryptedForRecipient = await encryptMessage('[Voice message]', recipientKey);
    const encryptedForSender = await encryptMessage('[Voice message]', senderKey);
    
    const formData = new FormData();
    formData.append('target', currentChatUser);
    formData.append('message', encryptedForRecipient);
    formData.append('message_for_sender', encryptedForSender);
    formData.append('voice_file', audioBlob, 'voice_message.webm');
    
    const res = await fetch('api/send_voice_message.php', {
      method: 'POST',
      body: formData,
    });
    
    const json = await res.json();
    if (json.status !== 'ok') throw new Error(json.error || 'Send failed');
    
    // Remove sending indicator
    sendingIndicator.remove();
    
    addUserToChatList(currentChatUser);
    loadMessages(currentChatUser);
    
  } catch (err) {
    alert('Voice message send error: ' + err.message);
    // Remove sending indicator on error
    const sendingIndicator = document.querySelector('.sending-indicator');
    if (sendingIndicator) sendingIndicator.remove();
  }
}
