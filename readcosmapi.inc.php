<?php
class ReadCosmAPI {
	private $host = 'api.cosm.com';
	private $port = '8081';
	private $url = '/v2/feeds/';
    private $user = 'froehr';
	private $password = 'B@chelor2012';
	private $timeout = 30;
	
	private $errno;
	private $errstr;
	
	private $fp;
	private $request = '';
	
    public function __construct($feedid) {
		$this->fp = fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);
		
		if ( $this->fp ) {
			$this->request = "GET ".$this->url.$feedid.".xml HTTP/1.1\r\n";
			$this->request .= "Host: ".$this->user.':'.$this->password.'@'.$this->host."\r\n";
			$this->request .= "Connection: Close\r\n\r\n";
		}
    }
	
	public function read() {
		fwrite($this->fp, $this->request);
		
		$data = '';
		while ( ! feof($this->fp) ) {
			$data .= fgets($this->fp, 128);
		}
		return $data;
	}
	
	public function __destruct() {
		fclose($this->fp);
	}
}
?>