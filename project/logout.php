<?php
require_once 'config/functions.php';

// Start secure session
start_secure_session();

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;