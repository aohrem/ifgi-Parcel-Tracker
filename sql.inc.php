<?php
class Sql {
	private $server = 'localhost';
    private $user = 'root';
	private $password = '';
	private $database = 'potwc';
	
	private $mysql_connection;
	
    public function __construct() {
        if ( ! ($this->mysql_connection = mysql_connect($this->server, $this->user, $this->password)) ) {
			die('Connection to database failed');
		}
		if ( ! mysql_select_db($this->database) ) {
			die('Connection to database failed');
		}
    }
	
	public function fetch($query) {
		return mysql_fetch_object(mysql_query($query));
	}
	
	public function query($query) {
		return mysql_query($query);
	}
	
	public function __destruct() {
		mysql_close($this->mysql_connection);
	}
}
?>