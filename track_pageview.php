<?php
/**
 * OpenShift PHP S2I Example
 *
 * Increment counter via AJAX, only count human pageviews
 */
define('BASEPATH', getenv('HOME').'/');
require_once BASEPATH.'connect.php';

// Track pageview for ?path={$page_path}
$page = track_pageview($_GET['path']);

// Set JSON response headers
header('Content-Type: application/json');

// Output response to JSON
echo json_encode($page);