<?php
require_once 'admin-config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: admin-login.php');
exit;
?>
