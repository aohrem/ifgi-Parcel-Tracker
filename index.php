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
			
			// set parameters for the cosm-API request
			$start = date('Y-m-d\TH:i:s\Z', time() - 21600);	// 21600 = 6 hours, 604800 = one week, 2419200 = 4 weeks
			$end = date('Y-m-d\TH:i:s\Z', time());
			$interval = 60;
			$limit = 500;
			
			// parse xml string
			$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '');
			
			// show stats, diagram or map
			switch ( $p ) {
				case 'stats':
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
					$map_active = $css_active;
					
					if ( $dataArray ) {
						// sort sensor data by timestamp (keys of the data array)
						ksort($dataArray, SORT_NUMERIC);
						
						// iterate sensor data
						foreach ( $dataArray as $time => $val ) {
							// if there is no data, set value to 0
							if ( ! isset($val['lat']) || ! isset($val['lon']) ) { continue; }
							
							// copy table row and fill in sensor data for one timestamp
							$tpl = copy_code($tpl, 'map_point');
							$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
							$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
							$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
							$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
						}
					}
					// delete the last row
					$tpl = clean_code($tpl, 'map_point');
				break;
				case 'events':
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