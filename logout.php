<?php
session_start();

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect to login/register page
header("Location: index.php");
exit();
?>
