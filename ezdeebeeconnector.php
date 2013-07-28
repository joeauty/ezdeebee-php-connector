<?php
// read config file
include 'config.php';

class ezdeebee {
	
	function init($cid, $mysql = null) {
		global $config;
		$ezdbdomain = "https://ezdeebee.com/app";

		$url = $ezdbdomain . "/dbmanager/" . $cid . "/json";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'sitetoken=' . $config['ezdeebee_site_id'] . '&cid=' . $cid . '&domain=' . $_SERVER['SERVER_NAME'] . '&HTTPS=' . $_GET['HTTPS']);
		$result = curl_exec($ch);
		curl_close($ch);

		if ($config['ezdeebee_localcache']) {
			// cURL SQL regen commands
	
			$resultsjson = json_decode($result);
			$regdbID = $resultsjson->dbmeta[0]->regdbID;

			// create modifications table, if necessary
			$query = mysql_query("CREATE TABLE IF NOT EXISTS `ezdb__modifications` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `registereddb` int(11) DEFAULT NULL,
			  `lastmodified` datetime DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1;", $mysql);
			
			// get remote last modified date
			$url = $ezdbdomain . '/dbmanager/' . $config['ezdeebee_site_id'] . '/lastmodified';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'sitetoken=' . $config['ezdeebee_site_id'] . '&regdbID=' . $regdbID . '&domain=' . $_SERVER['SERVER_NAME'] . '&HTTPS=' . $_GET['HTTPS']);
			$remotelastmodified = curl_exec($ch);
			curl_close($ch);
	
			// get local last modified date
			$prepquery = mysql_query('SELECT lastmodified FROM ezdb__modifications WHERE registereddb = "' . $regdbID . '"', $mysql);
			$query = mysql_fetch_array($prepquery);
			
			$locallastmodified = strtotime($query['lastmodified']);
			$action = (mysql_num_rows($prepquery)) ? 'update' : 'insert';

			if (!mysql_num_rows($prepquery) || $locallastmodified < $remotelastmodified) {
				// do sync
				$url = $ezdbdomain . '/dbmanager/' . $config['ezdeebee_site_id'] . '/dump';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'sitetoken=' . $config['ezdeebee_site_id'] . '&regdbID=' . $regdbID . '&domain=' . $_SERVER['SERVER_NAME'] . '&HTTPS=' . $_GET['HTTPS']);
				$sql = curl_exec($ch);
				curl_close($ch);
		
				$sql = preg_replace('/DROP TABLE IF EXISTS (.+)?/','DROP TABLE IF EXISTS ezdb_$1', $sql);
				$sql = preg_replace('/CREATE TABLE `(.+)?`/','CREATE TABLE `ezdb_$1`', $sql);
				// add ezdb prefix to insert table commands
				$sql = preg_replace('/INSERT INTO (.+)?\s\(/','INSERT INTO ezdb_$1 (', $sql);
		
				$sqlarray = explode(";\n", $sql);
				foreach ($sqlarray as $thissql) {
					if (!$thissql) { continue; }
					$query = mysql_query($thissql, $mysql);	
				}
		
				// update local last modified
				if ($action == "insert") {
					// insert new modification date
					mysql_query("insert into `ezdb__modifications` (`lastmodified`, `registereddb`) values ('" . date('Y-m-d H:i:s', $remotelastmodified) . "', $regdbID)", $mysql);
				}
				else if ($action == "update") {
					// update modification date
					mysql_query("update `ezdb__modifications` set `lastmodified` = '" . date('Y-m-d H:i:s', $remotelastmodified . "' where registereddb = '" . $regdbID . "'"), $mysql);
				}
			}
		}
		
		return $result;	
	}
}
