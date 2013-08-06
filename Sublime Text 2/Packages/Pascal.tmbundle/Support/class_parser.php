<?php

require_once("symbol_parser.php");
require_once("preferences.php");
require_once("common.php");

class ClassParser extends SymbolParser {
	
	const FILTER_DISABLED = 0;
	const FILTER_ENABLED = 1;
	const FILTER_ACTIVE = 2;
	const FILTER_TERMINATED = 3;

	private $methods = false; 			// under construction: show methods in list
	private $filter = 0;						// The current class filter state
	private $class_filter = null;		// If the set only this class will be displayed
	
	// Utility method to return the parent name for class (array)
	private function class_parent ($class) {
		$parts = explode(",", $class["parent"]);
		if ($parts) {
			$parent = $parts[0];
		} else {
			$parent = $class["parent"];
		}
		
		// Prevent infinite recursion
		if ($parent == $class["name"]) $parent = null;
		
		return $parent;
	}
	
	// Returns a class parent (array) for the class name
	private function find_class_tree ($find, $classes, &$tree) {
		
		foreach ($classes as $class) {
			if ($class["name"] == $find) {
				$tree[] = $class;
				//print("$find\n");
				if ($parent = $this->class_parent($class)) {
					$this->find_class_tree($parent, $classes, $tree);
				}
			}
		}
	}
		
	// Compares 2 class arrays by name
	private function compare_class ($a, $b) {
    if ($a["class"]["name"] == $b["class"]["name"]) {
        return 0;
    }
    return ($a["class"]["name"] < $b["class"]["name"]) ? -1 : 1;
	}
	
	// Prints classes in HTML list format
	public function print_classes ($depth, $classes) {
		global $preferences;
		$filtering = false;
		
		// No classes are available, bail!
		if (count($classes) == 0) {
			print("No classes are available.");
			return;
		}
		
		$indent = "";
		for ($i=0; $i < $depth; $i++) { 
			$indent .= "	";
		}
		
		// Sort the classes
		usort($classes, array('ClassParser', 'compare_class'));
		
		foreach ($classes as $class) {
			$name = $class["class"]["name"];
			$path = $class["class"]["path"];
			$line = $class["class"]["line"];
			//print_r($class["class"]);

			// Set the filter state
			if (($this->filter == self::FILTER_ENABLED) && ($name == $this->filter_class)) {
				$this->filter = self::FILTER_ACTIVE;
				$filtering = true;
			}
			
			// Filter classes
			$filters = $preferences->get_array_value("class_browser_filter", "\n");
			$filtered = false;
			if (count($filters) > 0) {
				foreach ($filters as $filter) {
					//print("$name\n");
					if (preg_match($filter, $name)) {
						$filtered = true;
						continue;
					}
				}
			}
			
			// The class has been filtered out, continue
			if (($filtered) || ($this->filter == self::FILTER_TERMINATED)) continue;
			
			// Open parent or child
			if (($this->filter == self::FILTER_DISABLED) || ($this->filter == self::FILTER_ACTIVE)) {
				if ($class["child"]) {
					echo "$indent<li class=\"parent\" onmouseover=\"this.setFocus();\" onmouseout=\"this.clearFocus();\"><a href=\"txmt://open/?url=file://$path&line=$line\" title=\"$path\" class=\"parent\"> $name</a>\n";
				} else {
					echo "$indent<li class=\"child\"><a href=\"txmt://open/?url=file://$path&line=$line\" title=\"$path\" > $name</a></li>\n";
				}
			}

			// Methods
			/*
			if ($this->methods) {
				$methods = $class["class"]["methods"];
				if ($methods) {
					echo "$indent<li>Methods\n";
					echo "$indent<ul>\n";
				
					foreach ($methods as $method) {
						$name = $method["name"];
						$path = $method["path"];
						$line = $method["line"];

						echo "$indent<li><a href=\"txmt://open/?url=file://$path&line=$line\" class=\"symbol_method\"> $name</a></li>\n";
					}
				
					echo "$indent</ul>\n";
					echo "$indent</li>\n";
				}
			}
			*/
			
			// Recurse into subclasses
			if ($class["child"]) {
				$depth++;
				if (($this->filter == self::FILTER_DISABLED) || ($this->filter == self::FILTER_ACTIVE)) echo "$indent<ul>\n";
				$this->print_classes($depth, $class["child"]);
				if (($this->filter == self::FILTER_DISABLED) || ($this->filter == self::FILTER_ACTIVE)) echo "$indent</ul>\n";
				if (($this->filter == self::FILTER_DISABLED) || ($this->filter == self::FILTER_ACTIVE)) echo "$indent</li>\n";
			}
			
			// Set the filter to terminated after we left the filter class level
			if ($filtering) $this->filter = self::FILTER_TERMINATED;
			
		}
	}
		
	// Loads all classes into hierarchical array
	public function load_classes () {
		global $preferences;
		
		$symbols = array();
		$classes = array();
		$methods = array();
		
		// Load all classes into master table
		$paths = $this->find_symbol_tables();
		foreach ($paths as $path) {
			$table = $this->load_symbol_table($path);
			foreach ($table as $symbol) {
								
				// Methods
				if ($this->methods) {
					if ($symbol["kind"] == self::SYMBOL_DECLARED_METHOD) $symbols[$symbol["parent"]]["methods"][] = $symbol;
				}
				
				// Classes
				switch ($symbol["kind"]) {
					case self::SYMBOL_CLASS:
						if ($preferences->get_boolean_value("class_browser_pascalclass")) $symbols[$symbol["name"]] = $symbol;
						break;
					case self::SYMBOL_CLASS_OBJC:
						if ($preferences->get_boolean_value("class_browser_objcclass")) $symbols[$symbol["name"]] = $symbol;
						break;
					case self::SYMBOL_CLASS_PROTOCOL:
						if ($preferences->get_boolean_value("class_browser_objcprotocol")) $symbols[$symbol["name"]] = $symbol;
						break;
					case self::SYMBOL_CLASS_CATEGORY:
						if ($preferences->get_boolean_value("class_browser_objccategory")) $symbols[$symbol["name"]] = $symbol;
						break;
				}

			}
		}
		
		// Build trees
		$count = 0;
		foreach ($symbols as $class) {
			$tree = array();
			$this->find_class_tree($class["name"], $symbols, $tree);
			$tree = array_reverse($tree);

			// ??? walk each value then add to master array
			$parent = &$classes;

			foreach ($tree as $child) {
				$name = $child["name"];
				//print("$name\n");
				
				// Append new class
				if (!$parent[$name]) {
					$parent[$name]["class"] = $child;
				}
				
				// Append to previous parent
				$parent = &$parent[$name]["child"];
			}
			
			// DEBUGGING
			$count++;
			if ($count == 2) {
				//print_r($classes);
				//return $classes;
			}
		}
		
		return $classes;
	}
	
	public function set_filter ($class) {
		$this->filter = self::FILTER_ENABLED;
		$this->filter_class = $class;
	}
	
}

// ??? DEBUGGING
/*
$_ENV["TM_BUNDLE_SUPPORT"] = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";

$parser = new ClassParser("/Users/ryanjoseph/Desktop/Projects/FPC_Projects/Desktops/build.i386/symbols", null);
if ($classes = $parser->load_classes()) {
	// ??? make a open in class browser command that filters the current word
	$parser->set_filter("NSView");
	$parser->print_classes(0, $classes);
}
*/

?>