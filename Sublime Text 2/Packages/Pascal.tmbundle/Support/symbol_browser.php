<?php 
	//$_ENV['TM_BUNDLE_SUPPORT'] = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";
	//$_ENV["TARGET"] = "development";
	//$_ENV["TM_PROJECT_DIRECTORY"] = "/Users/ryanjoseph/Desktop/Projects/FPC_Projects/Desktops";
	
	require_once("preferences.php");
	require_once("common.php");
	require_once("symbol_parser.php");
	require_once("target_loader.php");
		
	// Strings for HTML support	
	$script_path = $_ENV['TM_BUNDLE_SUPPORT']."/symbol_browser.php";
	//$query_string = ".*";
	
	// Reload
	$input = script_input();
	if ($input["reload"]) {
				
		// Parser the classes and print the list
		// Load the target
		$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
		$parser = new SymbolParser($target->get_symbols_directory(), $target->get_resolved_symbols());

		$parser->print_messages = true;
		$parser->print_html = true;
		$parser->case_insensitive = true;

		$query_string = $input["query"];
		$filter = explode(",", $input["filter"]);

		// ??? ack! we need to pass the filter INTO the symbol parser to limit results! better for memory
		// also we need * wild card searches
		$symbols = $parser->query_2($query_string);
		$results = 0;
		
		if (count($symbols) > 0) {
			//$gui->list_open
			print("<table class=\"table-view\" width=\"100%\">");
			foreach ($symbols as $key => $symbol) {
				$name = $symbol["name"];
				$file = basename($symbol["path"]);
				$line = $symbol["line"];
				$path = $symbol["path"];
				$kind = $symbol["kind"];

				if (in_array($kind, $filter) || ($input["filter"] == 0)) {
					$results += 1;
					//$gui->add_row()
					if ($results&1) {
						$style = "table-view-row-odd";
					} else {
						$style = "table-view-row";
					}
					print("<tr><td class=\"$style\"><a class=\"table-view-row\" href=\"txmt://open/?url=file://$path&line=$line\"><b>$name</b> in $file</a></td></tr>\n");
				}
			}
			//$gui->list_close
			print("</table>");
		}
		
		if ($results == 0) {
			print("<p style=\"padding:30px\">No results were found.</p>");
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
<script type="text/javascript">	
	function script_terminated (object) {
	}
</script>

</head>
<body onload="_reload('<?php echo $script_path ?>', null);">

<div class="toolbar">
	<p class="title">Symbol Browser</p>
		<input class="toolbar-item button seperator" type="button" value="Find" class="button" onclick="reload('<?php echo $script_path; ?>', null);"/>
		<input class="toolbar-item search-field" type="text" size="20" name="query" value="<?php echo $query_string ?>" onkeydown="reload_on_return(event, '<?php echo $script_path; ?>', null);">
		<select class="toolbar-item popup-menu" name="filter" onchange="reload('<?php echo $script_path; ?>', null);">
			<option value="0">Any</option>
			<option value="1,2">Functions</option>
			<option value="3,4">Methods</option>
			<option value="5">Classes</option>
			<optgroup label="Types">
				<option value="6">Declarations</option>
				<option value="7">Records</option>
				<option value="8">External</option>
			</optgroup>
			<optgroup label="Objective C">
				<option value="9">Protocols</option>
				<option value="10">Categories</option>
				<option value="11">Interfaces</option>
				<option value="13">Classes</option>
			</optgroup>
		</select>
</div>

<div class="main-pane">
	<div id="page-content"></div>
</div>

</body>
</html>
