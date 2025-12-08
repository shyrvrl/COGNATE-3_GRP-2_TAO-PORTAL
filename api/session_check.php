<?php
session_start();
// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: LoginPage.html');
    exit;
}
?>