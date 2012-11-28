<?php
class CosmAPI {
	private $url = 'http://api.cosm.com/v2/feeds';
	private $api_key = 'S_fFBZ0WcgkikDf29YcwEnVtLmiSAKx1RmgvUFQ0bndFZz0g';
	private $request_url;
	
	public function readFeed($feedid, $start, $end, $interval) {
		// set stream options
		$opts = array(
		  'http' => array('ignore_errors' => true)
		);

		// create the stream context
		$context = stream_context_create($opts);
		
		if ( $start != '' ) {
			$start = '&start='.$start;
		}
		if ( $end != '' ) {
			$end = '&end='.$end;
		}
		if ( $interval != '' ) {
			$interval = '&interval='.$interval;
		}
		
		// open the file using the defined context
		return file_get_contents($this->url.'/'.$feedid.'.xml?key='.$this->api_key.$start.$end.$interval, false, $context);
	}
	
	public function createFeed() {
		$xml = read_xml('create_feed');
		$xml = tpl_replace($xml, 'title', date('YmdHis', time()));
		
		$ch = curl_init($this->url.'.xml?key='.$this->api_key);
		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$response_header = explode("\r", curl_exec($ch));
		curl_close($ch);
		
		foreach( $response_header as $value ) {
			if ( strstr(strtolower($value), 'location:') ) {
				return trim(str_replace('Location: '.$this->url.'/', '', $value));
			}
		}
		
		return false;
	}
}
?>