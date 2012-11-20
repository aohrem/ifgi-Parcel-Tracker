<?php
class CosmAPI {
	private $url = 'api.cosm.com/v2/feeds/';
    private $user = 'froehr';
	private $password = 'Bachelor2012';
	private $api_key = 'S_fFBZ0WcgkikDf29YcwEnVtLmiSAKx1RmgvUFQ0bndFZz0g';
	private $request_url;
	
	public function __construct() {
		$this->request_url = 'http://'.$this->user.':'.$this->password.'@'.$this->url;
	}
	
	public function readFeed($feedid) {
		// set stream options
		$opts = array(
		  'http' => array('ignore_errors' => true)
		);

		// create the stream context
		$context = stream_context_create($opts);

		// open the file using the defined context
		return file_get_contents($this->request_url.$feedid.'.xml', false, $context);
	}
	
	public function createFeed() {
		$xml = read_xml('create_feed');
		
		$ch = curl_init($this->url.'?key='.$this->api_key);
 
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
}
?>