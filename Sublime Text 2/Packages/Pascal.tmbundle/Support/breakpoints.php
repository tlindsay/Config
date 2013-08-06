<?php

require_once("target_loader.php");
require_once("common.php");

class Breakpoints {
	
	const BREAKPOINT = "/\/\/break$/i";		// Pattern to search for break points
	
	public $target;												// The loaded target
	
	// Returns break points as GDB syntax string
	public function get_gdb_command ($breakpoints) {
		$results = "";
		foreach ($breakpoints as $file => $array) {
			$file_name = basename($file);
			
			foreach ($array as $line) {
			$results .= "break $file_name:$line\n";
			}
		}
		
		return $results;
	}
	
	// Finds all break points in the target
	public function find () {
		$results = array();

		// Iterate all paths in target
		foreach ($this->target->get_resolved_paths() as $path) {
			
			// Iterate all files in paths
			$files = directory_contents($path);
			foreach ($files as $file) {
				$lines = file($file);
				
				// Iterate all lines in file
				for ($i=0; $i < count($lines); $i++) { 
					if (preg_match(self::BREAKPOINT, $lines[$i])) {
						$results[$file][] = $i + 1;
					}
				}
			}
		}
		
		if (count($results) == 0) $results = null;
		
		return $results;
	}
	
	// Prints all defined break points
	public function show ($breakpoints) {
		foreach ($breakpoints as $file => $array) {
			$file_name = basename($file);
			foreach ($array as $line) {
				print("$file_name:$line\n");
			}
		}
	}
	
	// Sets the target
	public function set_target (&$target) {
		$this->target = $target;
	}
	
	// Loads a target manually
	public function load_target ($project, $target) {
		$this->target = new TargetLoader($project, $target);
	}
	
	public function __construct () {
	}
	
}

/*
$breakpoints = new Breakpoints();
$breakpoints->load_target("/Users/ryanjoseph/Desktop/Projects/FPC_Projects/WindowManager", "development");
if ($results = $breakpoints->find()) {
	//$breakpoints->show($results);
	//$command = $bp->get_gdb_command($results);
	//print($command);
}
*/

?>