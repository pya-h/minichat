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
                <form id="chatForm" class="chat-input" autocomplete="off">
                    <textarea id="chatInput" rows="2" class="form-control" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
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