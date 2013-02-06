<?php
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
				$db->query('INSERT INTO `ipt`.`parcels` ( `feedid`, `title`, `from`, `to`, `description`, `time` )
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
	
	// show success message
	case 'finished':
		$tpl = replace_feedid($tpl);
		break;
}
?>