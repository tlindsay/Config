<?php
require_once("target_loader.php");
require_once("symbol_parser.php");

if (isset($_ENV["TM_SELECTED_TEXT"])) {
	$query = $_ENV["TM_SELECTED_TEXT"];
} else {
	$query = $_ENV["TM_CURRENT_WORD"];
}

// Load the target and PPU parser
$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
$parser = new SymbolParser($target->get_symbols_directory(), null);

// Query for the parser for the symbols
$symbols = $parser->query_2($query);

// Determine the window size
$row_height = 20;
$character_width = 6;
$title_bar_height = 40;
if ($symbols) {
	
	$longest_name = "";
	foreach ($symbols as $symbol) {
		$name = "Go to ".$symbol["name"]." in ".basename($symbol["path"]);
		if (strlen($name) > strlen($longest_name)) $longest_name = $name;
	}
	
	$window_width = ($character_width * strlen($longest_name)) + 30;
	$window_height = ($row_height * count($symbols)) + $title_bar_height;
} else { // Default size
	$window_width = 275;
	$window_height = $row_height * 16;
}

?>

<html>
<head>
<!-- Common styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>

<style type="text/css">
body {
	margin: 0px;
	background-color: #ecf3fd;
}

a.selected {
	text-shadow: 0px 1px 1px black;
	color: white;
	font-weight: bold;
}	

td {
	max-height: 20px;
	min-height: 20px;
	height: 20px;
	padding-top: 2px;
	padding-bottom: 2px;
	padding-left: 8px;
	padding-right: 2px;
	overflow-x:hidden;
	border-bottom-color: #b3b3b3;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	white-space: nowrap;
}
	
table {
	width: 100%;
	border-spacing: 0px 0px;
	font-size: 8pt;
	overflow: hidden;
}

.container {
	width: 100%;
	height: 100%;
	margin: 0px;
	overflow: hidden;
}

.message {
	padding: 20px;
}

.note {
	font-size: 10px;
}

</style>

<script type="text/javascript">

var selection = 0;	// The currently selected row

HTMLElement.prototype.applySelection = function() {
	this.className = "selected-row";
	this.firstChild.className = "selected";
}

HTMLElement.prototype.clearSelection = function() {
	this.className = "";
	this.firstChild.className = "";
}

function getSelection () {
	return document.getElementById(selection);
}

// Sets the single selection for the table
function setSelection (index) {
	if (row = document.getElementById(selection)) row.clearSelection();
	document.getElementById(index).applySelection();
	selection = index;
}

function moveSelection (event) {

	// Return
	if (event.keyCode == 13) {
		
		// Get the path/line attribute from the selection row
		if (row = document.getElementById(selection)) {
			path = row.getAttribute("path");
			line = row.getAttribute("line");

			// Invoke "mate" with the selection attributes
			var support = "<?php print($_ENV["TM_SUPPORT_PATH"]); ?>";
			var command = TextMate.system(support+"/bin/mate \""+path+"\" -l "+line, null);
		}
		
		// Close the window
		window.close();
	}
	
	// Move selection up
	if (event.keyCode == 38) {
		if (row = document.getElementById(selection)) row.clearSelection();

		selection--;
		if (selection < 0) selection = 0;
		
		getSelection().applySelection();
		getSelection().scrollIntoView();
		
		event.preventDefault();
	}
	
	// Move selection down
	if (event.keyCode == 40) {
		if (row = document.getElementById(selection)) row.clearSelection();

		selection++;
		if (!document.getElementById(selection)) {
			selection--;
		}
		
		getSelection().applySelection();
		getSelection().scrollIntoView();

		event.preventDefault();
	}
	
}

function resizeWindow() { 
		var container = document.getElementById("container");
    window.resizeTo(container.offsetLeft + <?php echo $window_width; ?>, container.offsetTop + <?php echo $window_height; ?>); 
		
		width = container.offsetWidth;
		height = container.offsetHeight;
		window.moveTo((screen.availWidth-width)/2,(screen.availHeight-height)/2); 
} 

function load() {
	
	// Resize to the window size calculated before
	resizeWindow();
	
	// Select the default row
	setSelection(0);
}

</script>
	
</head>
<body onload="load();" onkeydown="moveSelection(event);">
<div id="container">
<?php
	// Query for the symbol
	if ($symbols) {
		print("<table>");
		
		// Select the first symbol if it's the only one
		if (count($symbols) == 1) {
			$path = $symbols[0]["path"];
			$line = $symbols[0]["line"];
			
			textmate_file($path, $line);
			die;
		}
		
		// Iterate each symbol found
		foreach ($symbols as $key => $symbol) {
			$name = $symbol["name"];
			$file = basename($symbol["path"]);
			$line = $symbol["line"];
			
			$path = $symbol["path"];
			print("<tr><td id=\"$key\" path=\"$path\" line=\"$line\" onclick=\"setSelection($key)\"><a href=\"txmt://open/?url=file://$path&line=$line\">Go to <b>$name</b> in $file</a></td></tr>\n");
		}
		print("</table>");
	} else {
		print("<p class=\"message\">The symbol <b>$query</b> can not be found in the current project.</p><p class=\"note\" align=\"center\">press return to close</p>");
	}
?>
</div>
</body>
</html>
