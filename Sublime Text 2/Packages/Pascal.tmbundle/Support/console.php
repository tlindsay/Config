<?php
	require_once($_ENV["TM_BUNDLE_SUPPORT"]."/common.php");
	require_once($_ENV["TM_BUNDLE_SUPPORT"]."/preferences.php");
	
	// Build console style CSS
	switch ($preferences->get_integer_value("console_style")) {
		case 1: {
			$console_style = "color: black; background-color: white; ";
			break;
		}
		case 2: {
			$console_style = "color: white; background-color: black; ";
			break;
		}
	}
	$console_style .= "font-size: ".$preferences->get_string_value("console_font_size")."pt; ";
	$console_style .= "font-family: ".$preferences->get_string_value("console_font")."; ";

	// Return control to the current file to hide the console window
	// ??? make this a target option
	textmate_current_file();
	
	// Load the target if it's not present
	if (!isset($loader)) {
		require_once($_ENV["TM_BUNDLE_SUPPORT"]."/target_loader.php");
		$loader = new TargetLoader($project, $target);
	}

	// Verify the target before we enter the console
	$loader->verify();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<!-- Splitter -->
<script type="text/javascript" src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/splitter/jquery.js"></script>
<script type="text/javascript" src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/splitter/splitter.js"></script>

<!-- Common Styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/splitter.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/console.css" type="text/css" rel="stylesheet" media="screen"/>

<!-- Console style -->
<style>
div#console {
	<?php echo $console_style ?>
}
</style>

<script type="text/javascript">
$().ready(function() {
	$("#splitview").splitter({
		type: "h", 
		sizeTop: true,
		accessKey: "P"
	});
});
</script>

<script type="text/javascript">

var compiler;									// Instance of the compiler command
var found_errors = false;			// Errors were found in the console

// Common Elements
var console;
var errors;
var errors_pane;
var error_count;
var terminate;
var partial_buffer;

String.prototype.trim = function() {
	return this.replace(/(^\s*)|(\s*$)/g, "");
}
 
String.prototype.ltrim = function() {
	return this.replace(/(^\s*)/g, "");
}
 
String.prototype.rtrim = function() {
	return this.replace(/(\s*$)/g, "");
}

// Resizes the split view to the window
function resize_splitview () {
	$("#splitview").css("width", (window.innerWidth - 0)+"px").trigger("resize");
	$("#splitview").css("height", (window.innerHeight - 0 /* What's this margin? */)+"px").trigger("resize");
}


// Toggles the error split pane
function toggle_error_pane (hide) {
	if (hide) {
		$("#splitview").trigger("resize", [10000]);
		errors_pane.style.visibility = 'hidden';
	} else {
		$("#splitview").trigger("resize", [200]); /* This has to be a preference */
		errors_pane.style.visibility = 'visible';
	}
}

window.onresize = function() {
	resize_splitview();
}

// Read STDOUT from the compiler
function read_stdout (text) {
	var last = 0;
	var buffer = "";
	
	var PATTERN_MESSAGE = /^\[%%MESSAGE%%\](.*)/i;
	var PATTERN_ERROR = /^\[%%ERROR%%\](.*)/i;
	var PATTERN_LAUNCHED = /^\[%%LAUNCHED%%\](.*)/i;

	// Append partial buffer to text
	if (partial_buffer) {
		text = partial_buffer+text;
	}
	
	for (var i=0; i < text.length; i++) {
		
		// EOL
		if (text.charAt(i) == "\n") {
			if (buffer = text.substring(last, i)) {
				
				// Trim white space from the string
				buffer = buffer.trim();
				
				// Errors
				if (captures = buffer.match(PATTERN_ERROR)) {
					errors.innerHTML += captures[1]+"<br/>\n";
					error_count.innerHTML = Number(error_count.innerHTML) + 1;
					found_errors = true;
					
					// Show the error pane
					toggle_error_pane(false);
				}
				
				// Messages
				if (captures = buffer.match(PATTERN_MESSAGE)) {
					console.innerHTML += captures[1]+"<br/>\n";
				}
				
				// Launched
				if (captures = buffer.match(PATTERN_LAUNCHED)) {
					terminate.style.visibility = 'visible';
				}
				
			}
			
			// Increment the buffer offset
			last = i;
		}
		
		// EOF
		if (i == text.length - 1) {
			partial_buffer = text.substring(last, i);
		}
		
	};
	
	// EOF is EOL, clear the partial buffer
	if (text.charAt(text.length - 1) == "\n") {
		partial_buffer = null;
	}
	
	// Scroll to bottom
	console.scrollTop = console.scrollHeight;
}

// The compiler process terminated
function terminated (object) {
	TextMate.isBusy = false;
	
	// Close the window if no errors were found
	// ??? make this an option! it's too dangerous to leave off cause some
	// things needs to be reviewed in the console
	if (found_errors == false) {
		//window.close();
	}
}

// Quits the launched bundle
function terminate_bundle () {
	var command = "osascript -e 'tell application \"<?php print($_ENV['BUNDLE']); ?>\" to quit'";
	var script = TextMate.system(command, null);
	
	terminate.style.visibility = 'hidden';
	
	// Close the window
	window.close();
}

// Run the compiler process
function run () {
		
	found_errors = false;
		
	var options = "mode=\"<?php print($_ENV['MODE']); ?>\" project=\"<?php print($_ENV['TM_PROJECT_DIRECTORY']); ?>\" target=\"<?php print($_ENV['TARGET']); ?>\" ";
	
	// Make the PHP command to run this script
	var command = "/usr/bin/php \"<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/compiler_helper.php\" "+options+" 2>&1";
	
	// Execute the command using TextMate.system
	TextMate.isBusy = true;
	compiler = TextMate.system(command, terminated);
	
	// Read from STDOUT
	compiler.onreadoutput = read_stdout;
}

function load () {

	// Init common elements
	console = document.getElementById('console');
	errors = document.getElementById('errors');
	errors_pane = document.getElementById('error_pane');
	error_count = document.getElementById('error_count');
	terminate = document.getElementById('terminate');

	// Setup the splitview intial state
	resize_splitview();
	toggle_error_pane(true);
	
	run();
}

</script>

</head>
<body onload="load();">
	
	<div id="splitview">

		<div id="toppane">
			<div id="console"></div>
		</div>

		<div id="bottompane">
			<div id="error_pane">
				
				<div class="section">
					<img class="section-image" src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/images/warning.png"><span class="message">Found <span id="error_count">0</span> Errors</span>
				</div>

				<table id="errors">
				</table>	
			</div>
		</div>

	</div>

</body>
</html>
