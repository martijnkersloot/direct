<?php
$mysqli = new mysqli('127.0.0.1', 'root', 'root', 'snomedct');

if ($mysqli->connect_errno) {
    echo "Error: Failed to make a MySQL connection.\n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}
?>