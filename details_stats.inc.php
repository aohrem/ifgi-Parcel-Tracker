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

if ( isset($_GET['time']) && is_numeric($_GET['time']) ) {
	$active_time = $_GET['time'];
}
else {
	$active_time = 0;
}

// mark table tab as active
$details_active = $css_active;

if ( $dataArray ) {
	// sort sensor data by timestamp (keys of the data array)
	ksort($dataArray, SORT_NUMERIC);
	
	// iterate sensor data
	foreach ( $dataArray as $time => $val ) {
		// if there is no location data, show a -
		if ( ! isset($val['lat']) ) { $val['lat'] = '-'; }
		if ( ! isset($val['lon']) ) { $val['lon'] = '-'; }
		
		// if there is no temperature data, set it's value to null
		if ( ! isset($val['tmp']) ) {
			$val['tmp'] = '-';
		}
		// calculate temperature value in degrees celsius
		else {
			$val['tmp'] = round(floatval($val['tmp']) * 100 + 2, 2)." &deg;C";
		}
		
		// if there is no humidity data, set it's value to null
		if ( ! isset($val['hum']) ) {
			$val['hum'] = '-';
		}
		// if the humdity value is 0, write '< 75 %' instead
		else if ( floatval($val['hum']) == 0 ) {
			$val['hum'] = '&lt; 75 %';
		}
		// calculate humidty value in percent
		else {
			$val['hum'] = round(28 * floatval($val['hum']) + 75, 2).' %';
		}
		
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
?>