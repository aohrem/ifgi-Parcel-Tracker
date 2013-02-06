<?php
// class for the database management
class Sql {
	private $server = 'localhost';
	private $user = 'root';
	private $password = '';
	private $database = 'ipt';
	
	private $mysql_connection;
	
	// initializes a mysql connection with data given in the attributes
	public function __construct() {
		if ( ! ($this->mysql_connection = mysql_connect($this->server, $this->user, $this->password)) ) {
			die('Connection to database failed');
		}
		if ( ! mysql_select_db($this->database) ) {
			die('Connection to database failed');
		}
	}
	
	// shortcut for the mysql_fetch_object function
	public function fetch($query) {
		return mysql_fetch_object(mysql_query($query));
	}
	
	// shortcut for the mysql_query function
	public function query($query) {
		return mysql_query($query);
	}
	
	// closes the mysql connection
	public function __destruct() {
		mysql_close($this->mysql_connection);
	}
}
?>