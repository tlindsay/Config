<?php 
	$_ENV['TM_BUNDLE_SUPPORT'] = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";
	$_ENV["TARGET"] = "development";
	$_ENV["TM_PROJECT_DIRECTORY"] = "/Users/ryanjoseph/Desktop/Projects/FPC_Projects/Desktops";

	require_once("common.php");
	require_once("target_loader.php");
	require_once("gui.php");

	// Strings for HTML support	
	$script_path = $_ENV['TM_BUNDLE_SUPPORT']."/project_search.php";
	$query_string = "desktop";
	
	// Reload
	$input = script_input();
	if ($input["reload"]) {
		print_r($input);
		die;
		$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
		$results = array();
		
		// Get query string
		if ($input["query"]) $query_string = $input["query"];
		
		if (!$query_string) $gui->print_error("Invalid query", true);
		
		die;
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<!-- Common Styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/gui.css" type="text/css" rel="stylesheet" media="screen"/>

<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/form.js" type="text/javascript" charset="utf-8"></script>
</head>
<body onload="reload('<?php echo $script_path ?>', null);">

<div class="toolbar">
	<p class="title">Project Find</p>
	<input class="toolbar-item button seperator" type="button" value="Search" class="button" onclick="reload('<?php echo $script_path; ?>', null);"/>
	<input class="toolbar-item search-field" type="text" size="20" name="query" value="<?php echo $query_string ?>" onkeydown="reload_on_return(event, '<?php echo $script_path; ?>', null);">
	
	<select class="toolbar-item popup-menu" name="section">
		<option value="0">Any</option>
		<option value="1">Unit</option>
		<option value="2">Program</option>
		<option value="3">Interface</option>
		<option value="4">Implementation</option>
	</select>
	
	<select class="toolbar-item popup-menu" name="filter">
		<option value="0">Any</option>
		<option value="1">Functions</option>
		<option value="2">Methods</option>
		<option value="3">Classes</option>
		<option value="4">Types</option>
	</select>
	
	<span class="toolbar-item text">Regex</span><input class="toolbar-item checkbox" type="checkbox" name="regex" />
	<span class="toolbar-item text">Whole Words</span><input class="toolbar-item checkbox" type="checkbox" name="whole-words" />
	<span class="toolbar-item text">Ignore Case</span><input class="toolbar-item checkbox" type="checkbox" name="ignore-case" />
</div>

<div class="main-pane">
	<div id="page-content"></div>
</div>

</body>
</html>
