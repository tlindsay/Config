<?php

/*
	The PPU parser generates symbols files based on PPU files from a build directory
	
	*** DEPRECATED IN FAVOR SYMBOL_PARSER.PHP ***
	
*/

ini_set("memory_limit", "64M");
define("AUTO_COMPLETE_TABLE", "autocomplete.symbols"); // Name of the auto-complete table file

require_once("plist.php");

class PPUParser {
	
	public $print_html_messages = false;		// Script messages will printed as HTML to STDOUT
	
	private $symbols = array();							// Master symbol table for all parsed .ppu files
	private $directory;											// Build birectory of .ppu files
	private $modified_files = array();			// Array of modified ppu files
	private $ignore_files = array();				// Array of ppu files names to ignore
	
	// Prints a .symbols file for the ppu.
	private function print_symbol_file ($symbols, $destination, $mod_time) {
		
		if ($handle = @fopen($destination, "w+")) {
			foreach ($symbols as $key => $value) {
				foreach ($value as $name => $info) {
					fwrite($handle, $name."|".$info["path"]."|".$info["line"]."|".$info["column"]."\n");
				}
			}
			
			fclose($handle);

			// Synch the modification date with the ppu file
			touch($destination, $mod_time, $time);
		} else {
			if ($this->print_html_messages) print("There was an error printing symbols to ".basebame($destination)."<br/>\n");
		}
	}
	
	// Sets the array of PPU files to ignore (from the target loader)
	public function set_ignore_files ($array) {
		if ($array) $this->ignore_files = $array;
	}
	
	// Parses a PPU directory for modified files
	public function parse ($ignore_mod_times) {
		$handle = opendir($this->directory);

		while (($file = readdir($handle)) !== false) {
			if ((eregi("[a-zA-Z0-9]+\.ppu", $file)) && (!in_array($file, $this->ignore_files))) {
				//print("$file\n");
				$symbol_file = substr($file, 0, strpos($file, ".")).".symbols";
				$modified = false;

				// Compare modification dates with .symbols file
				if (file_exists("$this->directory/$symbol_file")) {
					if ((filemtime("$this->directory/$symbol_file") != filemtime("$this->directory/$file")) || ($ignore_mod_times)) {
						$modified = true;
					}
				} else {
					$modified = true;
				}

				if ($modified) {
					if ($this->print_html_messages) print("Indexing $file... <br/>\n");
				}
				
				// Parse the file using ppudump
				if (($modified) && ($stream = popen("/usr/local/bin/ppudump \"$this->directory/$file\"", "r"))) {
					$symbols = array();
					$files = array();
					
					$this->modified_files[] = $symbol_file;
					
				    while (!feof($stream)) {
				        $buffer = fgets($stream, 32 * 1024);
						$buffer = trim($buffer, "\n");
						
						// Errors
						if (eregi("^!! Error in PPU", $buffer)) {
							if ($this->print_html_messages) print("Error in $file symbols won't be available.<br>\n");
							break;
						}
						
						// File Locations
						if (($got_symbol) && (eregi("^[[:space:]]*File Pos[[:space:]]+:[[:space:]]+([0-9]+)[[:space:]]+\(([0-9]+),([0-9]+)\)", $buffer, $captures))) {
							$id = $captures[1];
							$line = $captures[2];
							$column = $captures[3];
							
							//print($files[$id]." = $line ($id)\n");
							$symbol["path"] = $files[$id];
							$symbol["line"] = $line;
							$symbol["column"] = $column;

							$got_symbol = false;
							continue;
						}
						
						// Source file
						if (eregi("^Source file[[:space:]]+([0-9]+)[[:space:]]+:[[:space:]]+([a-zA-Z_]+\.([a-zA-Z]+))[[:space:]]+(.*)", $buffer, $captures)) {
							$files[$captures[1]] = $captures[2];
						}
												
						// Constants
						if (eregi("^[[:space:]]*Constant symbol ([a-zA-Z0-9_]+)", $buffer, $captures)) {
							$symbols["constants"][$captures[1]] = array(); 
							$symbol = &$symbols["constants"][$captures[1]];
							$got_symbol = true;
							continue;
						}

						// Procedures
						if (eregi("^[[:space:]]*Procedure symbol ([a-zA-Z0-9_]+)", $buffer, $captures)) {
							$symbols["functions"][$captures[1]] = array();
							$symbol = &$symbols["functions"][$captures[1]];
							$got_symbol = true;
							continue;
						}

						// Types
						if (eregi("^[[:space:]]*Type symbol ([a-zA-Z0-9_]+)", $buffer, $captures)) {
							$symbols["types"][$captures[1]] = array();
							$symbol = &$symbols["types"][$captures[1]];
							$got_symbol = true;
							continue;
						}
						
						// Globals
						if (eregi("^[[:space:]]*Global Variable symbol ([a-zA-Z0-9_]+)", $buffer, $captures)) {
							$symbols["globals"][$captures[1]] = array(); 
							$symbol = &$symbols["globals"][$captures[1]];
							$got_symbol = true;
							continue;
						}

						// Classes
						// ??? classes also appear in the type section
						//    Name of Class : TXMLRPC
				    }
					
					//print_r($files);
					//print_r($symbols);
					//die;
					
					// Print the symbols file to disk
					$this->print_symbol_file($symbols, "$this->directory/$symbol_file", filemtime("$this->directory/$file"));
					
					// Append to master listing
					$this->symbols[$file] = $symbols;
					
					pclose($stream);
				}
			}
		}

		closedir($handle);
	}
	
	// Generates auto complete table with symbols
	public function generate_auto_complete_table ($binary, $destination) {
		$symbols = array();
		
		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				$path = "$this->directory/$file";
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file)) && ($file != AUTO_COMPLETE_TABLE) && (count($this->modified_files) > 0)) {
					//if ($this->print_html_messages) print("Generating table for $file...<br/>\n");
					
					// Extract lines from symbol file
					$lines = file($path);
					foreach ($lines as $line) {
						$info = explode("|", $line);
						$symbols[] = $info[0];
					}
				}
			}
			closedir($handle);
			
			// Save property list to disk
			$symbols = array_unique($symbols);
			
			$path = "$destination/".AUTO_COMPLETE_TABLE;
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
			if ($this->print_html_messages) print("Failed to open ppu directory at $this->directory!<br />\n");
			die;
		}

		
	}
	
	// Finds all matching symbols in the project
	public function find_symbol_all ($name) {

		$symbols = array();

		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				$path = "$this->directory/$file";
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file)) && ($file != AUTO_COMPLETE_TABLE)) {
					
					// Extract lines from symbol file
					$lines = file($path);
					foreach ($lines as $line) {
						$info = explode("|", $line);
						
						// Return the symbol with matching name
						if ($info[0] == $name) {
							$symbol = array();
							$symbol["name"] = $info[0];
							$symbol["path"] = $info[1];
							$symbol["line"] = $info[2];
							$symbol["column"] = $info[3];
							
							$symbols[] = $symbol;
						}
					}
				}
			}
			closedir($handle);
		}
		
		return $symbols;
	}
	
	// Finds a symbol in the project
	public function find_symbol ($name) {

		if ($handle = opendir($this->directory)) {
			while (($file = readdir($handle)) !== false) {
				$path = "$this->directory/$file";
				if ((eregi("[a-zA-Z0-9]+\.symbols", $file)) && ($file != AUTO_COMPLETE_TABLE)) {
					
					// Extract lines from symbol file
					$lines = file($path);
					foreach ($lines as $line) {
						$info = explode("|", $line);
						
						// Return the symbol with matching name
						if ($info[0] == $name) {
							$symbol["name"] = $info[0];
							$symbol["path"] = $info[1];
							$symbol["line"] = $info[2];
							$symbol["column"] = $info[3];
							
							return $symbol;
						}
					}
				}
			}
			closedir($handle);
		}
	}
	
	// Prints the master symbol listing for debugging
	public function show () {
		print_r($this->symbols);
	}
	
	public function __construct ($directory) {
		$this->directory = $directory;
	}
}


//$parser = new PPUParser("/Users/ryanjoseph/Desktop/HelloWorldPascaliPhone/build.iphone_simulator");
//$parser->parse(false);
//$parser->generate_auto_complete_table(true, "/Users/ryanjoseph/Desktop/HelloWorldPascaliPhone");
//if ($symbol = $parser->find_symbol("descriptionInStringsFileFormat")) print_r($symbol);

?>