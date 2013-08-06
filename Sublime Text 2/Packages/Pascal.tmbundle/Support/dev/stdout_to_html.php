<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<!-- Template for reading STDOUT into DIV using JavaScript -->

<html lang="en">
<head>
	
<style type="text/css">
div#console {
	overflow: auto;
	overflow-x:hidden;
	height: 100%;
	padding: 20px;
	font-size: 7pt;
	font-family: monospace;
}
</style>

<script type="text/javascript">

var console;
var partial_buffer;

/*
function read_stdout (text) {
	var last = 0;
	var buffer = "";

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
				
				console.innerHTML += buffer+"<br/>\n";				
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
}
*/

function read_stdout (text) {
	console.innerHTML += text;
	
	// Scroll to bottom
	console.scrollTop = console.scrollHeight;
}

// The compiler process terminated
function terminated (object) {
	TextMate.isBusy = false;
}

// Run the compiler process
function run () {
	var command = "<?php print($_ENV['SCRIPT_COMMAND']); ?>";
	var compiler;

	// Execute the command using TextMate.system
	TextMate.isBusy = true;
	compiler = TextMate.system(command, terminated);
	
	// Read from STDOUT
	compiler.onreadoutput = read_stdout;
}

function load () {
	console = document.getElementById('console');
	
	<?php
		if ($_ENV['WINDOW_SiZE']) {
			$pair = explode(",", $_ENV['WINDOW_SiZE']);
			$width = $pair[0];
			$height = $pair[1];
			print("window.resizeTo($width, $height);");
		}
	?>
	run();
}

</script>

</head>
<body onload="load();">
	<div id="console"></div>
</body>
</html>
