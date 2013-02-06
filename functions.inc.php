<?php
	// reads one template file and returns it's html content as a string; if file is not found, error file is returned
	function read_tpl($filename) {
		if (!file_exists('tpl/'.$filename.'.html')) {
			return fread(fopen('tpl/error.html', 'r'), filesize('tpl/error.html'));
		}
		else {
			$f = fopen('tpl/'.$filename.'.html', 'r');
			return fread($f, filesize('tpl/'.$filename.'.html'));
		}
	}

	// replaces all occurances of the {$old} tag with the $new string
 	function tpl_replace($tpl, $old, $new) {
		return str_replace('{'.$old.'}', $new, $tpl);
	}
	
	// replaces the first occurance of the {$old} tag with the $new string
	function tpl_replace_once($tpl, $old, $new) {
		return preg_replace('/\{'.$old.'\}/', $new, $tpl, 1);
	}

	// copies and pastes code in a template; begin: {+$tag} end: {-$tag}
	function copy_code($tpl, $tag) {
		preg_match('@\{\+'.$tag.'\}(.*)\{\-'.$tag.'\}@s', $tpl, $subpattern);
		if ( isset($subpattern[1]) && isset($subpattern[0]) ) {
			return preg_replace('@\{\+'.$tag.'\}(.*)\{\-'.$tag.'\}@s', $subpattern[1].$subpattern[0], $tpl);
		}
		else {
			return false;
		}
	}

	// deletes the last row of copied code
	function clean_code($tpl, $tag) {
		return preg_replace('@\{\+'.$tag.'\}(.*)\{\-'.$tag.'\}@s', '', $tpl);
	}

	// reads one xml file and returns it's content as a string
	function read_xml($filename) {
		if ( file_exists('xml/'.$filename.'.xml') ) {
			$f = fopen('xml/'.$filename.'.xml', 'r');
			return fread($f, filesize('xml/'.$filename.'.xml'));
		}
		else {
			return false;
		}
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