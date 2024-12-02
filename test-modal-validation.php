<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once ("../db_connect.php");

$config = include('../db_connect.php');

$config = include('../config.php');
$serverAddress = $config['server_address'];
$projectName = $config['project_name'];

// Get serch term
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Sorting Variables
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Check if filters are applied
$filters = [];

// Build base SQL query with role-based filtering
