<?php
// user_id_loader.php
session_start();

function getUserId() {
    // Check both session and URL for flexibility
    if (isset($_SESSION['id'])) {
        return $_SESSION['id'];
    } elseif (isset($_GET['id'])) {
        $_SESSION['id'] = (int)$_GET['id'];
        return $_SESSION['id'];
    } else {
        header("Location: login.php?error=no_id");
        exit();
    }
}

// Make ID available everywhere
$current_user_id = getUserId();
?>