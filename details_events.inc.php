<?php
// define sensor abbreviations
$sensors = array('bri', 'acc');

// parse xml string
$dataArray = $cosmAPI->parseXML($feedid, $start, $end, $limit, $interval, '', $sensors);

// mark events tab as active
$events_active = $css_active;

$parcel_opened = false;
$parcel_crashed = false;

if ( $dataArray ) {
	// sort sensor data by timestamp (keys of the data array)
	ksort($dataArray, SORT_NUMERIC);
	
	// iterate sensor data
	foreach ( $dataArray as $time => $val ) {
		// if there is no brightness or acceleration data, continue with next loop circle
		if ( ! isset($val['bri']) && ! isset($val['acc']) ) {
			continue;
		}
		else {
			// if there is data for the brightness sensor, set the parcel state to opened
			if ( isset($val['bri']) ) {
				$parcel_opened = true;
				
				// copy 'parcel opened'-row and fill in data for one timestamp
				$tpl = copy_code($tpl, 'opening_events');
				$tpl = tpl_replace_once($tpl, 'opening_timestamp', $time);
				$tpl = tpl_replace_once($tpl, 'opening_timestamp', $time);
				$tpl = tpl_replace_once($tpl, 'opening_event', 'Parcel opened on '.date('d.m.Y, g:i a', $time).'.');
			}
			
			// if there is data for the acceleration sensor, set the parcel state to crashed
			if ( isset($val['acc']) ) {
				$parcel_crashed = true;
				
				// copy 'parcel crashed'-row and fill in data for one timestamp
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

// replace template tags for the 'parcel opened'-state
if ( $parcel_opened ) {
	$tpl = tpl_replace($tpl, 'state_open', 'open');
	$tpl = tpl_replace($tpl, 'state_open_text', 'Parcel opened');
	$tpl = tpl_replace($tpl, 'bool_opening_events', '');
}
else {
	$tpl = tpl_replace($tpl, 'state_open', 'normal');
	$tpl = tpl_replace($tpl, 'state_open_text', 'Parcel closed');
	$tpl = tpl_replace($tpl, 'bool_opening_events', '<li>No opening events.</li>');
}

// replace template tags for the 'parcel crashed'-state
if ( $parcel_crashed ) {
	$tpl = tpl_replace($tpl, 'state_crash', 'crashed');
	$tpl = tpl_replace($tpl, 'bool_crash_events', '');
}
else {
	$tpl = tpl_replace($tpl, 'state_crash', 'normal');
	$tpl = tpl_replace($tpl, 'bool_crash_events', '<li>No potential crash events.</li>');
}
?>