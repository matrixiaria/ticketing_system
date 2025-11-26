<?php
require_once "config.php";

if ($conn) {
    echo "Database connected successfully!";
} else {
    echo "Database connection failed!";
}
?>
