<?php
class ReadCosmAPI {
	private $url = 'api.cosm.com/v2/feeds/';
    private $user = 'froehr';
	private $password = 'Bachelor2012';
	
	private $feedid;
	
	// set the feedid field in constructor
    public function __construct($feedid) {
		$this->feedid = $feedid;
    }
	
	public function read() {
		// set stream options
		$opts = array(
		  'http' => array('ignore_errors' => true)
		);

		// create the stream context
		$context = stream_context_create($opts);

		// open the file using the defined context
		return file_get_contents('http://'.$this->user.':'.$this->password.'@'.$this->url.$this->feedid.'.xml', false, $context);
	}
}
?>