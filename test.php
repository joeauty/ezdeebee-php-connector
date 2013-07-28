<?php
// MySQL database auth stuff

// used for the local database cache option (you can comment this out if you don't wish to use this option)

// local cache enabled
// $mysql = mysql_connect('localhost', 'username', 'password'); 
// mysql_select_db('database');

// OR...

// local cache disabled
$mysql = null;

// load Ezdeebee PHP class
require_once('ezdeebeeconnector.php');

if ($_GET['ezdb_initconnector']) {
	$ezdeebee = new ezdeebee();
	$jsonobj = json_encode($ezdeebee->init($_GET['ezdeebee_cid'], $mysql));
}
