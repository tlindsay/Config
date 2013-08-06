<?php

/**
 * Converts indentation style
 */

// Set the default tab string
$tab = "	";

// Set the current indent string
// ??? currenly this script only converts single-space tab indents until it has a preference or GUI
$indent = " ";

// Create the soft tab string
if ($_ENV["TM_SOFT_TABS"] == "YES") {
	$tab = "";
	for ($i=0; $i < $_ENV["TM_TAB_SIZE"] - 1; $i++) { 
		$tab .= " ";
	}
}

// Iterate the file
$lines = file('php://stdin');

foreach ($lines as $line) {
	if (eregi("^([[:space:]]+)(.*)", $line, $captures)) {
		$margin = $captures[1];
		$margin = str_replace($indent, $tab, $margin);
		print($margin.$captures[2]);
	} else {
		print($line);
	}
}

?>