<?

// XIB Parser by Ryan Joseph (www.thealchemistguild.org) 12/19/2009

class XIBParser {
	
	// These are default classes in every XIB we should not accept
	var $default_classes = array("NSFontManager", "NSApplication", "FirstResponder");
	var $classes = array();
	var $connections = array();
	var $unit_handle;
	var $indent_length = " ";
	
	private function writeln ($indent, $line) {
		$indent_string = "";
		for ($i=0; $i < $indent; $i++) { 
			$indent_string .= $this->indent_length;
		}
		
		fwrite($this->unit_handle, "$indent_string$line\n");
	}
	
	public function print_unit ($path, $name) {
		if ($this->unit_handle = fopen($path, "w+")) {
			$this->writeln(0, "unit $name;");
			$this->writeln(0, "interface");
			$this->writeln(0, "uses");
			$this->writeln(1, "CocoaAll;");
			$this->writeln(0, "");

			// Interface
			foreach ($this->classes as $class) {
				$this->writeln(0, "type");
				$this->writeln(1, $class["name"]." = objcclass (NSObject)");
				$this->writeln(2, "private");

				// IBOutlets
				foreach ($class["outlets"] as $outlet) {
					$name = $outlet["name"];
					$type = $outlet["type"];
					$this->writeln(3, "$name: $type;");
				}
				$this->writeln(0, "");

				// IBActions
				foreach ($class["actions"] as $action) {
					$this->writeln(3, "procedure $action (sender: id); message '$action:';");
				}

				$this->writeln(1, "end;");
			}

			// Implementation
			$this->writeln(0, "");
			$this->writeln(0, "implementation");
			$this->writeln(0, "");

			foreach ($this->classes as $class) {
				$class_name = $class["name"];

				// IBActions
				foreach ($class["actions"] as $action) {
					$this->writeln(0, "procedure $class_name.$action (sender: id);");
					$this->writeln(0, "begin");
					$this->writeln(0, "end;");
				}

			}

			$this->writeln(0, "");
			$this->writeln(0, "end.");
		}
	}
	
	private function got_object ($node) {
		$class = $node["class"];
		
		// Collection connection records to assign with outlets
		if (isset($node["class"]) && isset($node["id"])) {
			$this->connection[(string)$node["id"]] = (string)$node["class"];
		}
		
		switch ($class) {
			 
			case "IBOutletConnection":

				foreach ($node->reference as $reference) {
					
					if ($reference["key"] == "source") {
						$source = (string)$reference["ref"];
						
						// Add outlet to class with source ID
						if (isset($this->classes[$source])) {
							$outlet = array();
							$outlet["name"] = (string)$node->string;
							$outlet["type"] = "id";

							$this->classes[$source]["outlets"][] = $outlet;
						}
					}
					
					if ($reference["key"] == "destination") {
						$destination = (string)$reference["ref"];
						
						// Assign outlet type from connection records
						if (isset($this->connection[$destination])) {
							foreach ($this->classes[$source]["outlets"] as $index => $outlet) {
								if ($outlet["name"] == (string)$node->string) {
									$this->classes[$source]["outlets"][$index]["type"] = $this->connection[$destination];
									//print("$node->string ($index)\n");
								}
							}
						}
					}
					
				}
				
				break;

			case "IBActionConnection":

				foreach ($node->reference as $reference) {
					if ($reference["key"] == "source") {
						$source = (string)$reference["ref"];
						$name = trim((string)$node->string, ":");
						
						// Add action to class with source ID
						if (isset($this->classes[$source])) $this->classes[$source]["actions"][] = $name;
					}
				}
				
				break;
				
			case "NSCustomObject":
			
				// Append the custom class with ID
				$custom_object = (string)$node->string;
				$id = (string)$node["id"];
				if (!in_array($custom_object, $this->default_classes)) {
					$this->classes[$id]["name"] = $custom_object;
				}
				
				//print($node->string."\n");
				break;
		}
		
	}
	
	private function examine_data ($node) {
		foreach ($node as $child) {
			if (is_object($child)) {
				
				// Valid object node
				if (isset($child["class"])) $this->got_object($child);
				
				// Recurse into object
				$this->examine_data($child);
			}
		}
	}
	
	function __construct ($xib_path, $unit_path, $unit_name, $indent) {
		$xml = new SimpleXMLElement(file_get_contents($xib_path));
		
		if (!$indent) $this->indent_length = " ";
		
		print("Examining data...\n");
		$this->examine_data($xml->data);
		
		print("Printing class...\n");
		$this->print_unit($unit_path, $unit_name);
		
		print("Finished.\n");
		//print_r($this->classes);
	}
	
}

foreach($GLOBALS["argv"] as $key => $value) {
	$pair = explode("=", $value);
	if (count($pair) == 2) {
		$pair[1] = trim($pair[1], "\"");
		$input[$pair[0]] = $pair[1];
	}
}

//	/Users/ryanjoseph/Desktop/fpc installer.xib
//	/Users/ryanjoseph/Desktop/MyUnit.pas

//	php /Users/ryanjoseph/Desktop/scripts/xib_parser.php xib="/Users/ryanjoseph/Desktop/fpc installer.xib" path="/Users/ryanjoseph/Desktop/MyUnit.pas" unit="MyUnit" indent=" "
//	php /Users/ryanjoseph/Desktop/scripts/xib_parser.php xib="/Users/ryanjoseph/Desktop/MainMenu.xib" path="/Users/ryanjoseph/Desktop/MyUnit.pas" unit="MyUnit" indent=" "
	
$parser = new XIBParser ($input["xib"], $input["path"], $input["unit"], $input["indent"]);

?>