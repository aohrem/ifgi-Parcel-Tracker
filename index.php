<?php
 include('sql.php');
 
 function tpl_replace($tpl, $replaces) {
	for ( $i = 0; $i < sizeof($replaces); $i++ ) {
		$tpl = str_replace('{'.$replaces[$i][0].'}', $replaces[$i][1], $tpl);
	}
	return $tpl;
 }
 
 function read_tpl($filename) {
	$f = fopen('tpl/'.$filename.'.html', 'r');
    return fread($f, filesize('tpl/'.$filename.'.html'));
 }
 
 $replaces = array();
 
 $f = fopen('tpl/main.html', 'r');
 $tpl = fread($f, filesize('tpl/main.html'));
 
 if ( isset($_GET['site']) ) {
	$site = $_GET['site'];
 }
 else {
	$site = 'home';
 }
 
 if ( isset($_GET['step']) ) {
	$step = $_GET['step'];
 }
 
 if ( isset($step) && file_exists('tpl/'.$site.'_step_'.$step.'.html') ) {
	$site_tpl = read_tpl($site.'_step_'.$step);
 }
 elseif ( file_exists('tpl/'.$site.'.html') ) {
	$site_tpl = read_tpl($site);
 }
 else {
	$site_tpl = 'Fehler - Datei \'tpl/'.$site.'.html\' existiert nicht.';
 }
 
 $replaces[] = array('content', $site_tpl);
 
 switch ( $site ) {
	case 'add';
		switch ( $step ) {
			case 2:
				$feedid = rand(10000,99999);
				$replaces[] = array('feedid', $feedid);
			break;
			case 3:
				$feedid = htmlentities(mysql_real_escape_string($_GET['feedid']));
				$replaces[] = array('feedid', $feedid);
				$errormsg = '';
				
				if ( isset($_POST['to']) && isset($_POST['from']) && isset($_POST['description']) ) {
					$to = mysql_real_escape_string(htmlentities($_POST['to']));
					$from = mysql_real_escape_string(htmlentities($_POST['from']));
					$description = mysql_real_escape_string($_POST['description']);
					
					if ( $to == '' ) {
						$errormsg = '<p>Sie haben nicht alle erforderlichen Felder ausgef&uuml;llt.</p>';
						$replaces[] = array('redfieldto', ' redfield');
					}
					else {
						$replaces[] = array('redfieldto', '');
					}
					if ( $from == '' ) {
						$errormsg = '<p>Sie haben nicht alle erforderlichen Felder ausgef&uuml;llt.</p>';
						$replaces[] = array('redfieldfrom', ' redfield');
					}
					else {
						$replaces[] = array('redfieldfrom', '');
					}
					
					$db = new Sql();
					$check = $db->fetch('SELECT `feedid` FROM `potwc`.`parcels` WHERE `feedid`=\''.$feedid.'\'');
					if ( ! isset($check->feedid) ) {
						if ( $errormsg == '' ) {
							$db->query('INSERT INTO `potwc`.`parcels` ( `feedid`, `from`, `to`, `description`, `time` ) VALUES (\''.$feedid.'\', \''.$to.'\', \''.$from.'\', \''.$description.'\', \''.time().'\')');
							header('Location: index.php?site=add&step=4&feedid='.$feedid);
						}
					}
					else {
						$errormsg = '<p>Die Feed ID existiert bereits in unserer Datenbank und konnte nicht erstellt werden.</p>';
					}
					
					if ( $errormsg != '' ) {
						$replaces[] = array('valueto', ' value=\''.$to.'\'');
						$replaces[] = array('valuefrom', ' value=\''.$from.'\'');
						$replaces[] = array('valuedescription', $description);
					}
				}
				else {
					$replaces[] = array('redfieldto', '');
					$replaces[] = array('redfieldfrom', '');
					$replaces[] = array('valueto', '');
					$replaces[] = array('valuefrom', '');
					$replaces[] = array('valuedescription', '');
				}
				
				$replaces[] = array('errormsg', $errormsg);
			break;
			case 4:
				$feedid = htmlentities(mysql_real_escape_string($_GET['feedid']));				
				$replaces[] = array('feedid', $feedid);
			break;
		}
	break;
	case 'details':
		$feedid = trim(htmlentities($_GET['feedid']));
		$db = new Sql();
		$row = $db->fetch('SELECT * FROM `potwc`.`parcels` WHERE `feedid`=\''.$feedid.'\'');
		
		if ( ! is_numeric($feedid) || ! isset($row->feedid) ) {
			$replaces[] = array('content', read_tpl('error_feedid'));
			$replaces[] = array('feedid', $feedid);
		}
		else {
			$replaces[] = array('content', read_tpl('details_content'));
			$replaces[] = array('time', date('d.m.Y, H:i', $row->time).' Uhr');
			$replaces[] = array('from', htmlentities($row->from));
			$replaces[] = array('to', htmlentities($row->to));
			$replaces[] = array('description', nl2br(htmlentities($row->description)));
		}
		$replaces[] = array('feedid', $feedid);
	break;
 }
 
 $tpl = tpl_replace($tpl, $replaces);
 
 print $tpl;
?>