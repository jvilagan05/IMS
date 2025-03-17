<?php
// Start the session to destroy it
session_start();

// Destroy all session data (this logs the user out)
session_unset();
session_destroy();

// Set headers to prevent caching of the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// This will forcefully redirect the user to the login page
header("Location: index.php");
exit();
?>