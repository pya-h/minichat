<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MiniChat - <?= htmlspecialchars($username) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container-fluid p-0">
        <nav class="navbar navbar-dark bg-primary px-4">
            <span class="navbar-brand mb-0 h1">💬 MiniChat</span>
            <span class="text-white fw-bold"><?= htmlspecialchars($username) ?></span>
            <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
        </nav>

        <div class="row g-0" style="height: calc(100vh - 56px);">
            <!-- Chat List -->
            <div class="col-md-4 border-end p-3 bg-light">
                <input id="target-input" class="form-control mb-3" placeholder="Enter username to chat">
                <ul id="chat-users" class="list-group chat-list"></ul>
            </div>

            <!-- Chat Box -->
            <div class="col-md-8 d-flex flex-column">
                <div id="chat-box" class="flex-grow-1 overflow-auto p-3 bg-white" style="position:relative;">
                    <p class="text-muted" id="no-chat-msg">💡 Select or enter a user to start chatting.</p>
                </div>
                <form id="send-form" class="d-flex border-top p-3 bg-light">
                    <input id="message-input" class="form-control me-2" placeholder="Type your message" autocomplete="off">
                    <button class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_USER = <?= json_encode($username) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/crypto.js"></script>
    <script src="assets/js/chat.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>