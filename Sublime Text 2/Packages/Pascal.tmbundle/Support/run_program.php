<?
	
	require_once("common.php");
	
	/**
	 * Runs a single Pascal program without project
	 */
	
	// Set the console font style
	print("<span style=\"font-family:andale mono; font-size: 9pt; text-decoration: none\">");
	
	//$_ENV["TM_FILEPATH"] = "/Users/ryanjoseph/Desktop/Test2D/Test2D.pas";
	$program = $_ENV["TM_FILEPATH"];
	
	$info = pathinfo($program);
	$program_name = basename($program,'.'.$info['extension']);
	
	// Copy command line options
	$lines = file($program);

	$options = trim($lines[0], "/{}");
	$options = "/".$options;
	
	$parts = explode(" ", $options);
	$compiler = $parts[0];
	
	// Warning
	if (!file_exists($compiler)) die("The compiler can not be located at $compiler!");
	
	// Build the FPC command
	$command = $compiler." \"".$program."\"".ltrim($options, $compiler);
	$command = rtrim($command, "\n");
	
	// Build output folder
	$output = dirname($program)."/".$program_name.".out";
	if (!file_exists($output)) mkdir($output);
	$command .= " -FU\"$output\"";
	
	// Get binary path
	$binary = dirname($program)."/".$program_name;
	
	// Execute command
	$error_line = null;
	if ($handle = popen($command, "r")) {		
    while (!feof($handle)) {
			$buffer = fgets($handle, 1024);
			
			
			// Insert the error as a clickable link
			if (eregi("^(.*)\(([0-9]+),([0-9]+)\) (Error|Fatal|Warning|Note):(.*)", $buffer, $matches)) {
				$line = $matches[2];
				$column = $matches[3];
				$message = $matches[5];
				$level = $matches[4];
				
				// Set the current error
				if ($level == "Error") $error_line = $line;
				
				print($matches[1]."($line,$column) "." <a href=\"txmt://open/?url=file://$program&line=$line&column=$column\">$level: $message</a><br>");
			} else {
				print($buffer."<br>\n");
			}
    }
		$error = pclose($handle);
	} else {
		$error = -1;
	}
	
	// Launch in terminal if the binary exists and the mod date changed
	if (file_exists($binary) && ($error == 0)) {
		$terminal_command = "/usr/bin/osascript <<EOF\n";
		$terminal_command .= "tell application \"Terminal\"\n";
		$terminal_command .= "	if (count of windows) is 0 then\n";
		$terminal_command .= "		do script \"'$binary'\"\n";
		$terminal_command .= "	else\n";
		$terminal_command .= "		do script \"'$binary'\" in window 1\n";
		$terminal_command .= "	end if\n";
		$terminal_command .= "	activate\n";
		$terminal_command .= "end tell\n";
		$terminal_command .= "EOF\n";

		// Execute the osascript
		exec($terminal_command);
	} else {
		// Show the first error
		if ($error_line) textmate_file($program, $error_line);
	}
	
	return $error;
?>