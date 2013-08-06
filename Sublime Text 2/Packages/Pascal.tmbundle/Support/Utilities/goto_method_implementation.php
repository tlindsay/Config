<?php

/**
 * Finds the method implementation for the method declaration at the current line
 */

// Iterate the file
$lines = file('php://stdin');
$count = 1;
$method = null;

foreach ($lines as $line) {
	
	// Find the current method declaration
	if (!$method) {
		
		// Found current class declaration
		if (preg_match('/^\s*\b(\w+)\b\s+=\s+(?i:(class|object|objcclass|objccategory|objcprotocol|interface))\b\s*(\((.*)\))*$/', $line, $captures)) {
			$class = $captures[1];
		}

		// Found the current line
		if ($count == $_ENV["TM_LINE_NUMBER"]) {
			
			if (preg_match('/^\s*\b(?i:(class)*)\b\s*\b(?i:(function|procedure|constructor|destructor))\b\s+(\w+)\s*(.*)/', $line, $captures)) {
				
				$method = array();
				$method["class"] = $class;
				
				if ($captures[1]) {
					$method["prefix"] = $captures[1]." ".$captures[2];
				} else {
					$method["prefix"] = $captures[2];
				}
				
				$method["name"] = $captures[3];
				$method["params"] = strpos(";", $captures[4]);
				$method["pattern"] = "/^\s*".$method["prefix"]."\s+".$method["class"]."\.".$method["name"]."/";
				
				//die($method["pattern"]);
				continue;
			} else {
				die("The method at the current line is not a valid declaration.");
			}
		}
		
	}
	
	// Find the method implementation
	if ($method) {
		if (preg_match($method["pattern"], $line, $captures)) {
			
			// Jump to the line
			$jump_line = $count + 1;
			exec($_ENV["TM_SUPPORT_PATH"]."/bin/mate \"".$_ENV["TM_FILEPATH"]."\" -l \"$jump_line\"");
			die;
		}
	}
	
	// Increment the line count
	$count++;
}

if ($method) die("The method implementation for ".$method["name"]." can't be found.");

?>