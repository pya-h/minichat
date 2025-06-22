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
        <nav class="navbar navbar-expand-lg px-3">
            <a class="navbar-brand" href="#">MiniChat</a>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 logged-in-as">Logged in as <strong><?= htmlspecialchars($username) ?></strong></span>
                <a href="api/logout.php" class="btn btn-logout btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </nav>

        <div class="chat-container">
            <aside class="sidebar">
                <h4>Chats</h4>
                <input type="text" id="searchUser" class="form-control mb-1" placeholder="Enter username to chat" />
                <div id="searchUserFeedback" class="invalid-feedback" style="display: none; font-size: 0.875em;">
                    Invalid username format
                </div>
                <div class="chat-list-wrapper">
                    <ul class="chat-list" id="chatList"></ul>
                </div>
            </aside>

            <section class="chat-area d-flex flex-column">
                <div class="chat-header">
                    <h5 id="chatWith">Select a chat</h5>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input p-3">
                    <form id="chatForm" class="d-flex w-100 align-items-center">
                        <textarea id="chatInput" class="form-control" placeholder="Type a message..." rows="1"></textarea>
                        
                        <input type="file" id="imageUploadInput" accept="image/*" style="display: none;">

                        <button type="button" id="imageUploadBtn" class="btn btn-secondary ms-2" title="Send an image">
                            <i class="fas fa-image"></i>
                        </button>

                        <button type="button" id="voiceBtn" class="btn btn-secondary ms-2" title="Record voice message">
                            <i class="fas fa-microphone"></i>
                        </button>

                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                </form>
                </div>
            </section>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" style="display: none;">
        <div id="modalContainer" class="modal-container">
            <div class="modal-header">
                <div class="modal-icon">
                    <i id="modalIcon" class="fas fa-info-circle"></i>
                </div>
                <h4 id="modalTitle" class="modal-title">Information</h4>
                <button type="button" class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">This is a modal message.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal()">OK</button>
            </div>
        </div>
    </div>

    <link href="assets/css/modal.css" rel="stylesheet" />
    <script>
        const CURRENT_USER = <?= json_encode($username) ?>;
        const CURRENT_USER_ID = <?= json_encode($user_id) ?>;
    </script>
    <script src="assets/js/modal.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/crypto.js"></script>
    <script src="assets/js/chat.js"></script>
</body>

</html>