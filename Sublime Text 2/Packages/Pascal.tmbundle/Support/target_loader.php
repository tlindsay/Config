<?
/*
	The target loader will read an XML target file from the project directory and print an FPC
	command from the defined settings.
*/

require_once("preferences.php");
require_once("common.php");

// Platform Constants
define("PLATFORM_PPC", "ppc");
define("PLATFORM_INTEL", "i386");
define("PLATFORM_IPHONE_SIMULATOR", "iphone_simulator");
define("PLATFORM_IPHONE_DEVICE", "iphone_device");
define("PLATFORM_UNIVERSAL", "universal");

// SDK Constants
define("SDK_IPHONE_SIMULATOR", "iphone_simulator");
define("SDK_IPHONE_DEVICE", "iphone_device");
define("SDK_UNIVERSAL", "universal");

class TargetLoader {
	
	/**
	 * Public
	 */
	public $name;									// Name of the active target                                    
	public $path;									// Path of the active target XML file                           
	public $xml;									// SimpleXML object of the active target                        
	public $project;							// Path to the project which owns the target                    
	                                                                                          
	public $compiler;							// Path of the FPC compiler                                     
	public $program;							// Path of the main program file                                
	public $output;								// Path of the output directory                                 
	public $bundle;								// Path of the application bundle (if applicable)               
	public $binary;								// Path of the directory the application binary will be moved to
	public $resources;						// Path of the resources directory which contains NIBs to be copied into the application bundle.
	public $platform;							// Active platform
	
	public $advanced_index_symbols;					// Enables symbol indexing during compiles
	public $advanced_resolve_paths_recursively;		// When resolving paths search recursively into sub-directories
	public $advanced_debugging;						// Debugging command line options
	public $advanced_show_fpc_command;				// Shows the FPC command in the console when building
	
	public $paths = array(); 				// Array of target defined paths
	public $options = array();			// Array of target defined compiler options 
	public $frameworks = array();		// Array of target defined framework paths
	public $sdks = array();				// Array (kind => path) of target defined SDKS (applied via the <platform> setting)
 	public $symbols = array();		// Paths for the symbol parser to reference

	public $fields = array();				// Array of raw fields as they appear in the XML file
	public $executable;							// Name of the compiled exectuable (extracted from <program>)
	public $executable_directory;		// Directory where the compiler will create the executable
	public $bundle_binary;					// Name of the bundle binary (bundle name without the .app extension)
	public $simulator_sdk_version;	// Version of the iPhone simulator SDK (extracted from <sdks/iphone_simulator>)
	
	public $ignore_ppu = array();		// Array of ppu files to ignore (hidden preference)
		
	/**
	 * PRIVATE
	 */
	private $debug_build_folder = "debug";		// Name of the debug build folder (inside the standard build folder)
	private $macros = array();		// Target path macros:
																// 		{project} = Path to the project directory
																//		{bundle} = Path to the application bundle (specified in <bundle>)
																//		{bundle-name} = Name of {bundle} without extension
																// 		{compiler} = FPC compiler name based upon <platform> settings
																//		{platform} = Name of the target compiler platform (defined in <platform>)
	
	const UNIVERSAL_MACRO = "__universal__";	// Macro used to specify the compiler (ppcppc or i386) depending on universal build phase
	
	
	/**
	 * GUI utilities
	 */
	
	// Returns a checkbox <input> value for target field of $key
	public function get_checkbox_value ($key) {
		if ($this->fields[$key]) {
			return "checked";
		} else {
			return null;
		}
	}
	
	// Sets an XML node with the value from a checkbox <input> element
	public function set_checkbox_value ($key, $value) {
		if ($value == "on") {
			$this->set_value($key, 1);
		} else {
			$this->set_value($key, 0);
		}
	}
	
	/**
	 * Saving Target XML
	 */
	
	// Sets the targets path array
	public function set_paths ($array) {
		$this->set_array_value("paths", "path", $array);
	}

	// Sets the targets framework array
	public function set_frameworks ($array) {
		$this->set_array_value("frameworks", "framework", $array);
	}
	
	// Sets the targets option array
	public function set_options ($array) {
		$this->set_array_value("options", "option", $array);
	}
	
	// Sets the targets symbol paths array
	public function set_symbols ($array) {
		$this->set_array_value("symbols", "symbol", $array);
	}

	// Sets the targets sdk
	public function set_sdk ($sdk, $value) {
		$this->xml->sdks->{$sdk} = $value;
	}

	// Sets the targets platform
	public function set_platform ($platform, $value) {
		foreach ((array)$this->xml->platform as $key => $switch) {
			if ($key == $platform) {
				if ($value) {
					$this->xml->platform->{$key} = "on";
				} else {
					$this->xml->platform->{$key} = "off";
				}
			} else {
				$this->xml->platform->{$key} = "off";
			}
			
		}
	}
	
	/**
	 * Setting Accessors
	 */
	
	// Sets the value of an array node with an array
	public function set_array_value ($node, $child, $array) {
		
		// Accept single-element parameters by making them arrays internally
		if (gettype($array) != "array") $array = array($array);
		
		$this->xml->{$node} = null;
		
		foreach ($array as $value) {
			$this->xml->{$node}->addChild($child, $value);
		}
	}

	// Sets a target value
	public function get_string_value ($key) {
		return $this->resolve_macro((string)$this->xml->{$key});
	}
	
	// Sets a target value
	public function set_value ($key, $value) {
		$this->xml->{$key} = $value;
	}
	
	// Sets a multi-line string value from array
	public function set_value_with_array ($key, $value) {
		
		if (is_array($value)) {
			$value = implode("\n", $value);
		}
		
		$this->xml->{$key} = $value;
	}
		
	
	// Save the target XML after making changes
	public function save () {
		if (file_put_contents($this->path, $this->xml->asXML())) {
			return true;
		} else {
			return false;
		}
	}
								
	/**
	 * Utilities
	 */
	
	// Recursively appends all child directories to an array
	private function child_directories ($directory, &$directories) {
		$directories[] = $directory;
		
		if ($handle = @opendir($directory)) {
			while (($file = readdir($handle)) !== false) {
				if (($file != '.') && ($file != '..') && ($file[0] != '.')) {
					$path = "$directory/$file";
					if (is_dir($path)) $this->child_directories($path, $directories);
				}
			}
			closedir($handle);
		}
	}
	
	// Resolves file name against the target paths
	public function resolve_path ($file) {
		
		// Get the target defined search paths
		$paths = $this->paths;
		//$paths[] = $this->project;

		// Append all child directories of the search paths
		if ($this->advanced_resolve_paths_recursively) {
			foreach ($paths as $path) {
				$this->child_directories($path, $paths);
			}
		}
		
		// Attempt to resolve the file name against a search path
		foreach ($paths as $path) {
			$resolved_path = $this->resolve_macro("$path/$file");
			if (file_exists($resolved_path)) {
				return $resolved_path;
			}
		}
	}
	
	// Resolve target macros
	private function resolve_macro ($path) {
		
		// Replace ~ with user path
		$path = str_ireplace("~", user_directory(), $path);
		
		// Replace all defined macros
		foreach ($this->macros as $key => $value) {
			$path = str_ireplace($key, $value, $path);
		}
		
		return $path;
	}
	
	// Utility to copy an indexed array from the XML array (without recursion)
	private function copy_array_from_xml ($xml_array) {
		
		foreach ($xml_array as $key => $value) {
			if (gettype($value) == "array") {
				return $value;
			} else {
				return array($value);
			}
			
		}
	}
	
	/**
	 * Accessors
	 */
	
	// Returns the path to the symbols directory where parsed symbols are stored
	public function get_symbols_directory () {
		return $this->output."/symbols";
	}
	
	// Returns path of bundle binary (i.e. /path/to/my.app/Contents/MacOS/my)
	public function get_bundle_binary_path () {
		return $this->bundle.$this->fields["binary"]."/".$this->bundle_binary;
	}
	
	// Returns an array of resolved symbol parser paths
	public function get_resolved_symbols () {
		$paths = array();
		if ($this->symbols) {
			foreach ($this->symbols as $value) $paths[] = $this->resolve_macro($value);
		}
		
		return $paths;
	}
	
	// Returns an array of resolved paths
	public function get_resolved_paths () {
		$paths = array();
		if ($this->paths) {
			foreach ($this->paths as $value) $paths[] = $this->resolve_macro($value);
		}
		
		return $paths;
	}
		
	// Returns a <select> element "selected" value if a platform is enabled
	public function is_platform_enabled ($platform) {
		if ($this->platform == $platform) return "selected";
	}
	
	// Returns the targets compiler command line as a formatted string
	public function get_full_command ($build_phase, $launch_mode) {

		$options = $this->get_command_options($launch_mode);
		
		$command .= "$this->compiler ";
		
		// Replace univeral platform macro depending on build phase
		if ($build_phase == 1) {
			$command = str_replace(self::UNIVERSAL_MACRO, "ppcppc", $command);
		} else {
			$command = str_replace(self::UNIVERSAL_MACRO, "ppc386", $command);
		}
		
		foreach ($options as $value) {
			$command .= "$value ";
		}
	
		return $command;
	}
	
	// Returns the targets command line options as an array
	public function get_command_options ($launch_mode) {
		
		$options = array();
		
		// Program
		$options[] = "\"$this->program\"";
		
		// Options
		if (count($this->options) > 0) {
			foreach ($this->options as $value) {
				if ($value != "") $options[] = $value;
			}
		}
		
		// Frameworks
		if (count($this->frameworks) > 0) {
			foreach ($this->frameworks as $value) {
				$path = $this->resolve_macro($value);
				if ($path != "") $options[] = "-Ff\"$path\"";
			}
		}
		
		// Paths
		if (count($this->paths) > 0) {
			foreach ($this->paths as $value) {
				$path = $this->resolve_macro($value);
				if ($path != "") $options[] = "-Fu\"$path\"";
			}
		}
		
		// Output directory
		if ($launch_mode == 2 /*LAUNCH_MODE_DEBUG*/) {
			$debug_output = "$this->output/$this->debug_build_folder";
			$options[] = "-FU\"$debug_output\"";
			
			// Create the debug output directory if absent
			if (!file_exists($debug_output)) mkdir($debug_output, 0777);
			
		} else {
			$options[] = "-FU\"$this->output\"";
		}
		
		return $options;
	}
	
	/**
	 * Methods
	 */
	
	// Sets default values in the target which were not defined in XML
	private function populate_default_values (&$xml) {
		
		// Added in 1.2
		if (!isset($xml->xcode_active_configuration)) $xml->xcode_active_configuration = "Debug";
		if (!isset($xml->xcode_project)) $xml->xcode_project = "{project}/Xcode/{bundle}";
		if (!isset($xml->debugger_breakpoints)) {
			$xml->debugger_breakpoints = "";
		}
		
		// Added in 1.1
		if (!isset($xml->advanced_index_symbols)) $xml->advanced_index_symbols = 1;
		if (!isset($xml->advanced_resolve_paths_recursively)) $xml->advanced_resolve_paths_recursively = 0;
		if (!isset($xml->advanced_debugging)) $xml->advanced_debugging = "-gw -Xg";
		if (!isset($xml->advanced_show_fpc_command)) $xml->advanced_show_fpc_command = 1;
		if (!isset($xml->symbols)) {
			$xml->symbols = "{project}/Sources\n";
			$xml->symbols .= "/Developer/ObjectivePascal/fpc/packages/univint/src\n";
			$xml->symbols .= "/Developer/ObjectivePascal/fpc/packages/cocoaint/src/appkit\n";
			$xml->symbols .= "/Developer/ObjectivePascal/fpc/packages/cocoaint/src/foundation\n";
		}

	}
	
	// Loads the target
	private function load_target ($xml) {
		
		// Set the active target XML
		$this->xml = $xml;
		
		// Setup any default values that are missing from the XML
		// like preferences that were added in later versions
		$this->populate_default_values($xml);
		
		// Active platform 
		foreach ((array)$xml->platform as $key => $value) {
			if ($value == "on") {
				$this->platform = $key;
				
				$this->macros["{platform}"] = $key;
				
				$this->fields["platform"] = $key;
				
				// Set the {compiler} macro
				switch ($key) {
					case PLATFORM_PPC: {
						$this->macros["{compiler}"] = "ppcppc";
						break;
					}
					case PLATFORM_INTEL: {
						$this->macros["{compiler}"] = "ppc386";
						break;
					}
					case PLATFORM_IPHONE_SIMULATOR: {
						$this->macros["{compiler}"] = "ppc386";
						break;
					}
					case PLATFORM_IPHONE_DEVICE: {
						$this->macros["{compiler}"] = "ppcrossarm";
						break;
					}
					case PLATFORM_UNIVERSAL: {
						$this->macros["{compiler}"] = self::UNIVERSAL_MACRO;
						break;
					}
				}
				
				break;
			}
		}
		
		// Target SDK's
		$this->sdks = (array)$xml->sdks;
				
		// Extract the iPhone simulator SDK version
		if (eregi("([0-9.]+)\.sdk$", $this->sdks[PLATFORM_IPHONE_SIMULATOR], $captures)) $this->simulator_sdk_version = $captures[1];
		
		// Bundle settings
		$this->bundle = $this->resolve_macro($xml->bundle);
		$this->macros["{bundle}"] = $this->bundle;
		$this->bundle_binary = basename($this->bundle, ".app");
		$this->macros["{bundle-name}"] = $this->bundle_binary;

		// String settings
		$this->compiler = $this->resolve_macro($xml->compiler);
		$this->output = $this->resolve_macro($xml->output);
		$this->resources = $this->resolve_macro($xml->resources);
		
		// Program settings
		$this->program = $this->resolve_macro($xml->program);
		
		// Get exectubable name from program
		if (eregi("([a-zA-Z0-9_]+)\.(p|pas|pp)+$", $this->program, $captures)) {
			$this->executable = $captures[1];
		}
		
		// Get the executable directory
		$this->executable_directory = dirname($this->program);
		
		// Get binary path
		$this->binary = "$this->bundle/".ltrim($this->resolve_macro($xml->binary), "/");
		$this->binary = rtrim($this->binary, "/");
		
		// Advanced settings
		$this->advanced_index_symbols = (bool)(int)$xml->advanced_index_symbols;
		$this->advanced_show_fpc_command = (bool)(int)$xml->advanced_show_fpc_command;
		$this->advanced_resolve_paths_recursively = (bool)(int)$xml->advanced_resolve_paths_recursively;
		$this->advanced_debugging = $xml->advanced_debugging;
				
		// Array settings
		$this->paths = $this->copy_array_from_xml((array)$xml->paths);
		$this->options = $this->copy_array_from_xml((array)$xml->options);
		$this->frameworks = $this->copy_array_from_xml((array)$xml->frameworks);
		$this->symbols = $this->copy_array_from_xml((array)$xml->symbols);

		// Hidden settings
		$this->ignore_ppu = $this->copy_array_from_xml((array)$xml->ignore_ppu);

		// Copy raw fields
		$this->fields["compiler"] = $xml->compiler;
		$this->fields["program"] = $xml->program;
		$this->fields["output"] = $xml->output;
		$this->fields["bundle"] = $xml->bundle;
		$this->fields["binary"] = $xml->binary;
		$this->fields["resources"] = $xml->resources;
		
		$this->fields["advanced_index_symbols"] = (bool)(int)$xml->advanced_index_symbols;
		$this->fields["advanced_show_fpc_command"] = (bool)(int)$xml->advanced_show_fpc_command;
		
		$this->fields["advanced_resolve_paths_recursively"] = (bool)(int)$xml->advanced_resolve_paths_recursively;
		$this->fields["advanced_debugging"] = $xml->advanced_debugging;

		$this->fields["xcode_active_configuration"] = $xml->xcode_active_configuration;
		$this->fields["xcode_project"] = $xml->xcode_project;
		$this->fields["debugger_breakpoints"] = $xml->debugger_breakpoints;

		$this->fields["sdk_universal"] = $this->sdks[PLATFORM_UNIVERSAL];
		$this->fields["sdk_iphone_simulator"] = $this->sdks[PLATFORM_IPHONE_SIMULATOR];
		$this->fields["sdk_iphone_device"] = $this->sdks[PLATFORM_IPHONE_DEVICE];
		
		// Convert multi-line fields to arrays
		if ($this->paths) $this->fields["paths"] = implode("\n", $this->paths);
		if ($this->frameworks) $this->fields["frameworks"] = implode("\n", $this->frameworks);
		if ($this->options) $this->fields["options"] = implode("\n", $this->options);
		if ($this->symbols) $this->fields["symbols"] = implode("\n", $this->symbols);
		
	}
	
	// Cleans the target
	public function clean () {
		
		// Delete the auto-complete file
		// ??? DEPRECATED - REMOVE
		//$path = $this->project."/".AUTO_COMPLETE_TABLE;
		//if (file_exists($path)) unlink($path);
		
		// Delete the build directory
		delete_directory($this->output);
		
		return true;
	}
	
	// Verifies the target has all required resources available or dies
	public function verify () {
		
		$error_prefix = "Fatal:";
		
		if (!file_exists($this->program)) die("$error_prefix The main program file can not be found at $this->program.\n");
		if (!file_exists($this->bundle)) die("$error_prefix The bundle can not be found at $this->bundle.\n");
				
		// Verify SDKs based on platform
		switch ($this->platform) {
			case PLATFORM_IPHONE_SIMULATOR:
				if (!file_exists($this->sdks[PLATFORM_IPHONE_SIMULATOR])) die("$error_prefix The iOS simulator SDK can't be found at \"".$this->sdks[PLATFORM_IPHONE_SIMULATOR]."\"");
				break;
			
			case PLATFORM_IPHONE_DEVICE:
				if (!file_exists($this->sdks[PLATFORM_IPHONE_DEVICE])) die("$error_prefix The iOS device SDK can't be found at \"".$this->sdks[PLATFORM_IPHONE_DEVICE]."\"");
				break;
				
			case PLATFORM_UNIVERSAL:
				if (!file_exists($this->sdks[PLATFORM_UNIVERSAL])) die("$error_prefix The universal SDK can't be found at \"".$this->sdks[PLATFORM_UNIVERSAL]."\"");
				break;
		}
		
		
		// Verify target paths
		$paths = $this->get_resolved_paths();
		foreach ($paths as $path) {
			if (!file_exists($path)) die("$error_prefix The target path \"$path\" does not exists.\n");
		}
		
		// Check for compiler version existing
		if (eregi(self::UNIVERSAL_MACRO, $this->compiler)) {
			
			// PPC
			$compiler_ppc = str_replace(self::UNIVERSAL_MACRO, "ppcppc", $this->compiler);
			if (!file_exists($compiler_ppc)) die("$error_prefix The PPC compiler for universal binaries can not be found at ".$compiler_ppc."\n");
			
			// i386
			$compiler_intel = str_replace(self::UNIVERSAL_MACRO, "i386", $this->compiler);
			if (!file_exists($compiler_ppc)) die("$error_prefix The PPC compiler for universal binaries can not be found at ".$compiler_intel."\n");
			
		} else {
			if (!file_exists($this->compiler)) die("$error_prefix The compiler can not be found at ".$this->compiler."\n");
		}
		
		
		return true;
	}
	
	function __construct ($project_path, $target_name) {
		$path = "$project_path/Targets/$target_name.xml";
		
		$this->name = $target_name;
		$this->path = $path;
		$this->project = $project_path;
		
		// Failed to locate XML target file, bail!
		if (!file_exists($path)) {
			print("Warning: The target could not be located at $path.\n");
			die(-1);
		}
		
		$xml = new SimpleXMLElement(file_get_contents($path));
		
		// Assign default target path macros
		$this->macros["{project}"] = $project_path;
		
		// Load the target
		$this->load_target($xml);
	}
	
}
/*
$input["project"] = "/Users/ryanjoseph/Desktop/HelloWorldPascaliPhone";
$input["target"] = "deployment";
	
$loader = new TargetLoader($input["project"], $input["target"]);

$loader->resolve_path("NSArray.inc");
*/

?>