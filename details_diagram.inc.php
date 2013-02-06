<?php
// replace start/end values/min/max in the template
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

// mark diagram tab as active
$diagram_active = $css_active;

if ( $dataArray ) {
	// sort sensor data by timestamp (keys of the data array)
	ksort($dataArray, SORT_NUMERIC);
	
	// iterate sensor data
	$i = 1;
	foreach ( $dataArray as $time => $val ) {
		// if there is no temperature data, set it's value to null
		if ( ! isset($val['tmp']) ) {
			$val['tmp'] = 'null';
		}
		// calculate temperature value in degrees celsius
		else {
			$val['tmp'] = round(floatval($val['tmp']) * 100 + 2, 2);
		}
		
		// if there is no humidity data or it's value is 0, set it's value to null
		if ( ! isset($val['hum']) || floatval($val['hum']) == 0 ) {
			$val['hum'] = 'null';
		}
		// calculate humidty value in percent
		else {
			$val['hum'] = round(28 * floatval($val['hum']) + 75, 2);
		}
		
		// copy table row and fill in sensor data for one timestamp
		$tpl = copy_code($tpl, 'diagram_data');
		$tpl = tpl_replace_once($tpl, 't', date('Y, m-1, d, H, i', $time));
		$tpl = tpl_replace_once($tpl, 'temp', $val['tmp']);
		$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y, g:i a', $time));
		$tpl = tpl_replace_once($tpl, 'temp', $val['tmp']);
		$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
		$tpl = tpl_replace_once($tpl, 'lt', date('d.m.Y, g:i a', $time));
		$tpl = tpl_replace_once($tpl, 'hum', $val['hum']);
		$tpl = tpl_replace_once($tpl, 'timestamp', $time);
		
		// check if it's the last entry, if true, delete the last comma from at the end of the data table row
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
?>