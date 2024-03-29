<?php
	class CosmAPI {
		private $url = 'http://api.cosm.com/v2/feeds';
		private $api_key = 'S_fFBZ0WcgkikDf29YcwEnVtLmiSAKx1RmgvUFQ0bndFZz0g';
		private $debug_mode = false;
		
		// creates a new cosm feed with title $title, returns the feed id of the new cosm feed
		public function createFeed($title) {
			// get content for the HTTP POST request to cosm API
			$xml = read_xml('create_feed');
			
			// replace title
			$xml = tpl_replace($xml, 'title', $title);
			
			// open a new connection to the cosm API with curl-module
			$ch = curl_init($this->url.'.xml?key='.$this->api_key);
			
			// set curl options (HTTP POST method with $xml as content, request response with header)
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			
			// parse the HTTP header of the response
			$response_header = explode("\r", curl_exec($ch));
			curl_close($ch);
			
			// search in the response header for the returned cosm feed id and return it
			foreach( $response_header as $value ) {
				if ( strstr(strtolower($value), 'location:') ) {
					return trim(str_replace('Location: '.$this->url.'/', '', $value));
				}
			}
			
			// if no feed id was found, return false
			return false;
		}
		
		// gets the creation time and the time the feed was last updated
		public function getTimeframe($feedid) {
			// set stream options
			$opts['http'] = array('ignore_errors' => true);
			// create the stream context
			$context = stream_context_create($opts);
			
			if ( ! $this->debug_mode ) {
				$requestUrl = $this->url.'/'.$feedid.'.xml?key='.$this->api_key;
				
				// get feed xml using the defined context and request url
				$xml = file_get_contents($requestUrl, false, $context);					
			}
			// debug mode
			else {
				$xml = read_xml('test_feed');
			}
			
			// parse feed xml
			$xml = simplexml_load_string($xml, 'SimpleXMLExtended');
			
			$return = array();
			if ( isset($xml->environment) ) {
				// read date on which the feed was created
				if ( $xml->environment->attribute('created') != '' ) {
					$return['created'] = $xml->environment->attribute('created');
				}
			
				// read date on which the feed was last updated
				if ( $xml->environment->attribute('updated') != '' ) {
					// convert time format to a php timestamp for better comparability
					$return['updated'] = strtotime(substr($xml->environment->attribute('updated'), 0, -11));
				}
				
				// look if one of the datastreams was updated after the last update of the feed
				if ( isset($xml->environment->data) ) {
					foreach ( $xml->environment->data as $data ) {
						if ( isset($data->current_value) ) {
							if ( $data->current_value->attribute('at') != '' ) {
								// convert time format to a php timestamp for better comparability
								$updated = strtotime(substr($data->current_value->attribute('at'), 0, -11));
								
								// if last update of the datastream was after last update of the feed, use it
								if ( $updated > $return['updated'] ) {
									$return['updated'] = $updated;
								}
							}
						}
					}
				}
				
				// re-convert timestamp to the cosm time format and add a time of 4 hours to compensate cosm delay
				$return['updated'] = date('Y-m-d\TH:i:s\Z', $return['updated'] + 14400);
			}
			
			return $return;
		}
		
		// parses cosm feed given in xml format and returns data as an array
		public function parseXML($feedid, $start, $end, $limit, $interval, $duration, $sensors) {
			// set stream options
			$opts['http'] = array('ignore_errors' => true);
			// create the stream context
			$context = stream_context_create($opts);
			
			// set parameters if they are not empty
			if ( $start != '' ) {
				$start = '&start='.$start;
			}
			if ( $end != '' ) {
				$end = '&end='.$end;
			}
			if ( $limit != '' ) {
				$limit = '&limit='.$limit;
			}			
			if ( $interval != '' ) {
				$interval = '&interval='.$interval;
			}
			if ( $duration != '' ) {
				$duration = '&duration='.$duration;
			}
			
			// iterate sensors (given as array)
			foreach ( $sensors as $sensor ) {
				if ( ! $this->debug_mode ) {
					// concatenate request url for the cosm API request via HTTP GET
					$requestUrl = $this->url.'/'.$feedid.'/datastreams/'.$sensor.'.xml?key='.$this->api_key.$start.$end.$limit.$interval.$duration;
					
					// get feed xml using the defined context and request url
					$xml = file_get_contents($requestUrl, false, $context);					
				}
				// debug mode
				else {
					$xml = read_xml('test_feed_'.$sensor);
				}
				
				// parse feed xml
				$xml = simplexml_load_string($xml, 'SimpleXMLExtended');
					
				// check if data is given for this timeframe
				if ( isset($xml->environment->data->datapoints->value) ) {
					foreach ( $xml->environment->data->datapoints->value as $value ) {
						// cut seconds from the time-string and convert it to a php-timestamp
						$at = strtotime(substr($value->attribute('at'), 0, -11));
						
						// save data in the data array, use timestamp as first key, sensor as second key and measured value as array value
						$dataArray[$at][$sensor] = $value->__toString();
					}
				}
			}
			
			// check if sensor data array has been initialized successfully and return it
			if ( isset($dataArray) ) {
				return $dataArray;
			}
			else {
				return false;
			}
		}
	}

	// child class of SimpleXMLElement with method to get attributes of xml tags by their name
	class SimpleXMLExtended extends SimpleXMLElement {
		public function attribute($name) {
			foreach($this->Attributes() as $key=>$val) {
				if ($key == $name)
					return (string) $val;
			}
		}
	}
?>