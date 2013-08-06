<?php 
	//$_ENV['TM_BUNDLE_SUPPORT'] = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";

	require_once("preferences.php");
	require_once("common.php");
	require_once("class_parser.php");
	require_once("target_loader.php");
		
	// Strings for HTML support	
	$script_path = $_ENV['TM_BUNDLE_SUPPORT']."/class_browser.php";
	
	if (!isset($filter_string)) $filter_string = $preferences->get_string_value('class_browser_filter_string');
	
	// Reload
	$input = script_input();
	if ($input["reload"]) {
		
		// Save the filter to preferences
		if ($input["filter"]) {
			$preferences->set_value('class_browser_filter_string', $input["filter"]);
			$preferences->save();
		}
				
		// Parser the classes and print the list
		// Load the target
		$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
		$parser = new ClassParser($target->get_symbols_directory(), null);
		if ($classes = $parser->load_classes()) {
			
			// Apply the filter string from input
			if ($input["filter"]) $parser->set_filter($input["filter"]);
			
			$parser->print_classes(0, $classes);
		}
		
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

<!-- jquery tree -->
<script type="text/javascript" src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/jquery-tree/jquery.min.js"></script>
<script type="text/javascript" src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/jquery-tree/jquery.tree.js"></script>

<script type="text/javascript">

	var focusedPane = null;
	
	HTMLElement.prototype.clearFocus = function() {
		//this.className = "parent";
		//focusedPane = null;
	}

	HTMLElement.prototype.setFocus = function() {
		//if ((focusedPane != null) && (focusedPane != this)) focusedPane.clearFocus();
		//this.className = "focused";
		//focusedPane = this;
	}
	
	function script_terminated (object) {
		$('ul#page-content').tree({default_expanded_paths_string : 'all'});

	}
</script>

<style type="text/css">
body {
	font-family: sans-serif;
	padding: 8px;
	font-size: 8pt;
	height: 100%;
	width: 100%;
}

li {
	padding: 2px;
	overflow: hidden;
}

li.parent {
	/*
	border-left-color: #4c4c4c;
	border-right-width: 0px;
	border-left-style: dashed;
	border-bottom-width: 0px;
	border-top-width: 0px;
	border-left-width: 1px;
	*/
	font-weight: bold;
}

li.focused {
	border-color: blue;
	border-style: dotted;
	border-width: 1px;
}

a.parent {
	background-color: #f6f6f6;
	-webkit-border-radius: 4px;
	border-color: #333333;
	border-width: 1px;
	border-style: inset;
	padding-left: 8px;
	padding-right: 8px;
	padding-top: 0px;
	padding-bottom: 1px;
	font-weight: bold;
}

ul#page-content {
	padding: 0px;
	margin: 0px;
	top: 40px;
	position: relative;
}

</style>

</head>
<body onload="reload('<?php echo $script_path ?>');">

<div class="toolbar">
	<p class="title">Class Browser</p>
		<input class="toolbar-item button seperator" type="button" value="Filter" class="button" onclick="reload('<?php echo $script_path; ?>', null);"/>
		<input class="toolbar-item search-field" type="text" size="20" name="filter" value="<?php echo $filter_string ?>" onkeydown="reload_on_return(event, '<?php echo $script_path; ?>', null);">
</div>

<div class="_main-pane">
	<ul id="page-content">
	</ul>
</div>

</body>
</html>
