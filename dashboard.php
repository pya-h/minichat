<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MiniChat Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/dashboard.css" rel="stylesheet" />
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg navbar-light bg-light px-3">
            <a class="navbar-brand" href="#">MiniChat</a>
            <div class="ms-auto">
                <span class="me-3">Logged in as <strong><?= htmlspecialchars($username) ?></strong></span>
                <a href="api/logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
            </div>
        </nav>

        <div class="chat-container">
            <aside class="sidebar">
                <h4>Chats</h4>
                <input type="text" id="searchUser" class="form-control mb-3" placeholder="Enter username to chat" />
                <ul class="chat-list" id="chatList"></ul>
            </aside>

            <section class="chat-area d-flex flex-column">
                <div class="chat-header">
                    <h5 id="chatWith">Select a chat</h5>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input p-3">
                    <form id="chatForm" class="d-flex w-100 align-items-center">
                        <textarea id="chatInput" class="form-control" placeholder="Type a message..." rows="1"></textarea>
                        
                        <!-- Hidden file input for image uploads -->
                        <input type="file" id="imageUploadInput" accept="image/*" style="display: none;">

                        <!-- Image Upload Button -->
                        <button type="button" id="imageUploadBtn" class="btn btn-secondary ms-2" title="Send an image">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-paperclip" viewBox="0 0 16 16">
                                <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 0 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0V3z"/>
                            </svg>
                        </button>

                        <!-- Voice Message Button -->
                        <button type="button" id="voiceBtn" class="btn btn-secondary ms-2" title="Record voice message">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-mic" viewBox="0 0 16 16">
                                <path d="M8 12a3 3 0 0 0 3-3V4a3 3 0 0 0-6 0v5a3 3 0 0 0 3 3z"/>
                                <path d="M5 10a5 5 0 0 0 6 0v1a4 4 0 0 1-8 0v-1a.5.5 0 0 1 1 0v1a3 3 0 0 0 6 0v-1a.5.5 0 0 1 1 0z"/>
                            </svg>
                        </button>

                        <!-- Send Button -->
                        <button type="submit" class="btn btn-primary ms-2">Send</button>
                </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        const CURRENT_USER = <?= json_encode($username) ?>;
        const CURRENT_USER_ID = <?= json_encode($user_id) ?>;

    </script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/crypto.js"></script>
    <script src="assets/js/chat.js"></script>
</body>

</html>