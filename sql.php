<?php
 class Sql {
	private $server = 'localhost';
    private $user = 'root';
	private $password = '';
	private $database = 'potwc';
	
	private $mysql_connection;
	
    public function __construct() {
        if ( ! ($this->mysql_connection = mysql_connect($this->server, $this->user, $this->password)) ) {
			print 'Connection to database failed';
			die;
		}
		if ( ! mysql_select_db($this->database) ) {
			print 'Selection of database failed';
			die;
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