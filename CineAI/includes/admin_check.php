<?php
// includes/admin_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super-Admin', 'Admin', 'Sub-Admin'])) {
    // Redirect non-admins/sub-admins or guests to index.php
    header('Location: index.php');
    exit;
}
?>
