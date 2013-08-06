<?php

/**
 * Cleans all {} style comments that appear on a single line
 */

// Iterate the file
$lines = file('php://stdin');

foreach ($lines as $line) {
	
	if (eregi("^[[:space:]]*{[^ $=](.*)[^ ]}[[:space:]]*$", $line)) {
		
		eregi("^[[:space:]]*{(.*)}[[:space:]]*$", $line, $captures);
		$comment = ucfirst($captures[1]);
		
		$line = preg_replace("({.*})", "{ $comment }", $line);
		
		print($line);
	} else {
		print($line);
	}

}

?>