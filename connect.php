<?php
/**
 * OpenShift PHP S2I Example
 *
 * Connect to database service OR local SQLite database
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Connect to Database
 *
 * Establishes a connection to a MySQL Database, if present.
 * Creates a new pageview counter database table if non-existent.
 *
 * @env	string	$DATABASE_DRIVER	                     PDO database driver, set by template param
 * @env	string	$DATABASE_SERVICE_NAME	               Name of your OpenShift database service, set by template param
 * @env	string	${DATABASE_SERVICE_NAME}_SERVICE_HOST	 Database service host, set automatically by OpenShift
 * @env	string	${DATABASE_SERVICE_NAME}_SERVICE_PORT	 Database service port, set automatically by OpenShift
 * @env	string	$DATABASE_NAME	                       Name of your database, set by template param
 * @env	string	$DATABASE_USER	                       Database username, set by template param
 * @env	string	$DATABASE_PASSWORD	                   Database password, set by template param
 */

// Database configuration
$db_conf = array(
	'driver'   => getenv('DATABASE_DRIVER'),
	'host'     => getenv(strtoupper(getenv("DATABASE_SERVICE_NAME")).'_SERVICE_HOST'),
	'port'     => getenv(strtoupper(getenv("DATABASE_SERVICE_NAME")).'_SERVICE_PORT'),
	'database' => getenv('DATABASE_NAME'),
	'username' => getenv('DATABASE_USER'),
	'password' => getenv('DATABASE_PASSWORD'),
);

// Table configuration
$table_conf => array(
	'name' => 'pages',
	'columns' => array(
		'path' => 'TEXT PRIMARY KEY', 
		'views' => 'INT DEFAULT 0'
	)
);

// Database drivers this example is designed to support
$db_drivers = array(
	'mysql',
	//'pgsql', 
	'sqlite'
);
if ($db_conf['driver'] && !in_array($db_conf['driver'], $db_drivers)) {
	header('HTTP/1.1 503 Service Unavailable');
	die("Connection failed: Invalid database driver ({$db_conf['driver']}). Valid drivers include: " . implode(', ', $db_drivers);
}

// Establish database $conn
try {
	switch ($db_conf['driver']) {
		case 'mysql':
			$dsn = "mysql:dbname={$db_conf['database']};host={$db_conf['host']};port={$db_conf['port']}";
			$conn = new PDO($dsn, $db_conf['username'], $db_conf['password']);
			break;
		//case 'pgsql':
		//	$dsn = "pgsql:dbname={$db_conf['database']};host={$db_conf['host']};port={$db_conf['port']}";
		//	$conn = new PDO($dsn, $db_conf['username'], $db_conf['password']);
		//	break;
		default: 
			$dsn = 'sqlite:'.BASEPATH.'database.sqlite';
			$conn = new PDO($dsn);
	}
} catch (PDOException $e) {
  header('HTTP/1.1 503 Service Unavailable');
	die("Connection failed: ({$e->getCode()}) {$e->getMessage()}";
}

// Flatten $table_conf[columns] assoc array to string
$column_defintions = implode(', ', array_map(function($col_name, $col_props){ return $col_name." ".$col_props; }, $table_conf['columns']));

// Create table if none exists
$conn->query("CREATE TABLE IF NOT EXISTS {$table_conf['name']} ({$column_defintions});");

/**
 * Helper Functions
 */
// ------------------------------------------------------------------------

/**
 * Page Exists
 *
 * Checks database for existing page record.
 *
 * @param	string	$path		Page path (i.e. /index.php)
 * @return	bool
 */
function page_exists($path) {
	$record_count = $conn->prepare("SELECT Count(*) FROM {$db_conf['table']} WHERE path = ?;")
													->execute(array($path))
													->fetchColumn();
	return $record_count ? true : false;
}

/**
 * Create Page
 *
 * Creates a new page record for a given path.
 *
 * @param	string	$path		Page path (i.e. /index.php)
 * @return	array
 */
function create_page($path) {
	$conn->prepare("INSERT INTO {$db_conf['table']} (path) values (?);")
				->execute(array($path));
	return array('path' => $path, 'views' => 0);
}

/**
 * Get Page
 *
 * Returns new or existing page record.
 *
 * @param	string	$path		Page path (i.e. /index.php)
 * @return	array
 */
function get_page($path) {
	if (page_exists($path)) {
		$page = $conn->prepare("SELECT * FROM {$db_conf['table']} WHERE path = ?;")
										->execute(array($path))
										->fetch(PDO::FETCH_ASSOC);
	} else {
		$page = create_page($path);
	}
	return $page;
}

/**
 * Track Pageview
 *
 * Increments view count for given page.
 *
 * @param	string	$path		Page path (i.e. /index.php)
 * @return	array
 */
function track_pageview($path) {
	$page = get_page($path);
	$page['views'] += 1;
	$conn->prepare("UPDATE {$db_conf['table']} SET views = ? WHERE path = ?;")
				->execute(array($page['views'], $path));
	return $page;
}