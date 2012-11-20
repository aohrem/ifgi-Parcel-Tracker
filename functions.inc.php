<?php
function read_tpl($filename) {
	$f = fopen('tpl/'.$filename.'.html', 'r');
    return fread($f, filesize('tpl/'.$filename.'.html'));
}

function tpl_replace($tpl, $old, $new) {
	$tpl = str_replace('{'.$old.'}', $new, $tpl);
	return $tpl;
}

function read_xml($filename) {
	$f = fopen('xml/'.$filename.'.xml', 'r');
    return fread($f, filesize('xml/'.$filename.'.xml'));
}

// if there is a feed id, replace the template placeholder with it
function replace_feedid($tpl) {
	if ( isset($_GET['fid']) ) {
		$feedid = htmlentities($_GET['fid']);
	}
	else {
		$feedid = '';
	}
	$tpl = tpl_replace($tpl, 'feedid', $feedid);
	return $tpl;
}
?>