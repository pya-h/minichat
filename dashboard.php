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
    <link href="assets/css/fontawesome.min.css" rel="stylesheet" />
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
                <input type="text" id="searchUser" class="form-control mb-1" placeholder="Enter username to chat" />
                <div id="searchUserFeedback" class="invalid-feedback" style="display: none; font-size: 0.875em;">
                    Invalid username format
                </div>
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
                            <i class="fas fa-image"></i>
                        </button>

                        <!-- Voice Message Button -->
                        <button type="button" id="voiceBtn" class="btn btn-secondary ms-2" title="Record voice message">
                            <i class="fas fa-microphone"></i>
                        </button>

                        <!-- Send Button -->
                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="fas fa-paper-plane"></i>
                        </button>
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