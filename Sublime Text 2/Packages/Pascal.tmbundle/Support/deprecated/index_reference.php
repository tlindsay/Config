<?php 
	require_once($_ENV['TM_BUNDLE_SUPPORT']."/function_reference.php");
	require_once($_ENV['TM_BUNDLE_SUPPORT']."/common.php");
	
	$reference = new FunctionReference($_ENV["TM_BUNDLE_SUPPORT"], $_ENV['TM_BUNDLE_PATH']."/Reference");
	
	$input = script_input();
	
	if ($GLOBALS["argv"][1] == "-save") {
		if ($reference->save($input["paths"])) {
			die("Saved successfully\n");
		} else {
			die("There was an error saving the paths.\n");
		}
	}
	
	if ($GLOBALS["argv"][1] == "-index") {
		$message = $reference->batch_parse();
		$reference->print_output();
		die($message);
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<style type="text/css">

body {
	font-size: 10pt;
	font-family: Verdana;
}

p.title {
	float: left;
	left: 40px;
	text-shadow: 0px 1px 2px #7f7f7f;
	font-weight: bold;
	font-size: 11pt;
}

.container {
	margin: 10px;
}

textarea {
	overflow: scroll;
}

input.button {
	font-family: sans-serif;
	border-style: groove;
	border-color: #666666;
	border-width: 1px;
	font-weight: bold;
	background-color: #cccccc;
	margin-right: 20px;
	float: right;
}

input,textarea {
	border-color: #7f7f7f;
	border-width: 1px;
	border-style: solid;
	background-color: #e6e6e6;
	font-size: 9pt;
	font-family: monospace;
}

.toolbar {
	background-color: #e6e6e6;
	background-repeat: repeat-x;
	margin: 0px;
	left: 0px;
	top: 0px;
	height: 24px;
	width: 100%;
	padding: 8px;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	position: fixed;
}

.section {
	font-weight: bold;
	font-size: 9pt;
}

.note {
	color: #4c4c4c;
	font-style: italic;
	font-size: 8pt;
}

</style>

<script type="text/javascript">

// Saves the function reference
function save (notify) {
	if (TextMate.isBusy) {
		show_alert("Indexing in progress.");
		return;
	}
	
	var paths = document.getElementById('paths');
	var options = paths.name+"='"+paths.value+"' ";
	
	var command = "/usr/bin/php \"<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/index_reference.php\" -save "+options;
	var output = TextMate.system(command, null).outputString;
	
	if (notify) show_alert(output);
}

function index_handler (output) {
	TextMate.isBusy = false;
	
	show_alert(output.outputString);
}

// Indexes the function reference
function index () {
	
	// Save the paths first
	save(false);
	
	if (TextMate.isBusy) {
		show_alert("Indexing in progress.");
		return;
	}
	
	var command = "/usr/bin/php \"<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/index_reference.php\" -index";
	
	TextMate.isBusy = true;
	output = TextMate.system(command, index_handler);
}

function unload () {
}

function load () {
	window.resizeTo(512, 335);
}

</script>

<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/popup_alert.js" type="text/javascript" charset="utf-8"></script>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/styles.css" type="text/css" rel="stylesheet" />

</head>
<body onload="load();" onunload="unload();">

<!-- PopupAlert -->	
<div id="alert-background">
	<div id="alert-box" onclick="hide_alert();">
		<p id="alert-message"></p>
	</div>
</div>
	
<div class="toolbar">
	<p class="title">Reference Library Indexer</p>
	<input type="submit" value="Save" class="button" onclick="save(true);"/>
	<input type="submit" value="Index" class="button" onclick="index();"/>
</div>

<br><br><br>

<div class="container">
	<textarea cols="65" rows="10" name="paths" id="paths"><?php print(implode("\n", $reference->paths)); ?></textarea>
	<p class="note">Paths to search for reference symbols (without recursion).</p>
</div>

</body>
</html>
