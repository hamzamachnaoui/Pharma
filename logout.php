<?php
require_once 'config/database.php';
unset($_SESSION['user']);
session_destroy();
header('Location: login.php');
exit;
