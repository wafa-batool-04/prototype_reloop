<?php
// logout.php - UNIVERSAL LOGOUT HANDLER
session_start();

// Store logout message before destroying session
$logout_message = "You have been successfully logged out!";

// Destroy the session
session_destroy();

// Start a new session just for the message
session_start();
$_SESSION['logout_message'] = $logout_message;

// Redirect to homepage
header("Location: homepage.php");
exit();
?>