<?php

/**
* Class to manage document input from TextMate
*/
class TextMateInput	{
	
	// Handle one line of input
	// Return TRUE to stop interating
	protected function handle_line ($number, $line) {
		return false;
	}
	
	protected function handle_current_line ($line) {
		return false;
	}
	
	function __construct()	{
		
		// Iterate the file
		$lines = file('php://stdin');
		$count = 1;

		foreach ($lines as $line) {
				
				
				if ($this->handle_line($count, $line)) {
					break;
				}
				
				// Found the current line
				if ($count == $_ENV["TM_LINE_NUMBER"]) {
					if ($this->handle_current_line($line)) {
						break;
					}
				}
				
				$count++;
		}
		
	}
}

?>