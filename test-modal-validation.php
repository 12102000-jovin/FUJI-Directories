<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../db_connect.php");

$config = include('../config.php');
$serverAddress = $config['server_address'];
?>