<?php	
	$target = $_ENV["TARGET"];
	$project = $_ENV["TM_PROJECT_DIRECTORY"];
	
	if (isset($_ENV["TM_SELECTED_TEXT"])) {
		$query = $_ENV["TM_SELECTED_TEXT"];
	} else {
		$query = $_ENV["TM_CURRENT_WORD"];
	}
	
	require_once($_ENV["TM_BUNDLE_SUPPORT"]."/target_loader.php");
	require_once($_ENV["TM_BUNDLE_SUPPORT"]."/symbol_parser.php");
  
	// Load the target and PPU parser
	$loader = new TargetLoader($project, $target);
	$parser = new SymbolParser($loader->get_symbols_directory(), null);
	$table = array();
	
	// Query for the symbol
	if ($symbols = $parser->query_2($query)) {
		
		// Iterate each symbol found
		foreach ($symbols as $key => $symbol) $table[] = $symbol["name"];
	}
	
	// Save the completions array to disk so it can be read by Ruby.
	// Note, this is a inefficient method but all I know how to do with Ruby
	$table_path = $_ENV["TM_PROJECT_DIRECTORY"]."/.tm_completions";
	file_put_contents($table_path, implode("\n", $table));
	
	// Exec the ruby script which loads the text table and opens the GUI
	$cmd = "ruby \"".$_ENV["TM_BUNDLE_SUPPORT"]."/symbol_completion.rb\"";
	printf("%s\n", `$cmd`);
	
	// Delete the temporary table file
	unlink($table_path);
?>
