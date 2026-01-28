<?php
require_once 'config.php';

// Update user status to offline
if (isLoggedIn()) {
    $stmt = $conn->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>
