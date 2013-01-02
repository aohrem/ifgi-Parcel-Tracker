<?php
	class CosmAPI {
		private $url = 'http://api.cosm.com/v2/feeds';
		private $api_key = 'S_fFBZ0WcgkikDf29YcwEnVtLmiSAKx1RmgvUFQ0bndFZz0g';
		private $request_url;
		private $debug_mode = true;
		
		// sends HTTP GET request to the cosm API and returns the answered xml with the feed data
		public function readFeed($feedid, $start, $end, $per_page, $interval, $duration) {
			if ( ! $this->debug_mode ) {
				// set stream options
				$opts = array(
				  'http' => array('ignore_errors' => true)
				);

				// create the stream context
				$context = stream_context_create($opts);
				
				// set parameters if they are not empty
				if ( $start != '' ) {
					$start = '&start='.$start;
				}
				if ( $end != '' ) {
					$end = '&end='.$end;
				}
				if ( $per_page != '' ) {
					$per_page = '&per_page='.$per_page;
				}			
				if ( $interval != '' ) {
					$interval = '&interval='.$interval;
				}
				if ( $duration != '' ) {
					$duration = '&duration='.$duration;
				}
				
				$requestUrl = $this->url.'/'.$feedid.'.xml?key='.$this->api_key.$start.$end.$per_page.$interval.$duration;
				
				// open the file using the defined context
				return file_get_contents($requestUrl, false, $context);
			}
			else {
				return read_xml('test_feed');
			}
		}
		
		// creates a new cosm feed with title $title
		public function createFeed($title) {
			$xml = read_xml('create_feed');
			$xml = tpl_replace($xml, 'title', $title);
			
			$ch = curl_init($this->url.'.xml?key='.$this->api_key);
			
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			$response_header = explode("\r", curl_exec($ch));
			curl_close($ch);
			
			// search in the response header for the returned cosm feed id
			foreach( $response_header as $value ) {
				if ( strstr(strtolower($value), 'location:') ) {
					return trim(str_replace('Location: '.$this->url.'/', '', $value));
				}
			}
			
			return false;
		}
		
		// parses cosm feed given in xml format and returns data as an array
		public function parseXML($xml) {
			// load xml string as object
			$xml = simplexml_load_string($xml, 'simple_xml_extended');
			
			if ( isset($xml->environment->data) ) {
				// iterate datastreams
				foreach ( $xml->environment->data as $data ) {
					// check the datafeed id for a given sensor type
					$sensor = $data->attribute('id');
					
					// check if data is given for this timeframe
					if ( ! isset($data->datapoints->value) ) {
					}
					// fill data array
					else {
						foreach ( $data->datapoints->value as $value ) {
							// cut seconds from the time-string and convert it to a php-timestamp
							$at = strtotime(substr($value->attribute('at'), 0, -11));
							
							// save data in the data array, use timestamp as first key, sensor as second key and measured value as array value
							$dataArray[$at][$sensor] = $value->__toString();
						}
					}
				}
			}
			
			if ( ! isset($dataArray) ) {
				return false;
			}
			else {
				return $dataArray;
			}
		}
	}

	// child class of SimpleXMLElement with method to get attributes of xml tags by their name
	class simple_xml_extended extends SimpleXMLElement {
		public function attribute($name) {
			foreach($this->Attributes() as $key=>$val) {
				if ($key == $name)
					return (string) $val;
			}
		}
	}
?>