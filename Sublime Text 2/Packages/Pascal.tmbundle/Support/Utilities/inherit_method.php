<?php

/**
 * Finds the method implementation for the method declaration at the current line
 */

// Iterate the file
$lines = file('php://stdin');
$count = 1;
$method = null;

for ($i=$_ENV["TM_LINE_NUMBER"] - 1; $i < count($lines); $i--) { 
	$line = $lines[$i];
	
	if (preg_match('/^\s*\b(?i:(class)*)\b\s*\b(?i:(function|procedure|constructor|destructor))\b\s+(\w+)\.(\w+)\s*(.*)/', $line, $captures)) {
		
		$method = array();
		
		$method["prefix"] = strtolower($captures[2]);
		$method["class"] = $captures[3];
		$method["name"] = $captures[4];
		$method["params"] = $captures[5];//strpos(";", $captures[5]);
		//if (!$method["params"]) $method["params"] = $captures[5];
		
		// Find the parameters
		if (preg_match("/\((.*)\)/", $method["params"], $captures)) {
			$method["params"] = $captures[1];
			
			// Build a cleaned parameter list
			$method["params"] = explode(";", $method["params"]);
			foreach ($method["params"] as $param) {
				$parts = explode(":", trim($param, " 	"));
				
				// Remove var prefix
				// ??? note don't use ltrim, it's not accurate at all!
				if (preg_match("/^\b(?i:var)\b/", $parts[0])) $parts[0] = ltrim($parts[0], "var ");
				
				$method["param_string"] .= $parts[0].", ";
			}
			
			// Clean trailing commas and spaces then wrap in parenthesis
			$method["param_string"] = "(".trim($method["param_string"], ", ").")";
		}
		
		//print_r($method);
		//die;
		
		if ($method["prefix"] == "function") {
			die("result := inherited ".$method["name"].$method["param_string"].";");
		} else {
			die("inherited ".$method["name"].$method["param_string"].";");
		}
	}
	
	// Found an end block, bail
	if (preg_match('/^\s*end;/i', $line, $captures)) break;
	
	// Found an inherited keyword, bail
	if (preg_match('/^\s*inherited/i', $line, $captures)) break;

}

//die("There was no valid method from to inherit from at the current line.");

?>