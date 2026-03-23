<?php
// config/db.php

$host = "localhost";
$user = "root";      // XAMPP default username 
$pass = "";          // XAMPP default password (usually empty) 
$dbname = "venue_rental_db"; // Please replace with the name of the database you created in phpMyAdmin

// build connection
$conn = new mysqli($host, $user, $pass, $dbname);

// check connection
if ($conn->connect_error) {
    // In production, you should not display detailed error messages to users, but keep it for development purposes
    die("Database Connection Failed: " . $conn->connect_error);
}

// set database charset to prevent Chinese character corruption
$conn->set_charset("utf8mb4");