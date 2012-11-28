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
				$description = '';
				
				// if all necessary post data is there, check data
				if ( isset($_POST['to']) && isset($_POST['from']) && isset($_POST['description']) ) {
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
						include('cosm_api.inc.php');
						$cosm_api = new CosmAPI();
						$feedid = $cosm_api->createFeed();
						
						// new database connection
						$db = new Sql();
						$db->query('INSERT INTO `potwc`.`parcels` ( `feedid`, `from`, `to`, `description`, `time` )
							VALUES (\''.$feedid.'\', \''.$from.'\', \''.$to.'\', \''.$description.'\', \''.date("YmdHis",time()).'\')');
						
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
			$tpl = tpl_replace($tpl, 'feedid', $feedid);
			$tpl = tpl_replace($tpl, 'time', date('d.m.Y, g:i a', strtotime($row->time)));
			$tpl = tpl_replace($tpl, 'from', htmlentities($row->from));
			$tpl = tpl_replace($tpl, 'to', htmlentities($row->to));
			$tpl = tpl_replace($tpl, 'description', nl2br(htmlentities($row->description)));
			
			// get data from cosm API
			include('cosm_api.inc.php');
			$cosmAPI = new CosmAPI();
			if ( ! $xml = $cosmAPI->readFeed($feedid, '', '', '') ) {
				die('Could not read cosm API');
			}
			else {
				// parse xml document given by the cosm API
				$xml = simplexml_load_string($xml);				
				
				// show some example stuff from the xml document
				$tpl = tpl_replace($tpl, 'details', 'Feed title: '.$xml->environment->title.'<br>
					Status: '.$xml->environment->status.'<br>
					Location: '.$xml->environment->location['exposure'].', '.$xml->environment->location['disposition'].'<br>
					Lat: '.$xml->environment->location->lat.' Lon: '.$xml->environment->location->lon);
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