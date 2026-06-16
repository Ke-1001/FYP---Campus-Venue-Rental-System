<?php
// This section handles db logic.
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "rental_venue";


$conn = new mysqli($host, $user, $pass, $dbname);


if ($conn->connect_error)
{

    die("Database Connection Failed: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");
