<?php
// includes/database.php

$host = 'localhost';      // your DB host
$db_user = 'root';        // your DB username
$db_password = '';        // your DB password
$db_name = 'PHARMACY';    // your database name (make sure it's uppercase or exact as created)

// Create connection
$con = new mysqli($host, $db_user, $db_password, $db_name);

// Check connection
if ($con->connect_error) {
    die("Database connection failed: " . $con->connect_error);
}

// Set charset to utf8mb4 for proper encoding support
$con->set_charset("utf8mb4");
