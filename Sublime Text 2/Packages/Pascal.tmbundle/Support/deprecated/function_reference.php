<?

ini_set("memory_limit", "64M");
require_once("plist.php");

class FunctionReference {
	
	// Patterns for header parser
	const REGEX_FUNCTION = "function[[:space:]]+([a-zA-Z0-9_]+)[[:space:]]*(\((.*)\))*:[[:space:]]*([a-zA-Z0-9_]+);";
	const REGEX_PROCEDURE = "procedure[[:space:]]+([a-zA-Z0-9_]+)[[:space:]]*(\((.*)\))*;";
	const REGEX_OBJCCLASS = "[[:space:]]*([a-zA-Z0-9_]+)[[:space:]]+=[[:space:]]+(objcclass|objccategory|objcprotocol)+[[:space:]]*(\((.*)\))*";
	const REGEX_TYPE = "[[:space:]]?([a-zA-Z0-9_]+)[[:space:]]+=[[:space:]]+(.*);";
	const REGEX_OBJC_METHOD = "(class)*[[:space:]]*(function|procedure)[[:space:]]+([a-zA-Z0-9_]+)(\()*.*(\))*[[:space:]]*message[[:space:]]*'(.*)'[[:space:]]*(;|:)";
	const REGEX_EXTERNAL_SYMBOL = "^[[:space:]]*(var)*[[:space:]]+([a-zA-Z0-9]+)[[:space:]]*:.*;[[:space:]]*external[[:space:]]+name[[:space:]]+'_[a-zA-Z0-9]+'[[:space:]]*;";
	
	// Icon kinds for symbols
	const ICON_ROUTINE = 1;
	const ICON_CLASS = 2;
	const ICON_TYPE = 3;
	const ICON_METHOD = 4;
	const ICON_SYMBOL = 5;
	
	// Symbol field ids
	const SYMBOL_FIELD_NAME = 0;
	const SYMBOL_FIELD_SOURCE = 1;
	const SYMBOL_FIELD_PATH = 2;
	const SYMBOL_FIELD_LINE = 3;
	const SYMBOL_FIELD_KIND = 4;

	public $paths = array();																		// Array of paths to search for units to parse

	private $exclude_files = array("MacOSAll.pas");							// Array of files to exclude during batch parses
	private $symbols = array();																	// Master symbol table parsed from units
	private $pascal_source_expression = ".*\.(pas|p|pp|inc)+$";	// Regex to match Pascal source files
	private $reference_file_name = "symbols.reference";					// Name of the reference file
	private $directory_support;																	// Bundle support path
	private $directory_output;																	// Directory which the reference file resides
	
	private $reference_xml;									// Path to the reference XML file
	private $xml;														// SimpleXML object containing the loaded paths
	private $print_status_messages = false;	// Parser messages will printed to STDOUT
	private $parse_only = array();					// I supplied with file names it will only parse these files
	
	// Prints the function reference file
	public function print_output () {
		@mkdir($this->directory_output, 0777);
		
		if ($this->print_status_messages) print("Printing function reference to $this->directory_output/$this->reference_file_name...\n");		
		
		if ($handle = fopen("$this->directory_output/$this->reference_file_name", "w+")) {
			
			foreach ($this->symbols as $name => $symbol) {
				fwrite($handle, $name."|".$symbol["source"]."|".$symbol["path"]."|".$symbol["line"]."|".$symbol["kind"]."\n");
			}
			
			fclose($handle);
		}
	}
		
	// Parse the parameters into array
	private function parse_parameters ($list) {
		//var1: foo; var number2: int
		$vars = explode(";", $list);
		$params = array();
		//print_r($vars);
		//exit;
		foreach ($vars as $var) {
			$pair = explode(":", $var);
			
			$type = trim($pair[1], " ");
			$name = trim($pair[0], " ");
			
			$params[$name] = $type;
		}
		
		return $params;
	}
		
	// Parse the file by line and collect functions
	public function parse ($path) {
		$lines = file($path); //FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
		$count = 0;
		$source_trim = " 	\n";
		
		foreach ($lines as $line) {
			$count++;
			
			if (eregi(self::REGEX_OBJCCLASS, $line, $captures)) {
				//$this->symbols[$captures[1]]["super"] = $captures[3];
				//$this->symbols[$captures[1]]["class_type"] = $captures[2];
				$this->symbols[$captures[1]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[1]]["path"] = $path;
				$this->symbols[$captures[1]]["line"] = $count;
				$this->symbols[$captures[1]]["kind"] = self::ICON_CLASS;
				continue;
			}
			
			if (eregi(self::REGEX_TYPE, $line, $captures)) {
				$this->symbols[$captures[1]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[1]]["path"] = $path;
				$this->symbols[$captures[1]]["line"] = $count;
				$this->symbols[$captures[1]]["kind"] = self::ICON_TYPE;
				continue;
			}

			if (eregi(self::REGEX_OBJC_METHOD, $line, $captures)) {
				$this->symbols[$captures[3]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[3]]["path"] = $path;
				$this->symbols[$captures[3]]["line"] = $count;
				$this->symbols[$captures[3]]["kind"] = self::ICON_METHOD;
				continue;
			}

			if (eregi(self::REGEX_EXTERNAL_SYMBOL, $line, $captures)) {
				$this->symbols[$captures[2]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[2]]["path"] = $path;
				$this->symbols[$captures[2]]["line"] = $count;
				$this->symbols[$captures[2]]["kind"] = self::ICON_SYMBOL;
				continue;
			}

			if (eregi(self::REGEX_FUNCTION, $line, $captures)) {
				//$this->symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
				$this->symbols[$captures[1]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[1]]["path"] = $path;
				$this->symbols[$captures[1]]["line"] = $count;
				$this->symbols[$captures[1]]["kind"] = self::ICON_ROUTINE;
				continue;
			}
			
			if (eregi(self::REGEX_PROCEDURE, $line, $captures)) {
				//$this->symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
				$this->symbols[$captures[1]]["source"] = trim($line, $source_trim);
				$this->symbols[$captures[1]]["path"] = $path;
				$this->symbols[$captures[1]]["line"] = $count;
				$this->symbols[$captures[1]]["kind"] = self::ICON_ROUTINE;
				continue;
			}
			
		}
		
		// Print a status message
		if ($this->print_status_messages) print("Parsed ".basename($path)." ($count lines)\n");
	}
	
	// Prints the results output in HTML format
	public function print_results ($results) {
		$template = file_get_contents("$this->directory_support/reference_template.html");
		$files = array();
		$result_count = 0;
		
		// Replace RESULTS macro
		if (count($results) > 0) {
			$text = "";
			
			// Sort results by file
			foreach ($results as $key => $value) {
				$file = basename($value[self::SYMBOL_FIELD_PATH]);
				
				$info["name"] = $key;
				$info["path"] = $value[self::SYMBOL_FIELD_PATH];
				$info["line"] = trim($value[self::SYMBOL_FIELD_LINE], " 	\n");
				$info["source"] = $value[self::SYMBOL_FIELD_SOURCE];
				$info["kind"] = $value[self::SYMBOL_FIELD_KIND];

				$files[$file][] = $info;
				$result_count++;
			}
			
			$source_count = 0;
			$text .= "<dl id=\"TJK_DL\">\n";

			foreach ($files as $file => $result) {
				$text .= "<p class=\"file\">$file</p>\n";
				
				foreach ($result as $info) {
					$source_count++;
					
					$path = $info["path"];
					$line = $info["line"];
					$source = $info["source"];
					$name = $info["name"];
					
					// Get the icon file based on the symbol kind
					switch ($info["kind"]) {
						case self::ICON_ROUTINE: {
							$kind = "function.icns";
							break;
						}
						
						case self::ICON_CLASS: {
							$kind = "type.icns";
							break;
						}

						case self::ICON_TYPE: {
							$kind = "class.icns";
							break;
						}
						
						case self::ICON_METHOD: {
							$kind = "method.icns";
							break;
						}
						
						case self::ICON_SYMBOL: {
							$kind = "global.icns";
							break;
						}
						
						default:
							# code...
							break;
					}
					$icon = "<img src=\"file://".$_ENV["TM_BUNDLE_SUPPORT"]."/Icons/$kind\">";	// ??? show an icon representing the type
					
					$text .= "<dt><a href=\"txmt://open/?url=file://$path&line=$line\" class=\"function\"> $name</a></dt>\n";
					$text .= "<dd><span class=\"source\">$source</span></dd>\n";
				}
			} 
			
			$text .= "</dl>\n";
			
			// ??? AJAX Test
			/*
			foreach ($results as $key => $value) {
				$path = $value[self::SYMBOL_FIELD_PATH];
				$formatter = $_ENV["TM_BUNDLE_SUPPORT"]."/pascal_text_formatter.php";
				$line = trim($value[self::SYMBOL_FIELD_LINE], " 	\n");
				
				$text .= "<a href=\"txmt://open/?url=file://$path&line=$line\">$key ($line)</a><br />\n";
				//$text .= "<a href=\"javascript:displayPreview('/usr/bin/php \'$formatter\' \'$path\' $line ', $line);\">$key</a><br />\n";
			} 
			*/
			
			$template = str_ireplace("[RESULTS]", $text, $template);
		} else {
			die("<p>No results found.</p>");
		}
		
		// Replace other macros
		$template = str_ireplace("[RESULT_COUNT]", $result_count, $template);
		$template = str_ireplace("[FILE_COUNT]", count($files), $template);
		$template = str_ireplace("[TM_BUNDLE_SUPPORT]", $_ENV["TM_BUNDLE_SUPPORT"], $template);

		// Print the template HTML to STDOUT
		print($template);
	}
	
	// Queries the reference file
	public function query ($query) {
		
		// Throw a fatal error if the directory can't be found
		if (!file_exists("$this->directory_output/$this->reference_file_name")) {
			die("The function reference can not be found. Please run the function reference indexer before attempting to look up symbols.\n");
		}
		
		$results = array();
		$path = "$this->directory_output/$this->reference_file_name";
		$lines = file($path);

		// Iterate the lines and look for the matching queries
		if ($lines) {
			foreach ($lines as $line) {
				$fields = explode("|", $line);
				$name = $fields[self::SYMBOL_FIELD_NAME];
				
				if (eregi($query, $name)) {
					$results[$name] = $fields;
				}
			}
		}

		return $results;
	}
	
	// Performs a batch parse on all Pascal units found in the paths array
	public function batch_parse () {
		$index_count = 0;
		
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$starttime = $mtime; 
		
		// Check for valid paths
		foreach ($this->paths as $path) {
			if (!file_exists($path)) return "The reference path \"$path\" could not be found.";
		}
		
		// Parse all files in the directory
		foreach ($this->paths as $path) {
			if ($files = scandir($path)) {
				foreach ($files as $file) {
					
					// Only parse these files
					if (count($this->parse_only) > 0) {
						if (in_array($file, $this->parse_only)) {
							$this->parse("$path/$file");
							$index_count++;
						}
					} else {
						// Parse all files if only files are null
						if ((eregi($this->pascal_source_expression, $file)) && (!in_array($file, $this->exclude_files))) {
							$this->parse("$path/$file");
							$index_count++;
						}
					}

				}
			}
		}
		
		// Clean up the symbols array
		array_unique($this->symbols);
		ksort($this->symbols);
		
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$endtime = $mtime; 
		$totaltime = round($endtime - $starttime); 
		
		// Return a status message for those interested
		$symbol_count = count($this->symbols);
		
		return "Indexed $index_count files for $symbol_count symbols in $totaltime seconds.";
	}

	// Saves the reference XML file with new paths
	public function save ($paths) {
		$this->xml = new SimpleXMLElement("<paths></paths>");

		//print_r($paths);
		foreach ($paths as $path) {
			$this->xml->addChild("path", "$path");
		}
		
		//print($this->xml->asXML());
		return file_put_contents($this->reference_xml, $this->xml->asXML());
	}

	// Loads the function reference paths from XML
	private function load_paths () {
		$this->reference_xml = "$this->directory_support/reference_paths.xml";
		
		$this->xml = new SimpleXMLElement(file_get_contents($this->reference_xml));
		$paths = array();
		
		for ($i=0; $i < 10000; $i++) { 
			if ($this->xml->path[$i]) {
				$paths[] = (string)$this->xml->path[$i];
			} else {
				break;
			}
		}
		
		return $paths;
	}
	
	function __construct ($directory_support, $directory_output) {
		$this->directory_support = $directory_support;
		$this->directory_output = $directory_output;

		// Load the search paths from XML
		$this->paths = $this->load_paths();
	}
}

// ??? DEBUGGING

/*
$path = "/Users/ryanjoseph/Library/Application Support/TextMate/Pristine Copy/Bundles/Pascal.tmbundle/Support";
$reference = new FunctionReference($path, $path);
$reference->batch_parse();
*/
//$reference->print_output();

//$reference->save(array("foo1", "foo2", "foo3"));
//$results = $reference->query("Window");
//$reference->print_results($results);
//print_r(count($results));

?>