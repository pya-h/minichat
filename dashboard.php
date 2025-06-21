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
    <style>
        /* Same styles as before, plus minor improvements */
        html,
        body,
        #app {
            height: 100%;
            margin: 0;
        }

        #app {
            display: flex;
            flex-direction: column;
        }

        .chat-container {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: 280px;
            border-right: 1px solid #ddd;
            padding: 1rem;
            overflow-y: auto;
        }

        .sidebar h4 {
            margin-bottom: 1rem;
        }

        .chat-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .chat-list li {
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .chat-list li:hover,
        .chat-list li.active {
            background-color: #0d6efd;
            color: white;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f1f1f1;
        }

        .chat-input {
            padding: 1rem;
            border-top: 1px solid #ddd;
            display: flex;
        }

        .chat-input textarea {
            flex: 1;
            resize: none;
        }

        .chat-input button {
            margin-left: 0.5rem;
        }

        .message {
            margin-bottom: 0.75rem;
            max-width: 70%;
            padding: 0.5rem 0.75rem;
            border-radius: 15px;
            animation: fadeIn 0.3s ease forwards;
        }

        .message.sent {
            background-color: #0d6efd;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }

        .message.received {
            background-color: #e4e6eb;
            color: #000;
            margin-right: auto;
            border-bottom-left-radius: 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media(max-width: 768px) {
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                height: 180px;
                overflow-x: auto;
                white-space: nowrap;
            }

            .chat-container {
                flex-direction: column;
            }

            .chat-area {
                height: calc(100% - 180px);
            }

            .chat-list li {
                display: inline-block;
                margin-right: 1rem;
                border-radius: 15px;
                padding: 0.5rem 1rem;
            }
        }
    </style>
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