<?php
include('sql.inc.php');
include('functions.inc.php');

// load main template file
$tpl = read_tpl('main');

// get current site and set content template
if ( isset($_GET['s']) ) {
	$s = mysql_real_escape_string($_GET['s']);
	$content_tpl = $s;
}
else {
	$s = '';
	$content_tpl = 'home';
}

// get current page and extend content template if it's set
if ( isset($_GET['p']) ) {
	$p = mysql_real_escape_string($_GET['p']);
	$content_tpl .= '_'.$p;
}
else {
	$p = '';
}

// replace content placeholder with content template file
$tpl = tpl_replace($tpl, 'content', read_tpl($content_tpl));

// differentiate between sites
switch ( $s ) {
	// home page
	case '':	
	break;
	// registration process
	case 'register':
		switch ( $p ) {
			case '':
				// initialise template placeholder replacements with empty strings
				$errormsg = '';
				$redfieldto = '';
				$redfieldfrom = '';
				$valueto = '';
				$valuefrom = '';
				$valuetitle = '';
				$description = '';
				
				// if all necessary post data is there, check data
				if ( isset($_POST['title']) && isset($_POST['to']) && isset($_POST['from']) && isset($_POST['description']) ) {
					$title = mysql_real_escape_string($_POST['title']);
					$to = mysql_real_escape_string($_POST['to']);
					$from = mysql_real_escape_string($_POST['from']);
					$description = mysql_real_escape_string($_POST['description']);
					
					// check, if mandatory fields are not filled
					if ( $to == '' || $from == '' ) {
						$errormsg = 'You did not fill all mandatory fields.';
						$valueto = ' value=\''.$to.'\'';
						$valuefrom = ' value=\''.$from.'\'';
						
						if ( $to == '' ) {
							$redfieldto = ' redfield';
						}
						if ( $from == '' ) {
							$redfieldfrom = ' redfield';
						}
					}
					// if everything is right, insert data into database table
					else {
						if ($title == '')
							{
							$title = date('Ymdhis');
							}
						include('cosm_api.inc.php');
						$cosm_api = new CosmAPI();
						$feedid = $cosm_api->createFeed($title);
						
						// new database connection
						$db = new Sql();
						$db->query('INSERT INTO `potwc`.`parcels` ( `feedid`, `title`, `from`, `to`, `description`, `time` )
							VALUES (\''.$feedid.'\', \''.$title.'\', \''.$from.'\', \''.$to.'\', \''.$description.'\', \''.date('YmdHis',time()).'\')');
						
						// redirect to the "parcel can be send" site
						header('Location: index.php?s=register&p=finished&fid='.$feedid);
					}
				}
				
				// replace template placeholders
				$tpl = tpl_replace($tpl, 'errormsg', '<p>'.$errormsg.'</p>');
				$tpl = tpl_replace($tpl, 'redfieldto', $redfieldto);
				$tpl = tpl_replace($tpl, 'redfieldfrom', $redfieldfrom);
				$tpl = tpl_replace($tpl, 'valueto', $valueto);
				$tpl = tpl_replace($tpl, 'valuefrom', $valuefrom);
				$tpl = tpl_replace($tpl, 'valuetitle', $valuetitle);
				$tpl = tpl_replace($tpl, 'valuedescription', $description);
			break;
			case 'finished':
				$tpl = replace_feedid($tpl);
			break;
		}
	break;
	// package details
	case 'details':	
		$feedid = trim(htmlentities($_GET['fid']));
		$db = new Sql();
		$row = $db->fetch('SELECT * FROM `potwc`.`parcels` WHERE `feedid`=\''.$feedid.'\'');
		
		// give an error, if parcel id was not found or the parcel id is not numeric
		if ( ! is_numeric($feedid) || ! isset($row->feedid) ) {
			header('Location: index.php?s=error&p=feed_id_not_found&fid='.$feedid);
		}
		else {
			// replace template placeholdes with values from the database
			$tpl = tpl_replace($tpl, 'information', read_tpl('information'));
			$tpl = tpl_replace($tpl, 'title', htmlentities($row->title));
			$tpl = tpl_replace($tpl, 'time', date('d.m.Y, g:i a', strtotime($row->time)));
			$tpl = tpl_replace($tpl, 'from', htmlentities($row->from));
			$tpl = tpl_replace($tpl, 'to', htmlentities($row->to));
			$tpl = tpl_replace($tpl, 'description', nl2br(htmlentities($row->description)));
			
			// if there's no or a wrong page set, use map page as default
			if ( $p != 'stats' && $p != 'diagram' && $p != 'map' && $p != 'events' ) {
				$p = 'map';
			}
			
			$css_active = " class='active'";
			$details_active = '';
			$diagram_active = '';
			$map_active = '';
			$events_active = '';
			
			// get data from cosm API
			include('cosm_api.inc.php');
			$cosmAPI = new CosmAPI();
			
			$timeframe = $cosmAPI->getTimeframe($feedid);
			$created = $timeframe['created'];
			$updated = $timeframe['updated'];
			
			// get start and end time
			if ( isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['start_time']) && isset($_GET['end_time']) ) {
				$times = '&amp;start_date='.$_GET['start_date'].'&amp;start_time='.$_GET['start_time'].'&amp;end_date='.$_GET['end_date'].'&amp;end_time='.$_GET['end_time'];
				$start = strtotime($_GET['start_date'].' '.$_GET['start_time']);
				$end = strtotime($_GET['end_date'].' '.$_GET['end_time']);
			}
			else {
				$times = '';
				$start = strtotime(substr($created, 0, -1));
				$end = strtotime(substr($updated, 0, -1));
			}
			
			// set parameters for the cosm-API request
			$timedifference = $end - $start;
			$value_start_date = date('Y-m-d', $start);
			$value_end_date = date('Y-m-d', $end);
			$value_start_time = date('H:i', $start);
			$value_end_time = date('H:i', $end);
			$min_date = date('Y-m-d', strtotime(substr($created, 0, -1)));
			$max_date = date('Y-m-d', strtotime(substr($updated, 0, -1)));
			$start = date('Y-m-d\TH:i:s\Z', $start);
			$end = date('Y-m-d\TH:i:s\Z', $end);
			
			$intervals = array(
				21600 => 0,			// 6 hours
				43200 => 30,		// 12 hours
				86400 => 60,		// 24 hours
				432000 => 300,		// 5 days
				1209600 => 900,		// 14 days
				2678400 => 1800,	// 31 days
				7776000 => 10800,	// 90 days
				15552000 => 21600	// 180 days
			);
			
			// set interval
			$interval = 21600;
			foreach ( $intervals as $key => $val ) {
				if ( $timedifference < $key ) {
					$interval = $val;
				}
			}
			$limit = 500;
			
			// show stats, diagram or map
			switch ( $p ) {
				case 'stats':
					// replace start/end values/min/max
					$tpl = tpl_replace($tpl, 'start_date_value', $value_start_date);
					$tpl = tpl_replace($tpl, 'start_time_value', $value_start_time);
					$tpl = tpl_replace($tpl, 'end_date_value', $value_end_date);
					$tpl = tpl_replace($tpl, 'end_time_value', $value_end_time);
					$tpl = tpl_replace($tpl, 'date_min', $min_date);
					$tpl = tpl_replace($tpl, 'date_max', $max_date);
					
					// define sensor abbreviations
					$sensors = array('lat', 'lon', 'tmp', 'hum');
			
					// parse xml string
					$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '', $sensors);
					
					if ( isset($_GET['time']) && is_numeric($_GET['time']) ) {
						$active_time = $_GET['time'];
					}
					else {
						$active_time = 0;
					}
					
					$details_active = $css_active;
					
					if ( $dataArray ) {
						// sort sensor data by timestamp (keys of the data array)
						ksort($dataArray, SORT_NUMERIC);
						
						// iterate sensor data
						foreach ( $dataArray as $time => $val ) {
							// if there is no data, show a -
							if ( ! isset($val['lat']) ) { $val['lat'] = '-'; }
							if ( ! isset($val['lon']) ) { $val['lon'] = '-'; }
							if ( ! isset($val['tmp']) ) { $val['tmp'] = '-'; }
							if ( ! isset($val['hum']) ) { $val['hum'] = '-'; }
							
							// check if the row should be marked as active
							if ( $time == $active_time ) {
								$row_active = " class='row_active'";
							}
							else {
								$row_active = '';
							}
							
							// copy table row and fill in sensor data for one timestamp
							$tpl = copy_code($tpl, 'tableRow');
							$tpl = tpl_replace_once($tpl, 'timestamp', $time);
							$tpl = tpl_replace_once($tpl, 'timestamp', $time);
							$tpl = tpl_replace_once($tpl, 't', date('d.m.Y, g:i a', $time));
							$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
							$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
							$tpl = tpl_replace_once($tpl, 'temp', $val['tmp']);
							$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
							
							// replace row active if row is active
							for ( $i = 0; $i < 6; $i++ ) {
								$tpl = tpl_replace_once($tpl, 'row_active', $row_active);
							}
						}
					}
					// delete the last row
					$tpl = clean_code($tpl, 'tableRow');
				break;
				case 'diagram':
					// replace start/end values/min/max
					$tpl = tpl_replace($tpl, 'start_date_value', $value_start_date);
					$tpl = tpl_replace($tpl, 'start_time_value', $value_start_time);
					$tpl = tpl_replace($tpl, 'end_date_value', $value_end_date);
					$tpl = tpl_replace($tpl, 'end_time_value', $value_end_time);
					$tpl = tpl_replace($tpl, 'date_min', $min_date);
					$tpl = tpl_replace($tpl, 'date_max', $max_date);
					
					// define sensor abbreviations
					$sensors = array('tmp', 'hum', 'acc');
			
					// parse xml string
					$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '', $sensors);
					
					$diagram_active = $css_active;
					
					if ( $dataArray ) {
						// sort sensor data by timestamp (keys of the data array)
						ksort($dataArray, SORT_NUMERIC);
						
						// iterate sensor data
						$i = 1;
						foreach ( $dataArray as $time => $val ) {
							// if there is no data, set value to 0
							if ( ! isset($val['tmp']) ) { $val['tmp'] = 'null'; }
							if ( ! isset($val['hum']) ) { $val['hum'] = 'null'; }
							if ( ! isset($val['acc']) ) { $val['acc'] = 'null'; }
							
							// copy table row and fill in sensor data for one timestamp
							$tpl = copy_code($tpl, 'diagram_data');
							$tpl = tpl_replace_once($tpl, 't', date('Y, m-1, d, H, i', $time));
							$tpl = tpl_replace_once($tpl, 'temp', $val['tmp']);
							$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y, g:i a', $time));
							$tpl = tpl_replace_once($tpl, 'temp', $val['tmp']);
							$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
							$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y, g:i a', $time));
							$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
							$tpl = tpl_replace_once($tpl, 'acc', $val['acc']);
							$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y, g:i a', $time));
							$tpl = tpl_replace_once($tpl, 'acc', $val['acc']);
							$tpl = tpl_replace_once($tpl, 'timestamp', $time);
							
							if ( count($dataArray) == $i ) {
								$tpl = tpl_replace_once($tpl, ',', '');
							}
							else {
								$tpl = tpl_replace_once($tpl, ',', ',');
							}
							$i++;
						}
					}
					// delete the last row
					$tpl = clean_code($tpl, 'diagram_data');
				break;
				case 'map':
					// define sensor abbreviations
					$sensors = array('lat', 'lon', 'tmp', 'hum');
			
					// parse xml string
					$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '', $sensors);
					
					$map_active = $css_active;
					
					if ( $dataArray ) {
						// sort sensor data by timestamp (keys of the data array)
						ksort($dataArray, SORT_NUMERIC);
						
						// iterate sensor data
						foreach ( $dataArray as $time => $val ) {
							// if there is no data, set value to 0
							if ( ! isset($val['lat']) || ! isset($val['lon']) ) { continue; }
							if ( ! isset($val['tmp']) ) { $val['tmp'] = 'null'; }
							else { $val['tmp'] = floatval($val['tmp']); }
							if ( ! isset($val['hum']) ) { $val['hum'] = 'null'; }
							else { $val['hum'] = floatval($val['hum']); }
							
							// copy table row and fill in sensor data for one timestamp
							$tpl = copy_code($tpl, 'map_point');
							$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
							$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
							$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
							$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
							$tpl = tpl_replace_once($tpl, 'markertime', date("d.m.Y, g:i a",$time));
							$tpl = tpl_replace_once($tpl, 'markertime', date("d.m.Y, g:i a",$time));
							if ( $val['tmp']!= "null" ) {
								$tpl = tpl_replace_once($tpl, 'temp', "<br><u>Temp:</u> ".$val['tmp']."&deg;C");
							}
							else {
								$tpl = tpl_replace_once($tpl, 'temp', "");
							}
							if ( $val['hum']!= "null" ) {
								$tpl = tpl_replace_once($tpl, 'humid', "<br><u>Hum:</u> ".$val['hum']."%");
							}
							else {
								$tpl = tpl_replace_once($tpl, 'humid', "");
							}
							$tpl = tpl_replace_once($tpl, 'timeanker', $time);
							$tpl = tpl_replace_once($tpl, 'timeanker', $time);
						}
					}
					// delete the last row
					$tpl = clean_code($tpl, 'map_point');
				break;
				case 'events':
					// define sensor abbreviations
					$sensors = array('bri', 'acc');
			
					// parse xml string
					$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '', $sensors);
					
					$events_active = $css_active;
					
					if ( $dataArray ) {
						// sort sensor data by timestamp (keys of the data array)
						ksort($dataArray, SORT_NUMERIC);
						
						$parcel_opened = false;
						$parcel_crashed = false;
						
						// iterate sensor data
						foreach ( $dataArray as $time => $val ) {
							// if there is no brightness or acceleration data, continue with next loop circle
							if ( ! isset($val['bri']) && ! isset($val['acc']) ) {
								continue;
							}
							else {
								if ( isset($val['bri']) ) {
									$parcel_opened = true;
									
									// copy table row and fill in sensor data for one timestamp
									$tpl = copy_code($tpl, 'opening_events');
									$tpl = tpl_replace_once($tpl, 'opening_timestamp', $time);
									$tpl = tpl_replace_once($tpl, 'opening_timestamp', $time);
									$tpl = tpl_replace_once($tpl, 'opening_event', 'Parcel opened on '.date('d.m.Y, g:i a', $time).'.');
								}
								
								if ( isset($val['acc']) ) {
									$parcel_crashed = true;
									
									$tpl = copy_code($tpl, 'crash_events');
									$tpl = tpl_replace_once($tpl, 'crash_timestamp', $time);
									$tpl = tpl_replace_once($tpl, 'crash_timestamp', $time);
									$tpl = tpl_replace_once($tpl, 'crash_event', 'Potential crash with '. $val['acc'] .' m/s<sup>2</sup> detected on '.date('d.m.Y, g:i a', $time).'.');
								}
							}
							
							
						}
					}
					// delete the last row
					$tpl = clean_code($tpl, 'opening_events');
					$tpl = clean_code($tpl, 'crash_events');
					
					if ( $parcel_opened ) {
						$tpl = tpl_replace($tpl, 'state_open', 'open');
						$tpl = tpl_replace($tpl, 'state_open_text', 'Parcel opened');
						$tpl = tpl_replace($tpl, 'bool_opening_events', '');
					}
					else {
						$tpl = tpl_replace($tpl, 'state_open', 'closed');
						$tpl = tpl_replace($tpl, 'state_open_text', 'Parcel closed');
						$tpl = tpl_replace($tpl, 'bool_opening_events', '<li>No opening events.</li>');
					}
					
					if ( $parcel_crashed ) {
						$tpl = tpl_replace($tpl, 'bool_crash_events', '');
					}
					else {
						$tpl = tpl_replace($tpl, 'bool_crash_events', '<li>No potential crash events.</li>');
					}
				break;
			}
			
			$tpl = tpl_replace($tpl, 'feedid', $feedid);
			$tpl = tpl_replace($tpl, 'details_active', $details_active);
			$tpl = tpl_replace($tpl, 'diagram_active', $diagram_active);
			$tpl = tpl_replace($tpl, 'map_active', $map_active);
			$tpl = tpl_replace($tpl, 'events_active', $events_active);
			$tpl = tpl_replace($tpl, 'times', $times);
		}
	break;
	// error handling
	case 'error':
		switch ( $p ) {
			case 'feed_id_not_found':
				$tpl = replace_feedid($tpl);
			break;
		}
	break;
}

// print whole webseite
print $tpl;
?>