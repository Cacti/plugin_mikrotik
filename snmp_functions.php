<?php

function cacti_escapeshellcmd($string) {
	global $config;

	if ($config["cacti_server_os"] == "unix") {
		return escapeshellcmd($string);
	}else{
		$replacements = "#&;`|*?<>^()[]{}$\\";

		for ($i=0; $i < strlen($replacements); $i++) {
			$string = str_replace($replacements[$i], " ", $string);
		}
		return $string;
	}
}


/**
 * mimics escapeshellarg, even for windows
 * @param $string 	- the string to be escaped
 * @param $quote 	- true: do NOT remove quotes from result; false: do remove quotes
 * @return			- the escaped [quoted|unquoted] string
 */
function cacti_escapeshellarg($string, $quote=true) {
	global $config;
	/* we must use an apostrophe to escape community names under Unix in case the user uses
	characters that the shell might interpret. the ucd-snmp binaries on Windows flip out when
	you do this, but are perfectly happy with a quotation mark. */
	if ($config["cacti_server_os"] == "unix") {
		$string = escapeshellarg($string);
		if ( $quote ) {
			return $string;
		} else {
			# remove first and last char
			return substr($string, 1, (strlen($string)-2));
		}
	}else{
		if (substr_count($string, CACTI_ESCAPE_CHARACTER)) {
			$string = str_replace(CACTI_ESCAPE_CHARACTER, "\\" . CACTI_ESCAPE_CHARACTER, $string);
		}

		if ( $quote ) {
			return CACTI_ESCAPE_CHARACTER . $string . CACTI_ESCAPE_CHARACTER;
		} else {
			return $string;
		}
	}
}

?>
