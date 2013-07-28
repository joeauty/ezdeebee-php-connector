Ezdeebee PHP Connector
======================

Requirements
------------

* PHP 4.0.2 or greater (with the cURL extension enabled, as it should be by default)
* Support in your application to accept HTTP GET variables in some manner

Configuration
=============

In config.php provide your Ezdeebee Site ID (found in Ezdeebee's "Settings" page) within "ezdeebee_site_id", and set the "ezdeebee_localcache" variable as desired. If you wish to use the local cache you'll need to be able to connect to your local database and have necessary insert privleges (more on this below)

Trigger an Ezdeebee Fetch Request
=================================

On the page that will fetch your data from Ezdeebee you'll need to allow it to recognize two HTTP get variables:

	$_GET['ezdb_initconnector']
	$_GET['ezdeebee_cid']

The first variable is simply a boolean flag, and the second the numerical ID of the connector you want to access, which you can retrieve from within your Ezdeebee data collection within "Connector Options", located within "Data Collection Operations/Settings". A sample URL might look like:

	http://yoursite.com/products/?ezdb_initconnector=1&ezdeebee_cid=214

Next, check out the included test.php to see how these variables are received and how Ezdeebee is instantiated. If you ish to use the local cache feature and your application doesn't authenticate and establish a connection to your database you'll want to uncomment the mysql_connect() and mysql_select_db() lines, provide your own authentication credentials, and of course uncomment:

	$mysql = null;

As you can see in test.php, Ezdeebee is instantitated as follows:

	$jsonobj = json_encode($ezdeebee->init($_GET['ezdeebee_cid'], $mysql));
	
where $mysql is the database resource returned by mysql_connect() (although you can omit this if you do not wish to use the local cache feature). If your site ID is included in your config.php file and is valid, your connector is instantiating the Ezdeebee class' init() function properly, and within Ezdeebee you have included the domain of the site you wish to provide access to your data in the "Settings" page access list, you'll find that doing a:

	print_r($jsonobj->dbdata);
	
returns a JSON object containing all of the data contained within the data collection which you can use as you wish. If the local cache feature has been enabled you should also find your cache tables in your database with an "ezdb_" prefix.

If you wish to connect to multiple Ezdeebee connectors on a single page you can just keep on calling $ezdeebee->init() as necessary with the connector IDs of these respective connectors.

Database Permissions
====================

If you are using the local cache feature you'll need to grant create, drop, select, update and insert privileges to your database user.