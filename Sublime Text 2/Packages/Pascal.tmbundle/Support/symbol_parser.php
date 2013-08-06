<?php

/*
	The symbol parser generates an up to date symbol table based upon an array of source paths
*/

ini_set("memory_limit", "128M");
define("AUTO_COMPLETE_TABLE", "autocomplete.table"); 	// Name of the auto-complete table file

require_once("common.php");
require_once("target_loader.php");

class SymbolParser {
	
	public $print_messages = false;																					// Script messages will printed
	public $print_symbol_messages = false;																	// Syntax parser messages will be printed
	public $print_html = false;																							// All messages will printed in HTML
	public $print_prefix;																										// Messages will be prefixed with this string
	
	public $case_insensitive = false;					// Queries are case insensitive
	
	protected $directory;																										// Directory containg .symbol files	
	
	private $symbols = array();																							// Master symbol table
	private $ignore_files = array(	"MacOSAll.pas", 												// Array of files names to ignore
																	"IvarSize.pas"
																	);				
	private $pascal_source_expression = ".*\.(pas|p|pp|inc)+$";							// Regex to match Pascal source files
	private $paths = array();																								// Array of directory paths to search for source files
	
	// Regex scope patterns
	private $REGEX_SCOPE_INTERFACE = array(	"open" => "^[[:space:]]*interface", 
																					"close" => "^[[:space:]]*implementation",
																					"scope" => "interface",
																					);
	
	private $REGEX_SCOPE_IMPLEMENTATION = array(	"open" => "^[[:space:]]*implementation", 
																								"close" => "^[[:space:]]*end\.$",
																								"scope" => "implementation",
																								);
																						
	private $REGEX_SCOPE_CLASS = array(	"open" => "[[:space:]]*([a-zA-Z0-9_]+)[[:space:]]*=[[:space:]]*(class|object|interface|objcclass|objccategory|objcprotocol)+[[:space:]]*(external)*[[:space:]]*(\((.*)\))*", 
																			"close" => "^[[:space:]]*end;",
																			"scope" => "class",
																			"break" => ";"
																			);

	// Regex match patterns
	// NOTE: these should be have a variable scope but they are fixed in code inside parse_header
	const REGEX_FUNCTION = "function[[:space:]]+([a-zA-Z0-9_]+)[[:space:]]*(\((.*)\))*:[[:space:]]*([a-zA-Z0-9_]+);";
	const REGEX_PROCEDURE = "procedure[[:space:]]+([a-zA-Z0-9_]+)[[:space:]]*(\((.*)\))*;";
	const REGEX_TYPE = "^[[:space:]]*([a-zA-Z0-9_]+)[[:space:]]+=[[:space:]]+(.*);";
	const REGEX_RECORD = "^[[:space:]]*([a-zA-Z0-9_]+)[[:space:]]*=[[:space:]]*record";
	const REGEX_EXTERNAL_SYMBOL = "^[[:space:]]*(var)*[[:space:]]+([a-zA-Z0-9]+)[[:space:]]*:.*;[[:space:]]*external[[:space:]]+name[[:space:]]+'_[a-zA-Z0-9]+'[[:space:]]*;";	
	const REGEX_DECLARED_METHOD = "(class)*[[:space:]]*(function|procedure|constructor|destructor)[[:space:]]+([a-zA-Z0-9_]+)(\()*.*(\))*";
	const REGEX_IMPLEMENTED_METHOD = "(class)*[[:space:]]*(function|procedure|constructor|destructor)[[:space:]]+([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)(\()*.*(\))*";
	
	// Symbol kinds
	const SYMBOL_DECLARED_ROUTINE = 1;
	const SYMBOL_IMPLEMENTED_ROUTINE = 2;
	const SYMBOL_DECLARED_METHOD = 3;
	const SYMBOL_IMPLEMENTED_METHOD = 4;
	const SYMBOL_CLASS = 5;
	const SYMBOL_TYPE = 6;
	const SYMBOL_RECORD = 7;
	const SYMBOL_SYMBOL = 8;
	const SYMBOL_CLASS_PROTOCOL = 9;
	const SYMBOL_CLASS_CATEGORY = 10;
	const SYMBOL_CLASS_INTERFACE = 11;
	const SYMBOL_CLASS_TYPE = 12;
	const SYMBOL_CLASS_OBJC = 13;

	// Symbol field indexes (for flat file database)
	const SYMBOL_FIELD_NAME = 0;
	const SYMBOL_FIELD_PARENT = 1;
	const SYMBOL_FIELD_LINE = 2;
	const SYMBOL_FIELD_KIND = 3;

	/**
	 * Printing Utilities
	 */
	private function print_message ($message) {
		if ($this->print_messages) {
			if ($this->print_html) {
				print($this->print_prefix.$message."<br/>\n");
			} else {
				print($this->print_prefix.$message."\n");
			}
		}
		
		flush();
	}
	
	private function print_symbol_message ($message) {
		if ($this->print_symbol_messages) {
			if ($this->print_html) {
				print($this->print_prefix.$message."<br/>\n");
			} else {
				print($this->print_prefix.$message."\n");
			}
		}
		
		flush();
	}
	
	/**
	 * Methods
	 */

	// Prints a .symbols file
	public function print_symbol_file ($symbols, $header_path, $destination, $mod_time) {
		
		if ($handle = @fopen($destination, "w+")) {
			if ($symbols) {
				
				// Print header path
				fwrite($handle, "$header_path\n");
				
				// Print symbol fields
				foreach ($symbols as $name => $info) {
					
					// Print only the allowed symbol types
					fwrite($handle, $name."|".$info["parent"]."|".$info["line"]."|".$info["kind"]."\n");
					/*
					switch ($info["kind"]) {
						case self::SYMBOL_DECLARED_ROUTINE:
						case self::SYMBOL_IMPLEMENTED_METHOD:
						case self::SYMBOL_DECLARED_ROUTINE:
						case self::SYMBOL_IMPLEMENTED_METHOD:
						case self::SYMBOL_CLASS:
						case self::SYMBOL_TYPE:
						case self::SYMBOL_RECORD:
						case self::SYMBOL_SYMBOL: {
							fwrite($handle, $name."|".$info["parent"]."|".$info["path"]."|".$info["line"]."|".$info["kind"]."\n");
							
							break;
						}
					}*/
				}
			}
			
			fclose($handle);

			// Synch the modification date with the source file
			touch($destination, $mod_time, $time);
			
		} else {
			$this->print_message("There was an error printing symbols to ".basename($destination));
		}
	}
	
	// Sets the array of files to ignore (from the target loader)
	public function set_ignore_files ($array) {
		if ($array) $this->ignore_files = array_merge($this->ignore_files, $array);
	}
	
	// Terminates the $current scope block at $line then pops it off the $scope stack
	// and finally returns the new current scope block
	private function terminate_scope ($current, $line, &$scope) {
		
		if ($current) {
			if (eregi($current["terminate"], $line)) {
				//print("closed ".$current["kind"]." scope\n");
				array_pop($scope);
				$current = end($scope);
			}
		}
		
		return $current;
	}
	
	// Parses a single header and returns the symbols
	public function parse_header ($path) {
		$lines = file($path); //FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
		$count = 0;
		$source_trim = " 	\n";
		$symbols = array();
		$scope = array();
		$current_scope = null;
		
		foreach ($lines as $line) {
			$count++;
			
			// Parse the current scope
				
			// Open class scope
			if (eregi($this->REGEX_SCOPE_CLASS["open"], $line, $captures)) {
				
				if (eregi($this->REGEX_SCOPE_CLASS["break"], $line)) continue;
										
				$current_scope = $this->terminate_scope($current_scope, $line, &$scope);
								
				$current_scope = array();
				$current_scope["kind"] = $this->REGEX_SCOPE_CLASS["scope"];
				$current_scope["symbol"] = $captures[1];
				$current_scope["terminate"] = $this->REGEX_SCOPE_CLASS["close"];
				
				// Append the symbol
				if ($current_scope["symbol"]) {
					$this->print_symbol_message("- Class ".$captures[1]." (".$captures[5].")");
					$symbols[$captures[1]]["parent"] = $captures[5];
					$symbols[$captures[1]]["source"] = trim($line, $source_trim);
					$symbols[$captures[1]]["path"] = $path;
					$symbols[$captures[1]]["line"] = $count;
					
					// Get the class type
					$class_type = strtolower($captures[2]);
					switch ($class_type) {
						case 'class':
						case 'object':
							$symbols[$captures[1]]["kind"] = self::SYMBOL_CLASS;
							break;
						
						case 'objcclass':
							$symbols[$captures[1]]["kind"] = self::SYMBOL_CLASS_OBJC;
							break;
						
						case 'objccategory':
							$symbols[$captures[1]]["kind"] = self::SYMBOL_CLASS_CATEGORY;
							break;
							
						case 'objcprotocol':
							$symbols[$captures[1]]["kind"] = self::SYMBOL_CLASS_PROTOCOL;
							break;
							
						case 'interface':
							$symbols[$captures[1]]["kind"] = self::SYMBOL_CLASS_INTERFACE;
							break;
					}
					
				}

				$scope[] = $current_scope;
				$this->print_symbol_message("	* Class ".$current_scope["symbol"]);
				//continue;
			}
			
			// Open interface scope
			if (eregi($this->REGEX_SCOPE_INTERFACE["open"], $line, $captures)) {
				$current_scope = $this->terminate_scope($current_scope, $line, &$scope);

				$current_scope = array();
				$current_scope["kind"] = $this->REGEX_SCOPE_INTERFACE["scope"];
				$current_scope["terminate"] = $this->REGEX_SCOPE_INTERFACE["close"];
				
				$scope[] = $current_scope;
				$this->print_symbol_message("• Interface");
				//continue;
			}
			
			// Open implementation scope
			if (eregi($this->REGEX_SCOPE_IMPLEMENTATION["open"], $line, $captures)) {
				$current_scope = $this->terminate_scope($current_scope, $line, &$scope);
				
				$current_scope = array();
				$current_scope["kind"] = $this->REGEX_SCOPE_IMPLEMENTATION["scope"];
				$current_scope["terminate"] = $this->REGEX_SCOPE_IMPLEMENTATION["close"];
				
				$scope[] = $current_scope;
				$this->print_symbol_message("• Implementation");
				//continue;
			}
				
			// Terminate the current scope
			$current_scope = $this->terminate_scope($current_scope, $line, &$scope);
			
			// Parse by scope
			switch ($current_scope["kind"]) {
				
				// Inteface Scope
				case "interface": {
					
					if (eregi(self::REGEX_TYPE, $line, $captures)) {
						$this->print_symbol_message("- Type ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_TYPE;
						continue;
					}

					if (eregi(self::REGEX_RECORD, $line, $captures)) {
						$this->print_symbol_message("- Record ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_RECORD;
						continue;
					}

					if (eregi(self::REGEX_EXTERNAL_SYMBOL, $line, $captures)) {
						$this->print_symbol_message("- External ".$captures[2]);
						$symbols[$captures[2]]["parent"] = null;
						$symbols[$captures[2]]["source"] = trim($line, $source_trim);
						$symbols[$captures[2]]["path"] = $path;
						$symbols[$captures[2]]["line"] = $count;
						$symbols[$captures[2]]["kind"] = self::SYMBOL_SYMBOL;
						continue;
					}
					
					if (eregi(self::REGEX_FUNCTION, $line, $captures)) {
						//$symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
						$this->print_symbol_message("- Function ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_DECLARED_ROUTINE;
						continue;
					}

					if (eregi(self::REGEX_PROCEDURE, $line, $captures)) {
						//$symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
						$this->print_symbol_message("- Procedure ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_DECLARED_ROUTINE;
						continue;
					}
					
					break;
				}
				
				// Implementation Scope
				case "implementation": {
					
					if (eregi(self::REGEX_IMPLEMENTED_METHOD, $line, $captures)) {
						$this->print_symbol_message("= Method ".$captures[3].".".$captures[4]);
						$symbols[$captures[4]]["parent"] = $captures[3];
						$symbols[$captures[4]]["source"] = trim($line, $source_trim);
						$symbols[$captures[4]]["path"] = $path;
						$symbols[$captures[4]]["line"] = $count;
						$symbols[$captures[4]]["kind"] = self::SYMBOL_IMPLEMENTED_METHOD;
						continue;
					}
					
					if (eregi(self::REGEX_FUNCTION, $line, $captures)) {
						//$symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
						$this->print_symbol_message("= Function ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_IMPLEMENTED_ROUTINE;
						continue;
					}

					if (eregi(self::REGEX_PROCEDURE, $line, $captures)) {
						//$symbols[$captures[1]]["params"] = $this->parse_parameters($captures[2]);
						$this->print_symbol_message("= Procedure ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_IMPLEMENTED_ROUTINE;
						continue;
					}
					
					if (eregi(self::REGEX_TYPE, $line, $captures)) {
						$this->print_symbol_message("= Type ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_TYPE;
						continue;
					}

					if (eregi(self::REGEX_RECORD, $line, $captures)) {
						$this->print_symbol_message("= Record ".$captures[1]);
						$symbols[$captures[1]]["parent"] = null;
						$symbols[$captures[1]]["source"] = trim($line, $source_trim);
						$symbols[$captures[1]]["path"] = $path;
						$symbols[$captures[1]]["line"] = $count;
						$symbols[$captures[1]]["kind"] = self::SYMBOL_RECORD;
						continue;
					}
					
					break;
				}
				
				// Class Scope
				case "class": {
					
					if (eregi(self::REGEX_DECLARED_METHOD, $line, $captures)) {
						$this->print_symbol_message("		- Method ".$captures[3]);
						$symbols[$captures[3]]["parent"] = $current_scope["symbol"];
						$symbols[$captures[3]]["source"] = trim($line, $source_trim);
						$symbols[$captures[3]]["path"] = $path;
						$symbols[$captures[3]]["line"] = $count;
						$symbols[$captures[3]]["kind"] = self::SYMBOL_DECLARED_METHOD;
						continue;
					}
					
					break;
				}
			}
		}
		
		// Clean up the symbols array
		ksort($symbols);
		//$this->print_message("Parsed ".basename($path)." ($count lines)");
		
		return $symbols;
	}
	
	// Parses all paths for modified files
	public function parse ($ignore_mod_times) {
		
		// Start metrics
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$starttime = $mtime; 

		$symbol_count = 0;
		$index_count = 0;
		
		// Iterate all paths
		foreach ($this->paths as $path) {
			
			// Path doesn't exist, ignore
			if (!file_exists($path)) continue;
			
			$this->print_message("Indexing ".basename($path)."...");
			
			$handle = opendir($path);
			
			// Iterate all files in directory
			while (($file = readdir($handle)) !== false) {
				if ((eregi($this->pascal_source_expression, $file)) && (!in_array($file, $this->ignore_files))) {
					//print("$file\n");

					$symbol_file = substr($file, 0, strpos($file, ".")).".symbols";
					$modified = false;

					// Compare modification dates with .symbols file
					if (file_exists("$this->directory/$symbol_file")) {
						if ((filemtime("$this->directory/$symbol_file") != filemtime("$path/$file")) || ($ignore_mod_times)) {
							$modified = true;
						}
					} else {
						$modified = true;
					}

					// If the source file was modified parse
					if (($modified) && (!array_key_exists($file, $this->symbols))) {
						$this->print_message("	Parsing $file...");
						
						$index_count++;
						$header_path = "$path/$file";
						
						if ($symbols = $this->parse_header($header_path)) {
							
							$symbol_count += count($symbols);
							
							// Print the symbol file
							$this->print_symbol_file($symbols, $header_path, "$this->directory/$symbol_file", filemtime("$path/$file"));

							// Append master table
							$this->symbols[$file] = $symbols;
						} else {
							$this->symbols[$file] = null;
							$this->print_symbol_file(null, $header_path, "$this->directory/$symbol_file", filemtime("$path/$file"));
						}
					}

				}
			}

			closedir($handle);
		}
		
		// Display metrics
		if ($this->print_messages) {
			$mtime = microtime(); 
			$mtime = explode(" ",$mtime); 
			$mtime = $mtime[1] + $mtime[0]; 
			$endtime = $mtime; 
			$totaltime = round($endtime - $starttime); 
			
			//print_r($this->symbols);
			
			$this->print_message("Indexed $index_count files for $symbol_count symbols in $totaltime seconds.");
		}
		
		// Return true if the parser modified files
		if ($index_count > 0) return true;
	}
		
	// Generates auto complete table with symbols
	// ??? DEPRECATED, WILL BE REMOVED
	public function generate_auto_complete_table ($binary, $destination) {
		$symbols = array();
		
		$this->print_message("Generating auto complete table...");
		
		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				$path = "$this->directory/$file";
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file))) {
					
					// Extract lines from symbol file
					$lines = file($path);
					foreach ($lines as $line) {
						$info = explode("|", $line);
						$symbols[] = $info[self::SYMBOL_FIELD_NAME];
					}
				}
			}
			closedir($handle);
			
			// Save property list to disk
			$symbols = array_unique($symbols);
			
			$path = $destination."/".AUTO_COMPLETE_TABLE;
			if ($handle = @fopen($path, "w+")) {
				fwrite($handle, "(\n");
				
				foreach ($symbols as $value) {
					fwrite($handle, "	{display = \"$value\"; insert = \"\";},\n");
				}

				fwrite($handle, ")\n");
				fclose($handle);
			}
			
			// Convert to binary
			if ($binary) {
				exec("/usr/bin/plutil -convert binary1 \"$path\" -o \"$path\"");
			}
			
			//print_r($symbols);
		} else {
			$this->print_message("Failed to open directory at $this->directory!");
			die;
		}
	}
	
	// Returns an array of all symbol file paths in the project
	protected function find_symbol_tables () {
		$paths = array();
		
		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file))) {
					$paths[] = "$this->directory/$file";
				}
			}
			closedir($handle);
		}
		
		return $paths;
	}
	
	// Loads a symbol table into an array of fields
	protected function load_symbol_table ($path) {
		$symbols = array();
		
		$lines = file($path);
		
		// The first line is the path to the Pascal source file
		$source_path = $lines[0];
		
		foreach ($lines as $line) {						
			$info = explode("|", $line);
			
			$symbol = array();
			$symbol["path"] = $source_path;
			$symbol["name"] = $info[self::SYMBOL_FIELD_NAME];
			$symbol["parent"] = $info[self::SYMBOL_FIELD_PARENT];
			$symbol["line"] = $info[self::SYMBOL_FIELD_LINE];
			$symbol["kind"] = $info[self::SYMBOL_FIELD_KIND];

			$symbols[] = $symbol;
		}
		
		return $symbols;
	}
			
	// Queries .symbol files for a symbol
	public function query_2 ($query) {
		$symbols = array();
		$index_valid = false;
				
		// Start metrics
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$starttime = $mtime; 

		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				$path = "$this->directory/$file";
				$header_path = null;
				
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file))) {
					
					// Extract lines from symbol file
					$lines = file($path);
					foreach ($lines as $line) {
						
						// The header path is the first line
						if (!$header_path) {
							$header_path = trim($line, "\n");
							continue;
						}
						
						// Explode the line into sections
						$info = explode("|", $line);
								
						// Toggle index			
						// ??? is this faster then eregi?			
						//if ($info[self::SYMBOL_FIELD_NAME][0] == $query[0]) $index_valid = true;
						//if (($index_valid) && ($info[self::SYMBOL_FIELD_NAME][0] != $query[0])) $index_valid = false;
						
						// ??? make an option?
						$index_valid = true;
						
						// Return the symbol with matching name
						if (($index_valid) && ($info)) {
														
							// Apply query options
							// ??? broken until index is case insensitive!
							if ($query) {
								if ($this->case_insensitive) {
									$result = eregi($query, $info[0]);
								} else {
									$result = ereg($query, $info[0]);
								}
							} else {
								$result = true;
							}
							
							if ($result) {
								$symbol = array();
								$symbol["path"] = $header_path;
								$symbol["name"] = $info[self::SYMBOL_FIELD_NAME];
								$symbol["parent"] = $info[self::SYMBOL_FIELD_PARENT];
								$symbol["line"] = (int)$info[self::SYMBOL_FIELD_LINE];
								$symbol["kind"] = (int)$info[self::SYMBOL_FIELD_KIND];

								$symbols[] = $symbol;
							}
						}
						
					}
				}
			}
			closedir($handle);
		}
		
		$mtime = microtime(); 
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		$endtime = $mtime; 
		$totaltime = $endtime - $starttime; 
		
		//$this->print_message("Found ".count($symbols)." symbols in $totaltime seconds.");
		
		ksort($symbols);
		
		if (count($symbols) == 0) $symbols = null;

		return $symbols;
	}
			
	// Prints the master symbol listing for debugging
	public function show () {
		print_r($this->symbols);
	}
	
	public function __construct ($directory, $paths) {
		$this->directory = $directory;
		$this->paths = $paths;
				
		// Create the symbol directory if needed
		if ($this->directory) @mkdir($this->directory, 0777);
	}
}

/*
if ($symbols = $parser->parse_header("/Users/ryanjoseph/Desktop/Projects/Common/PascalCarbonCommon.pas")) {
	print("Parsed: ");
	print_r($symbols);
	$parser->print_symbol_file($symbols, "/Foo", "/Users/ryanjoseph/Desktop/__TEST__.symbols", null);
}
*/

/*
$paths = array(	
								//"/Users/ryanjoseph/Desktop/Projects/FPC_Projects/WindowManager/Sources",
								"/Developer/Pascal/GPCInterfaces/VersionH/FPCPInterfaces",
								"/Developer/ObjectivePascal/2.0/appkit",
								"/Developer/ObjectivePascal/2.0/foundation",
								"/Developer/ObjectivePascal/iPhone/UIKit",
								"/Developer/ObjectivePascal/2.0/webkit"
								);

$parser = new SymbolParser("/Users/ryanjoseph/Desktop/symbols", $paths);
$parser->print_messages = true;
$parser->print_symbol_messages = false;
//if ($parser->parse(false)) {
	//$parser->generate_auto_complete_table(true);
	//$parser->generate_master_table();
//}

//if ($parser->parse(false)) $parser->generate_auto_complete_table(true, "/Users/ryanjoseph/Desktop");

if ($symbol = $parser->query_2("CF")) ;//print_r($symbol);
*/

?>