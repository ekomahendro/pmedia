<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if alumni data is complete
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM alumni WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$alumni = $stmt->get_result()->fetch_assoc();

if (!$alumni) {
    header('Location: complete_profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container { height: 500px; overflow-y: scroll; }
        .active-users { height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">SMA Alumni</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </nav>
        <div class="row mt-3">
            <!-- Alumni List -->
            <div class="col-md-6">
                <h3>Alumni List</h3>
                <div class="mb-3">
                    <input type="text" id="search" class="form-control" placeholder="Search alumni...">
                    <select id="filter-angkatan" class="form-select mt-2">
                        <option value="">All Angkatan</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>">Angkatan <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div id="alumni-list">
                    <!-- Alumni list will be loaded via AJAX -->
                </div>
            </div>
            <!-- Chat Room -->
            <div class="col-md-6">
                <h3>Chat Room</h3>
                <div class="chat-container border p-3 mb-3" id="chat-box">
                    <!-- Chat messages will be loaded here -->
                </div>
                <form id="chat-form">
                    <div class="input-group">
                        <input type="text" id="chat-message" class="form-control" placeholder="Type a message...">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
                <h4 class="mt-3">Active Users</h4>
                <div class="active-users border p-3" id="active-users">
                    <!-- Active users will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Load alumni list
        function loadAlumni() {
            let search = $('#search').val();
            let angkatan = $('#filter-angkatan').val();
            $.ajax({
                url: 'get_alumni.php',
                method: 'GET',
                data: { search: search, angkatan: angkatan },
                success: function(data) {
                    $('#alumni-list').html(data);
                }
            });
        }

        // Load chat messages
        function loadChat() {
            $.ajax({
                url: 'get_chat.php',
                method: 'GET',
                success: function(data) {
                    $('#chat-box').html(data);
                    $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
                }
            });
        }

        // Load active users
        function loadActiveUsers() {
            $.ajax({
                url: 'get_active_users.php',
                method: 'GET',
                success: function(data) {
                    $('#active-users').html(data);
                }
            });
        }

        $(document).ready(function() {
            loadAlumni();
            loadChat();
            loadActiveUsers();

            $('#search, #filter-angkatan').on('change keyup', loadAlumni);

            $('#chat-form').submit(function(e) {
                e.preventDefault();
                let message = $('#chat-message').val();
                if (message.trim() !== '') {
                    $.ajax({
                        url: 'send_chat.php',
                        method: 'POST',
                        data: { message: message },
                        success: function() {
                            $('#chat-message').val('');
                            loadChat();
                        }
                    });
                }
            });

            // Refresh chat and active users every 5 seconds
            setInterval(loadChat, 5000);
            setInterval(loadActiveUsers, 5000);
        });
    </script>
</body>
</html>