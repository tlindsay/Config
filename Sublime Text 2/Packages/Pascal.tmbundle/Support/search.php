<?php 
$_ENV['TM_BUNDLE_SUPPORT'] = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";
$_ENV["TARGET"] = "development";
$_ENV["TM_PROJECT_DIRECTORY"] = "/Users/ryanjoseph/Desktop/Projects/FPC_Projects/Desktops";

require_once("gui.php");
require_once("preferences.php");
require_once("target_loader.php");

define("SEARCH_OPTION_WHOLE_WORDS", "option-whole-words");
define("SEARCH_OPTION_REGEX", "option-regex");
define("SEARCH_OPTION_IGNORE_CASE", "option-ignore-case");

define("SEARCH_FILTER_METHODS", "methods");
define("SEARCH_FILTER_FUNCTIONS", "functions");
define("SEARCH_FILTER_TYPES", "types");
define("SEARCH_FILTER_DECLARATIONS", "declarations");

define("SEARCH_SECTION_UNIT", "unit");
define("SEARCH_SECTION_PROGRAM", "program");
define("SEARCH_SECTION_INTERFACE", "interface");
define("SEARCH_SECTION_IMPLEMENTATION", "implementation");

class Search {
	
	private $options = array();
	private $filter = array();
	private $section = null;
	private $file_types = array("pas", "pp", "p", "inc");		// Only search these file types
	
	public function set_option ($option) {
		$this->options[] = $option;
	}
	
	public function set_filter ($option) {
		
	}
	
	public function set_section ($section) {
		$this->section = $section;
	}
	
	public function display_results ($results) {
		global $gui;
		
		// Display results
		$gui->table_row_style = "font-size:9pt";
		$gui->table_open("margin: 20px");
		foreach ($results as $file => $result) {
			$gui->table_add_header($file);
			foreach ($result as $info) {
				$gui->table_add_row($info["line"], "color: #333333; padding-left:20px");
			}
		}
		$gui->table_close();
	}
	
	function query ($paths, $query) {
		$results = array();
		
		// Search each resolved path in the target
		foreach ($paths as $path) {

			// Iterate each file in the directory
			if ($files = directory_contents($path)) {
				foreach ($files as $file) {

					// Iterate each line of the file
					if ($lines = file($file)) {
						$line_index = 0;
						$file_name = basename($file);
						$section = null;
						//print("Searching $file_name...\n");
						
						foreach ($lines as $line) {
							
							// Get the current source section
							if (preg_match("/^\s*interface/i", $line)) $section = "interface";
							if (preg_match("/^\s*implementation/i", $line)) $section = "implementation";
							if (preg_match("/^\s*program/i", $line)) $section = "program";
							if (preg_match("/^\s*unit/i", $line)) $section = "unit";
							
							if (($section != $this->section) && ($this->section != null)) continue;
							
							// Filter Pascal syntax
							/*
							$pattern = null;
							if (in_array(SEARCH_FILTER_DECLARATIONS, $this->filter)) $pattern = "/\b$query\b/";
							
							if ($pattern) {
								if (!preg_match($pattern, $line)) continue;
							}
							*/
							
							// Get the regex pattern based on options
							$pattern = "/$query/";
							
							if (in_array(SEARCH_OPTION_WHOLE_WORDS, $this->options)) $pattern = "/\b$query\b/";
							if (in_array(SEARCH_OPTION_IGNORE_CASE, $this->options)) $pattern = $query."i";
							
							// If the query is a regular expression replace the entire pattern with the query string
							if (in_array(SEARCH_OPTION_REGEX, $this->options)) $pattern = $query;

							// Match the line against the pattern
							if (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
								//matches[0][1]
								$result = array("index"=>$line_index, "line"=>$line, "offset"=>$offsets);
								$results[$file_name][] = $result;
							}
							
							$line_index += 1;
						}	
					}

				}
			}
		}
		
		return $results;
	}
	
	// Constructor
	function __construct() {
	}
}
		
$search = new Search();		
$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
$paths = $target->get_resolved_paths();

$search->set_section(SEARCH_SECTION_IMPLEMENTATION);
$search->set_option(SEARCH_OPTION_WHOLE_WORDS);

if ($results = $search->query($paths, "desktop")) {
	print_r($results);
	//$search->display_results($results);
}
?>