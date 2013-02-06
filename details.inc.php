<?php
$feedid = trim(htmlentities($_GET['fid']));
$db = new Sql();
$row = $db->fetch('SELECT * FROM `ipt`.`parcels` WHERE `feedid`=\''.$feedid.'\'');

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
	
	// if there's set no page or a wrong page, use map page as default
	if ( $p != 'stats' && $p != 'diagram' && $p != 'map' && $p != 'events' ) {
		$p = 'map';
	}
	
	// initialize css attributes to mark the active tab
	$css_active = " class='active'";
	$details_active = '';
	$diagram_active = '';
	$map_active = '';
	$events_active = '';
	
	// initialize cosm API connection
	include('cosm_api.inc.php');
	$cosmAPI = new CosmAPI();
	
	// get time frame of the cosm feed
	$timeframe = $cosmAPI->getTimeframe($feedid);
	$created = $timeframe['created'];
	$updated = $timeframe['updated'];
	
	// calculate start and end time for the tab urls
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
	
	// calculate start and end time and minimums and maximums for the time pickers
	$timedifference = $end - $start;
	$value_start_date = date('Y-m-d', $start);
	$value_end_date = date('Y-m-d', $end);
	$value_start_time = date('H:i', $start);
	$value_end_time = date('H:i', $end);
	$min_date = date('Y-m-d', strtotime(substr($created, 0, -1)));
	$max_date = date('Y-m-d', strtotime(substr($updated, 0, -1)));
	$start = date('Y-m-d\TH:i:s\Z', $start);
	$end = date('Y-m-d\TH:i:s\Z', $end);
	
	// set parameters for the cosm-API request
	// look-up table for the cosm API time interval
	$intervals = array(
		21600 => 1,			// 6 hours
		43200 => 30,		// 12 hours
		86400 => 60,		// 24 hours
		432000 => 300,		// 5 days
		1209600 => 900,		// 14 days
		2678400 => 1800,	// 31 days
		7776000 => 10800,	// 90 days
		15552000 => 21600	// 180 days
	);
	
	// lookup interval
	$interval = 21600;
	foreach ( $intervals as $key => $val ) {
		if ( $timedifference < $key ) {
			$interval = $val;
			break;
		}
	}
	
	// maximum number of entries in the cosm API response
	$limit = 500;
	
	// show stats, diagram or map (outsourced in seperate files)
	switch ( $p ) {
		case 'stats':
			include('details_stats.inc.php');
			break;
		case 'diagram':
			include('details_diagram.inc.php');
			break;
		case 'map':
			include('details_map.inc.php');
			break;
		case 'events':
			include('details_events.inc.php');
			break;
	}
	
	// replace tags in the html template
	$tpl = tpl_replace($tpl, 'feedid', $feedid);
	$tpl = tpl_replace($tpl, 'details_active', $details_active);
	$tpl = tpl_replace($tpl, 'diagram_active', $diagram_active);
	$tpl = tpl_replace($tpl, 'map_active', $map_active);
	$tpl = tpl_replace($tpl, 'events_active', $events_active);
	$tpl = tpl_replace($tpl, 'times', $times);
}
?>