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
			$tpl = tpl_replace($tpl, 'feedid', $feedid);
			
			// if there's no or a wrong page set, use stats page as default
			if ( $p != 'stats' && $p != 'diagram' && $p != 'map' ) {
				$p = 'stats';
			}
			
			// show stats, diagram or map
			switch ( $p ) {
				case 'stats':
					// get data from cosm API
					include('cosm_api.inc.php');
					$cosmAPI = new CosmAPI();
					
					// set parameters for the cosm-API request
					$start = date('Y-m-d\TH:i:s\Z', time() - 604800);	// 21600 = 6 hours, 604800 = one week
					$end = date('Y-m-d\TH:i:s\Z', time());
					$interval = 0;
					$per_page = 500;
					
					if ( ! $xml = $cosmAPI->readFeed($feedid, $start, $end, $per_page, $interval, '') ) {
						die('Could not read cosm API');
					}
					else {
						// parse xml string
						$dataArray = $cosmAPI->parseXML($xml);
						
						// replace debugxml in template
						$tpl = tpl_replace($tpl, 'debugxml', htmlentities($xml));
						
						if ( $dataArray ) {
							// sort sensor data by timestamp (keys of the data array)
							ksort($dataArray, SORT_NUMERIC);
							
							// iterate sensor data
							foreach ( $dataArray as $time => $val ) {
								// if there is no data, show a -
								if ( ! isset($val['lat']) ) { $val['lat'] = '-'; }
								if ( ! isset($val['lon']) ) { $val['lon'] = '-'; }
								if ( ! isset($val['temp']) ) { $val['temp'] = '-'; }
								if ( ! isset($val['hum']) ) { $val['hum'] = '-'; }
								if ( ! isset($val['acc']) ) { $val['acc'] = '-'; }
								if ( ! isset($val['brig']) ) { $val['brig'] = '-'; }
								
								// copy table row and fill in sensor data for one timestamp
								$tpl = copy_code($tpl, 'tableRow');
								$tpl = tpl_replace_once($tpl, 't', date('d.m.Y H:i', $time));
								$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
								$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
								$tpl = tpl_replace_once($tpl, 'temp', $val['temp']);
								$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
								$tpl = tpl_replace_once($tpl, 'acc', $val['acc']);
								$tpl = tpl_replace_once($tpl, 'brig', $val['brig']);
							}
						}
						// delete the last row
						$tpl = clean_code($tpl, 'tableRow');
					}
				break;
				case 'diagram':
					// get data from cosm API
					include('cosm_api.inc.php');
					$cosmAPI = new CosmAPI();
					
					// set parameters for the cosm-API request
					$start = date('Y-m-d\TH:i:s\Z', time() - 604800);	// 21600 = 6 hours, 604800 = one week
					$end = date('Y-m-d\TH:i:s\Z', time());
					$interval = 0;
					$per_page = 500;
					
					if ( ! $xml = $cosmAPI->readFeed($feedid, $start, $end, $per_page, $interval, '') ) {
						die('Could not read cosm API');
					}
					else {
						// parse xml string
						$dataArray = $cosmAPI->parseXML($xml);
						
						// replace debugxml in template
						$tpl = tpl_replace($tpl, 'debugxml', htmlentities($xml));
						
						if ( $dataArray ) {
							// sort sensor data by timestamp (keys of the data array)
							ksort($dataArray, SORT_NUMERIC);
							
							// iterate sensor data
							$i = 1;
							foreach ( $dataArray as $time => $val ) {
								// if there is no data, set value to 0
								if ( ! isset($val['temp']) ) { $val['temp'] = '0'; }
								if ( ! isset($val['hum']) ) { $val['hum'] = '0'; }
								if ( ! isset($val['acc']) ) { $val['acc'] = '0'; }
								if ( ! isset($val['brig']) ) { $val['brig'] = '0'; }
								
								// copy table row and fill in sensor data for one timestamp
								$tpl = copy_code($tpl, 'diagram_data');
								$tpl = tpl_replace_once($tpl, 't', date('Y, m-1, d, H, i', $time));
								$tpl = tpl_replace_once($tpl, 'temp', $val['temp']);
								$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y H:i', $time));
								$tpl = tpl_replace_once($tpl, 'temp', $val['temp']);
								$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
								$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y H:i', $time));
								$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
								$tpl = tpl_replace_once($tpl, 'acc', $val['acc']);
								$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y H:i', $time));
								$tpl = tpl_replace_once($tpl, 'acc', $val['acc']);
								$tpl = tpl_replace_once($tpl, 'brig', $val['brig']);
								$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y H:i', $time));
								$tpl = tpl_replace_once($tpl, 'brig', $val['brig']);
								
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
					}
				break;
				case 'map':
					
				break;
			}
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