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
		include('register.inc.php');
	break;
	// package details
	case 'details':	
		include('details.inc.php');
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