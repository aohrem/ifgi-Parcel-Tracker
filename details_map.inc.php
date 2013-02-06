<?php
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

// mark map tab as active
$map_active = $css_active;

// if there is a timestamp given, give another color to it's marker
if ( isset($_GET['timestamp']) && is_numeric($_GET['timestamp']) ) {
	$active_time = $_GET['timestamp'];
}
else {
	$active_time = 0;
}

if ( $dataArray ) {
	// sort sensor data by timestamp (keys of the data array)
	ksort($dataArray, SORT_NUMERIC);
	
	// iterate sensor data
	foreach ( $dataArray as $time => $val ) {
		// if one of the location data parts is missing, go to the next value
		if ( ! isset($val['lat']) || ! isset($val['lon']) ) { continue; }
		
		// if there is no temperature data, set it's value to null
		if ( ! isset($val['tmp']) ) {
			$val['tmp'] = 'null';
		}
		// calculate temperature value in degrees celsius
		else {
			$val['tmp'] = round(floatval($val['tmp']) * 100 + 2, 2)." &deg;C";
		}
		
		// if there is no humidity data, set it's value to null
		if ( ! isset($val['hum']) ) {
			$val['hum'] = 'null';
		}
		// if the humdity value is 0, write '< 75 %' instead
		else if ( floatval($val['hum']) == 0 ) {
			$val['hum'] = '&lt; 75 %';
		}
		// calculate humidty value in percent
		else {
			$val['hum'] = round(28 * floatval($val['hum']) + 75, 2).' %';
		}
		
		// check if the point should be marked as active
		if ( $time == $active_time ) {
			$point_active = "true";
		}
		else {
			$point_active = "false";
		}
		
		// copy table row and fill in sensor data for one timestamp
		$tpl = copy_code($tpl, 'map_point');
		$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
		$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
		$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
		$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
		$tpl = tpl_replace_once($tpl, 'markertime', date("d.m.Y, g:i a",$time));
		$tpl = tpl_replace_once($tpl, 'markertime', date("d.m.Y, g:i a",$time));
		
		// insert temperature data in the popup if it's not null
		if ( $val['tmp'] != 'null' ) {
			$tpl = tpl_replace_once($tpl, 'temp', '<br><u>Temp:</u> '.$val['tmp']);
		}
		else {
			$tpl = tpl_replace_once($tpl, 'temp', '');
		}
		
		// insert humidity data in the popup if it's not null
		if ( $val['hum'] != 'null' ) {
			$tpl = tpl_replace_once($tpl, 'humid', '<br><u>Hum:</u> '.$val['hum']);
		}
		else {
			$tpl = tpl_replace_once($tpl, 'humid', '');
		}
		
		// finish filling in the data for one timestamp
		$tpl = tpl_replace_once($tpl, 'timeanker', $time);
		$tpl = tpl_replace_once($tpl, 'timeanker', $time);
		$tpl = tpl_replace_once($tpl, 'fragnach', $point_active);
		$tpl = tpl_replace_once($tpl, 'lat', $val['lat']);
		$tpl = tpl_replace_once($tpl, 'lon', $val['lon']);
	}
}

// delete the last row
$tpl = clean_code($tpl, 'map_point');
?>