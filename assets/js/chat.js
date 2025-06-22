const chatListElem = document.getElementById("chatList");
const chatMessagesElem = document.getElementById("chatMessages");
const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatWithElem = document.getElementById("chatWith");
const searchUserInput = document.getElementById("searchUser");
const imageUploadInput = document.getElementById("imageUploadInput");
const imageUploadBtn = document.getElementById("imageUploadBtn");

let currentChatUser = null;
let recentMessage = null;
const chatUsers = new Set();

let mediaRecorder = null;
let audioChunks = [];
const voiceBtn = document.getElementById("voiceBtn");
let isRecording = false;
let recordingStartTime = null;
let shouldSendRecording = true;
let audioContext = null;
let activeAnalyser = null;

function addUserToChatList(username) {
    if (chatUsers.has(username) || username === CURRENT_USER) return;
    chatUsers.add(username);

    const li = document.createElement("li");
    li.tabIndex = 0;
    li.style.setProperty("--i", chatListElem.children.length);

    const initials = username
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase();

    li.innerHTML = `<span class="avatar">${initials}</span> <span>${username}</span><span id='user_${username}_loading' style="display:none" class="spinner-border spinner-border-sm text-primary ms-2" role="status" aria-hidden="true"></span>`;
    li.id = `user_${username}`;
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

    document
        .getElementById(`user_${currentChatUser}`)
        ?.classList.remove("selected-chat");
    currentChatUser = username;
    document
        .getElementById(`user_${currentChatUser}`)
        ?.classList.add("selected-chat");
    chatInput.disabled = false;
    chatWithElem.textContent = username;
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
            if (loadingSpinnerElement)
                loadingSpinnerElement.style = "display: none";
            return;
        }

        if (recentMessage?.created_at) {
            const lastMessage = data.messages[data.messages.length - 1];
            lastMessage.created_at = new Date(lastMessage.created_at);
            if (lastMessage.created_at <= recentMessage.created_at) {
                if (loadingSpinnerElement)
                    loadingSpinnerElement.style = "display: none";
                return;
            }
        }

        chatMessagesElem.innerHTML = "";
        for (const msg of data.messages) {
            let div = document.createElement("div");
            div.classList.add("message");
            div.classList.add(
                msg.sender_id == CURRENT_USER_ID ? "sent" : "received"
            );

            if (msg.message_type === "voice" && msg.voice_file_path) {
                // Add a special class for voice messages to customize the bubble
                div.classList.add("is-voice-message");

                // The voice player itself will become the message bubble
                div.innerHTML = `
          <div class="voice-player-container">
            <button class="voice-play-btn" onclick="playVoiceMessage(${
                msg.id
            })">
              <i class="fas fa-play"></i>
            </button>
            <div class="voice-waveform">
              <div class="waveform-bars">
                ${generateWaveformBars()}
              </div>
            </div>
            <div class="voice-duration-display">--:--</div>
          </div>
        `;
                div.setAttribute("data-message-id", msg.id);
            } else if (msg.message_type === "image" && msg.image_file_path) {
                div.classList.add("is-image-message");

                div.innerHTML = `
                <a href="api/get_image.php?id=${msg.id}" target="_blank" title="View full image">
                    <img src="api/get_image.php?id=${msg.id}" class="message-image" alt="Image from ${msg.sender_id}" 
                        onload="this.parentNode.parentNode.parentNode.scrollTop = this.parentNode.parentNode.parentNode.scrollHeight"
                        onerror="this.parentNode.innerHTML='<div style=\\'padding: 20px; text-align: center; color: #6c757d;\\'>Image not available</div>'">
                </a>
                `;
                // onload is a bit of a hack to scroll down once the image loads
                // onerror provides fallback for failed image loads
            } else {
                let decryptedText = "[Unable to decrypt message]";
                try {
                    if (msg.sender_id == CURRENT_USER_ID) {
                        decryptedText = await decryptMessage(
                            msg.message_for_sender
                        );
                    } else {
                        decryptedText = await decryptMessage(msg.message);
                    }
                } catch (e) {
                    decryptedText = "[Unsupported message]";
                }
                div.textContent = decryptedText;
                if (
                    /^[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF]/.test(
                        decryptedText.trim()
                    )
                ) {
                    div.dir = "rtl";
                }
            }
            chatMessagesElem.appendChild(div);
        }

        chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
        recentMessage = data.messages?.[data.messages.length - 1];
    } catch (err) {
        chatMessagesElem.textContent = "Error loading messages";
    }
    const loadingSpinnerElement = document.getElementById(
        `user_${username}_loading`
    );
    if (loadingSpinnerElement) loadingSpinnerElement.style = "display: none";
}

// Generate waveform bars for voice messages
function generateWaveformBars() {
    const bars = [];
    const barCount = 30;
    for (let i = 0; i < barCount; i++) {
        const height = Math.random() * 60 + 15;
        bars.push(
            `<div class="waveform-bar" style="height: ${height}%"></div>`
        );
    }
    return bars.join("");
}

window.playVoiceMessage = function (messageId) {
    const messageDiv = document.querySelector(
        `[data-message-id="${messageId}"]`
    );
    if (!messageDiv) return;

    const playBtn = messageDiv.querySelector(".voice-play-btn");
    const durationDisplay = messageDiv.querySelector(".voice-duration-display");

    let audio = messageDiv.querySelector("audio");
    if (!audio) {
        if (!audioContext) {
            audioContext = new (window.AudioContext ||
                window.webkitAudioContext)();
        }

        audio = document.createElement("audio");
        audio.src = `api/get_voice_message.php?id=${messageId}`;
        audio.preload = "metadata";
        audio.style.display = "none"; // Hide the actual audio element
        messageDiv.appendChild(audio);

        const source = audioContext.createMediaElementSource(audio);
        const analyser = audioContext.createAnalyser();
        analyser.fftSize = 256;
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        source.connect(analyser);
        analyser.connect(audioContext.destination);

        messageDiv.audioAnalyser = { analyser, bufferLength, dataArray };

        audio.addEventListener("loadedmetadata", function () {
            if (isFinite(audio.duration)) {
                const duration = Math.round(audio.duration);
                const minutes = Math.floor(duration / 60);
                const seconds = duration % 60;
                durationDisplay.textContent = `${minutes}:${seconds
                    .toString()
                    .padStart(2, "0")}`;
            } else {
                durationDisplay.textContent = "??:??";
            }
        });

        audio.addEventListener("timeupdate", function () {
            if (isFinite(audio.duration)) {
                const current = Math.round(audio.currentTime);
                const minutes = Math.floor(current / 60);
                const seconds = current % 60;
                durationDisplay.textContent = `${minutes}:${seconds
                    .toString()
                    .padStart(2, "0")}`;

                const progress = audio.currentTime / audio.duration;
                const waveformBars =
                    messageDiv.querySelectorAll(".waveform-bar");
                const playedBarsCount = Math.floor(
                    progress * waveformBars.length
                );

                waveformBars.forEach((bar, index) => {
                    if (index < playedBarsCount) {
                        bar.classList.add("played");
                    } else {
                        bar.classList.remove("played");
                    }
                });
            }
        });

        audio.addEventListener("ended", function () {
            playBtn.innerHTML = `<i class="fas fa-play"></i>`;
            playBtn.classList.remove("playing");

            messageDiv
                .querySelectorAll(".waveform-bar")
                .forEach((bar) => bar.classList.add("played"));
        });

        audio.addEventListener("error", function (e) {
            console.error("Audio error:", e);
            showModal(
                "Audio Error",
                "Unable to load voice message. The audio file may be missing or corrupted.",
                "error"
            );
            playBtn.disabled = true;
            playBtn.style.opacity = "0.5";
        });
    }

    const { analyser, dataArray } = messageDiv.audioAnalyser;
    const waveformBarsContainer = messageDiv.querySelector(".waveform-bars");

    function draw() {
        if (audio.paused || audio.ended) {
            if (activeAnalyser === analyser) activeAnalyser = null;

            const bars = waveformBarsContainer.children;
            for (let i = 0; i < bars.length; i++) {
                bars[i].style.height = `20%`;
            }

            return;
        }

        activeAnalyser = analyser;
        requestAnimationFrame(draw);

        analyser.getByteFrequencyData(dataArray);

        const bars = waveformBarsContainer.children;
        const barCount = bars.length;

        for (let i = 0; i < barCount; i++) {
            // Scale the data to the bar height (0-100%)
            const barHeight = Math.pow(dataArray[i] / 255, 2) * 100;
            bars[i].style.height = `${Math.max(10, barHeight)}%`;
        }
    }

    if (audio.paused) {
        // Pause all others
        document.querySelectorAll(".voice-play-btn.playing").forEach((btn) => {
            const audio = btn
                .closest(".voice-player-container")
                .querySelector("audio");
            if (audio && !audio.paused) {
                audio.pause();
            }
            btn.classList.remove("playing");
            const i = btn.querySelector("i");
            i.classList.remove("fa-pause");
            i.classList.add("fa-play");
        });

        if (audioContext.state === "suspended") {
            audioContext.resume();
        }

        audio.play().catch(function (error) {
            console.error("Playback error:", error);
            showModal(
                "Playback Error",
                "Unable to play voice message. Please try again.",
                "error"
            );
        });
        playBtn.classList.add("playing");
        playBtn.innerHTML = `<i class="fas fa-pause"></i>`;
        draw();
    } else {
        audio.pause();
        playBtn.classList.remove("playing");
        playBtn.innerHTML = `<i class="fas fa-play"></i>`;
    }
};

const sendMessage = async () => {
    if (!currentChatUser) {
        showModal(
            "No Chat Selected",
            "Select a user to chat with first",
            "warning"
        );
        return;
    }
    const text = chatInput.value.trim();
    if (!text) return;

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
        showModal(
            "Send Error",
            "Encryption/send error: " + err.message,
            "error"
        );
    } finally {
        sendBtn.disabled = false;
        sendBtn.classList.remove("btn-pressed");
    }
};

chatForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    sendMessage();
});

chatInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

chatInput.addEventListener("input", () => {
    const text = chatInput.value;
    const rtlRegex = /^[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF]/;

    if (rtlRegex.test(text)) {
        chatInput.dir = "rtl";
    } else {
        chatInput.dir = "ltr";
    }
});

searchUserInput.addEventListener("change", async () => {
    const val = searchUserInput.value.trim();
    if (!val || val === CURRENT_USER) return;

    if (!/^[a-zA-Z][a-zA-Z0-9_-]{2,}$/.test(val)) {
        showModal(
            "Invalid Username",
            "Username must start with a letter and contain only letters, numbers, hyphens, and underscores.",
            "error"
        );
        searchUserInput.value = "";
        return;
    }

    const originalPlaceholder = searchUserInput.placeholder;
    searchUserInput.placeholder = "Checking user...";
    searchUserInput.disabled = true;

    try {
        const response = await fetch(
            `api/check_user_exists.php?username=${encodeURIComponent(val)}`
        );
        const data = await response.json();

        if (data.exists) {
            addUserToChatList(val);
            searchUserInput.value = "";
        } else {
            showModal(
                "User Not Found",
                `User "${val}" does not exist. Please check the username and try again.`,
                "warning"
            );
            searchUserInput.value = "";
        }
    } catch (error) {
        showModal(
            "Connection Error",
            "Error checking user existence. Please try again.",
            "error"
        );
        searchUserInput.value = "";
    } finally {
        searchUserInput.placeholder = originalPlaceholder;
        searchUserInput.disabled = false;
    }
});

searchUserInput.addEventListener("input", function () {
    const val = this.value.trim();
    const feedback = document.getElementById("searchUserFeedback");

    this.classList.remove("is-invalid", "is-valid");

    if (val && val !== CURRENT_USER) {
        if (/^[a-zA-Z][a-zA-Z0-9_-]{2,}$/.test(val)) {
            this.classList.remove("is-invalid");
            if (feedback) feedback.style.display = "none";
        } else {
            this.classList.add("is-invalid");
            if (feedback) feedback.style.display = "block";
        }
    } else {
        if (feedback) feedback.style.display = "none";
    }
});

chatInput.disabled = true;
chatInput.textContent = "Select someone to chat...";
fetchAndImportPrivateKey().catch((err) => {
    showModal(
        "Key Error",
        "Error loading private key: " + err.message,
        "error"
    );
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

loadChatList();

setInterval(() => {
    if (!currentChatUser?.length) return;
    loadMessages(currentChatUser);
}, 1000);

setInterval(() => {
    loadChatList();
}, 5000);

voiceBtn.addEventListener("click", async () => {
    if (!currentChatUser) {
        showModal(
            "No Chat Selected",
            "Select a user to chat with first",
            "warning"
        );
        return;
    }
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            recordingStartTime = Date.now();
            shouldSendRecording = true;

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = async () => {
                if (shouldSendRecording && audioChunks.length > 0) {
                    const audioBlob = new Blob(audioChunks, {
                        type: "audio/webm",
                    });
                    await sendVoiceMessage(audioBlob);
                }

                stream.getTracks().forEach((track) => track.stop());
                resetRecordingState();
            };

            mediaRecorder.start();
            isRecording = true;
            setRecordingState(true);

            addRecordingIndicator();
        } catch (err) {
            showModal(
                "Microphone Error",
                "Microphone access denied or not available.",
                "error"
            );
        }
    } else {
        stopRecording();
    }
});

function setRecordingState(recording) {
    if (recording) {
        voiceBtn.classList.add("btn-danger");
        voiceBtn.classList.remove("btn-secondary");
        voiceBtn.innerHTML = `<i class="fas fa-stop"></i>`;
        voiceBtn.title = "Stop recording (click to stop)";
    } else {
        voiceBtn.classList.remove("btn-danger");
        voiceBtn.classList.add("btn-secondary");
        voiceBtn.innerHTML = `<i class="fas fa-microphone"></i>`;
        voiceBtn.title = "Record voice message";
    }
}

function resetRecordingState() {
    isRecording = false;
    setRecordingState(false);
    removeRecordingIndicator();
}

function addRecordingIndicator() {
    const indicator = document.createElement("div");
    indicator.id = "recordingIndicator";
    indicator.className = "recording-indicator";
    indicator.innerHTML = `
    <div class="recording-content">
      <div class="recording-dot"></div>
      <span class="px-1 px-lg-5 pg-md-5">Recording...</span>
      <button type="button" class="btn btn-sm btn-outline-light me-2" onclick="stopRecording()" title="Stop Recording">
        <i class="fas fa-stop"></i>
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelRecording()" title="Cancel Recording">
        <i class="fas fa-times"></i>
      </button>
    </div>
  `;
    chatMessagesElem.appendChild(indicator);
    chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;
}

function removeRecordingIndicator() {
    const indicator = document.getElementById("recordingIndicator");
    if (indicator) {
        indicator.remove();
    }
}

function stopRecording() {
    if (isRecording && mediaRecorder) {
        shouldSendRecording = true;
        mediaRecorder.stop();
    }
}

function cancelRecording() {
    if (isRecording && mediaRecorder) {
        shouldSendRecording = false;
        mediaRecorder.stop();
    }
}

window.stopRecording = stopRecording;
window.cancelRecording = cancelRecording;

async function sendVoiceMessage(audioBlob) {
    try {
        const sendingIndicator = document.createElement("div");
        sendingIndicator.className = "message sent sending-indicator";
        sendingIndicator.innerHTML = `
      <div class="voice-message-sending">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <span>Sending voice message...</span>
      </div>
    `;
        chatMessagesElem.appendChild(sendingIndicator);
        chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;

        const formData = new FormData();
        formData.append("target", currentChatUser);
        formData.append("message", null); // TODO: Add caption for voice messages
        formData.append("message_for_sender", null);
        formData.append("voice_file", audioBlob, "voice_message.webm");

        const res = await fetch("api/send_voice_message.php", {
            method: "POST",
            body: formData,
        });

        const json = await res.json();
        if (json.status !== "ok") throw new Error(json.error || "Send failed");

        sendingIndicator.remove();

        addUserToChatList(currentChatUser);
        loadMessages(currentChatUser);
    } catch (err) {
        showModal(
            "Voice Send Error",
            "Voice message send error: " + err.message,
            "error"
        );

        const sendingIndicator = document.querySelector(".sending-indicator");
        if (sendingIndicator) sendingIndicator.remove();
    }
}

imageUploadBtn.addEventListener("click", () => {
    if (!currentChatUser) {
        showModal(
            "No Chat Selected",
            "Select a user to chat with first",
            "warning"
        );
        return;
    }
    imageUploadInput.click();
});

imageUploadInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
        if (!file.type.startsWith("image/")) {
            showModal(
                "Invalid File Type",
                "Please select an image file.",
                "warning"
            );
            e.target.value = null;
            return;
        }

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showModal(
                "File Too Large",
                "Image file size must be less than 5MB.",
                "warning"
            );
            e.target.value = null;
            return;
        }

        sendImageMessage(file);
    }

    e.target.value = null;
});

async function sendImageMessage(imageFile) {
    try {
        const sendingIndicator = document.createElement("div");
        sendingIndicator.className = "message sent sending-indicator";
        sendingIndicator.innerHTML = `
      <div class="image-message-sending">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <span>Sending image...</span>
      </div>
    `;
        chatMessagesElem.appendChild(sendingIndicator);
        chatMessagesElem.scrollTop = chatMessagesElem.scrollHeight;

        imageUploadBtn.disabled = true;

        const formData = new FormData();
        formData.append("target", currentChatUser);
        formData.append("message", null); // TODO: Add caption for image messages
        formData.append("message_for_sender", null);
        formData.append("image_file", imageFile, imageFile.name);

        const res = await fetch("api/send_image_message.php", {
            method: "POST",
            body: formData,
        });

        const json = await res.json();
        if (json.status !== "ok") throw new Error(json.error || "Send failed");

        sendingIndicator.remove();

        addUserToChatList(currentChatUser);
        loadMessages(currentChatUser);
    } catch (err) {
        showModal(
            "Image Send Error",
            "Image send error: " + err.message,
            "error"
        );

        const sendingIndicator = document.querySelector(".sending-indicator");
        if (sendingIndicator) sendingIndicator.remove();
    } finally {
        imageUploadBtn.disabled = false;
    }
}
