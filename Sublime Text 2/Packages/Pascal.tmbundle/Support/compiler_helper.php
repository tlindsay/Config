<?

/*
	The compiler is used for all compiling operations
*/
require_once("preferences.php");
require_once("cocoa_dialog.php");
require_once("symbol_parser.php");
require_once("breakpoints.php");
require_once("target_loader.php");
require_once("common.php");

$input = script_input();

class CompilerHelper {
	
	// Status Codes
	const STATUS_TARGET = 1;
	const STATUS_COMPILING = 2;
	const STATUS_ERROR = 3;
	const STATUS_ERROR_MAIN = 4;
	const STATUS_LINKING = 5;
		
	// Launch Modes
	const LAUNCH_MODE_CONSOLE = 0;		// Application is launched in the TextMate console
	const LAUNCH_MODE_TERMINAL = 1;		// Application is launched in the Terminal
	const LAUNCH_MODE_DEBUG = 2;			// Application is launched in the Terminal under GDB
	const LAUNCH_MODE_BUILD = 3;			// Application is built only - no launching

	// Console Macros
	const CONSOLE_MACRO_MESSAGE = "[%%MESSAGE%%]";
	const CONSOLE_MACRO_ERROR = "[%%ERROR%%]";
	const CONSOLE_MACRO_LAUNCHED = "[%%LAUNCHED%%]";

	// Master array of FPC status lines for regex parser
	private $status = array(	"^Target OS: (.*)"=>STATUS_TARGET,
														"^Compiling (.*)"=>STATUS_COMPILING,
														"^Linking (.*)"=>STATUS_LINKING,
														"^(.*)\(([0-9]+),([0-9]+)\) (Error|Fatal|Warning|Note):(.*)"=>STATUS_ERROR,
														"^(Error|Fatal|Warning|Note):\s*(.*)"=>STATUS_ERROR_MAIN,
														);
	
	
	public $project;					// Path of the project directory
	public $target;						// Name of the active target
	public $target_loader;				// Instance of the target loader class
	public $resolved_paths;				// Array of source files resolved to their path (from target data)
	
	public $ignore_warnings = true;		// Warnings will not be printed to the console
	public $ignore_notes = true;		// Notes will not be printed to the console
	public $print_raw_output = true;	// Prints RAW compiler messages to STDOUT
	
	private $executable_build = array();
	private $build_phase = 0;			// Current build phase for universal binaries
	private $launch_mode;				// The mode in which bundles are launched
	private $source_paths = array();	// Array of resolved source paths for the current build
	private $cocoa_dialog;			// Cocoa dialog reference
	
	/**
	 * Utilities
	 */
	
	// Returns the OS X system version we're running in FPC macro friendly format without minor version
	// I.E. 10.5.8 = 105
	private function osx_system_version () {
		if ($handle = popen("/usr/bin/sw_vers", "r")) {
		    while (!feof($handle)) {
				$buffer = fgets($handle, 1024);
				if (eregi("^ProductVersion:[[:space:]]*(.*)", $buffer, $captures)) {
					$parts = explode(".", $captures[1]);
					return $parts[0].$parts[1];
				}
			}
			pclose($handle);
		}

		// Return 10.0 which is the olden system we can run on
		return "100";
	}

	/**
	 * Console Utilities
	 */
	
	// Prints a compiler error to the console
	private function print_error ($error, $die) {
		print(self::CONSOLE_MACRO_ERROR."<span style=\"font-weight:bold; color:red\">$error</span><br/>\n");
		flush();
		if ($die) die();
	}
		
	// Prints a message to the console
	private function print_message ($message) {
		
		// Convert [] to path links
		//$message = preg_replace("/\[([^\]]*)\]/i", "<a href=\"#\" class=\"path\">$1</a>", $message);
		
		print(self::CONSOLE_MACRO_MESSAGE."$message\n");
		flush();
	}
	
	// Returns a HTML formatted error message
	private function convert_html_error ($level, $message, &$file, $line, $column) {
		$message = trim($message, " ");
		
		// Resolve the file path
		if ($file) {
			/*
				WARNING: we need to cache the resolved files some where on disk otherwise this takes forever!
				if we put them in the build directory then users can clean the target to remove bad paths
				or we could resolve upon clicking via javascript?
			*/
			if (!array_key_exists($file, $this->source_paths)) {
				$path = $this->target_loader->resolve_path($file);
				$this->source_paths[$file] = $path;
			} else {
				$path = $this->source_paths[$file];
			}
			
			// Return the resolved path
			$file = $path;
			
			if (file_exists($path)) {
				$error = "<a href=\"txmt://open/?url=file://$path&line=$line&column=$column\">$message</a><br>";
			} else {
				$error = "<span class=\"error-no-source\">$message</a><br>";
			}
		} else {
			$error = "<span class=\"error-no-source\">$message</a><br>";
		}
		
		return $error;
	}
	
	// Parses compiler output into HTML format and returns the link
	private function parse_status ($line, &$fatal, &$full_error) {
		$error = null;
		$full_error = null;
		
		foreach ($this->status as $key => $value) {
			if (eregi($key, $line, $captures)) {
				
				switch ($value) {
					case STATUS_ERROR_MAIN: {
						$file = null;
						$error = $this->convert_html_error($captures[1], $captures[2], &$file, null, null);

						$fatal = true;
						
						return $error;
					}
					case STATUS_ERROR: {
						$level = $captures[4];
						
						$error = $this->convert_html_error($level, $captures[5], &$captures[1], $captures[2], $captures[3]);
						
						// Return the full error
						$full_error = array();
						$full_error["path"] = $captures[1];
						$full_error["line"] = $captures[2];
						$full_error["column"] = $captures[3];
						$full_error["message"] = $captures[5];

						// Ignore errors
						if (($level == "Note") && ($this->ignore_notes)) $error = null;
						if (($level == "Warning") && ($this->ignore_warnings)) $error = null;
						
						// Fatal errors that stop compilation
						if (($level == "Error") || ($level == "Fatal")) $fatal = true;

						return $error;
					}

					default:
						break;
				}
				
			}
		}
		
		return $error;
	}
		
	// Prints errors to console
	private function print_errors ($errors) {
		$count = count($errors);
		
		if (count($errors) > 0) {
			foreach ($errors as $value) {
				print(self::CONSOLE_MACRO_ERROR."<tr><td class=\"error\">$value</td></tr>\n");
			} 
		}
		
		/*
		print("<p class=\"caption\">Found $count errors.</p>");
		print("<div class=\"errors\">");
		
		if (count($errors) > 0) {
			$error_text .= "<ol>\n";
			foreach ($errors as $value) {
				$error_text .= "<li>$value</li>\n";
			} 
			$error_text .= "</ol>\n";
		}
		
		print($error_text);
		print("</div>");
		*/
	}
		
	/**
	 * Launching
	 */

	private function launch_process ($command, $cwd, $show_output) {
		$descriptor_spec = array(
			0 => array("pipe", "r"),  // stdin
			1 => array("pipe", "w"),  // stdout
			//2 => array("pipe", "a"),  // stderr
		);

		$env = null;

		$process = proc_open($command, $descriptor_spec, $pipes, $cwd, $env);

		if (is_resource($process)) {
			
			// Read the output line by line
	    while (!feof($pipes[1])) {
	        if ($buffer = fgets($pipes[1], 4096)) {
						if ($show_output) $this->print_message($buffer);
					}
	    }
			
			// Close the process
		  fclose($pipes[1]);
			return proc_close($process);
		}
	}

	// Launches the application bundle
	private function launch_bundle () {
		$bundle = $this->target_loader->get_bundle_binary_path();
		$bundle_name = basename($this->target_loader->bundle);
		
		// Print the console macro
		print(self::CONSOLE_MACRO_LAUNCHED."\n");


		switch ($this->launch_mode) {
			case self::LAUNCH_MODE_CONSOLE: {
				$this->print_message("Launching $bundle_name...");
				$this->launch_process("\"$bundle\" 2>&1", null, true);
				break;
			}
			
			case self::LAUNCH_MODE_TERMINAL: {
				$this->print_message("Launching $bundle_name in Terminal...");
				
				$command = "/usr/bin/osascript <<EOF\n";
				$command .= "tell application \"Terminal\"\n";
				$command .= "	if (count of windows) is 0 then\n";
				$command .= "		do script \"'$bundle'\"\n";
				$command .= "	else\n";
				$command .= "		do script \"'$bundle'\" in window 1\n";
				$command .= "	end if\n";
				$command .= "	activate\n";
				$command .= "end tell\n";
				$command .= "EOF\n";

				// Execute the osascript
				exec($command);
				
				// Activate TextMate again once the process is complete
				// ??? how do we know the process quit?
				//exec("open -a TextMate");
				
				break;
			}
		}
	}
	
	// Launches the GDB debugger via Terminal.app
	private function launch_debugger () {
		global $preferences;
		
		// Generate the GDB command
		$script = "";
		
		// ??? We need new target settings for debugging
		
		// Malloc stack logging
		//$script .= "set env MallocStackLogging 1\n";
		
		// Malloc debug
		//$script .= "set env DYLD_INSERT_LIBRARIES /usr/lib/libMallocDebug.A.dylib\n";
		//$script .= "break malloc_printf\n";
		//$script .= "break malloc_error_break\n";
		
		// Debug guarded memory
		//$script .= "set env DYLD_INSERT_LIBRARIES /usr/lib/libgmalloc.dylib\n";
		
		// CFLog
		//$script .= "break CFLog\n";
		//$script .= "break NSLog\n";
		
		// General
		// $script .= "break \"+[NSException raise]\"\n";
		// $script .= "break objc_exception_throw\n";
		// $script .= "break _NSAutoreleaseNoPool\n";
		// $script .= "break _objc_fatal\n";
		// $script .= "break malloc_error_break\n";
		// $script .= "break CGErrorBreakpoint\n";
		
		// Global break points
		$script .= $preferences->get_string_value("gdb_breakpoints");
		$script .= "\n";
		
		// Target break points
		$script .= $this->target_loader->get_string_value("debugger_breakpoints");
		$script .= "\n";
		
		// User break points
		$this->print_message("Searching for break points...");
		$breakpoints = new Breakpoints();
		$breakpoints->set_target($this->target_loader);
		if ($results = $breakpoints->find()) {
			$script .= $breakpoints->get_gdb_command($results);
		}
		
		// Append source paths
		foreach ($this->target_loader->get_resolved_paths() as $path) {
			if (eregi("([ ]+)", $path)) {
				$script .= "directory \"$path\"\n";
			} else {
				$script .= "directory $path\n";
			}
		}
		
		$script .= "run\n";

		// Save the GDB command file in the build folder
		$script_path = $this->target_loader->output."/debug.txt";
		file_put_contents($script_path, $script);
		
		// Get the path of the executable
		//$executable_path = $this->target_loader->binary."/".$this->target_loader->executable;
		$executable_path = $this->target_loader->get_bundle_binary_path();

		// Build Terminal command
		$command = "/usr/bin/osascript <<EOF\n";
		
		$command .= "tell application \"Terminal\"\n";
		$command .= "	activate\n";
		$command .= "	if (count of windows) is 0 then\n";
		$command .= "		do script \"/usr/bin/gdb --command='$script_path' '$executable_path' \"\n";
		$command .= "	else\n";
		$command .= "		do script \"/usr/bin/gdb --command='$script_path' '$executable_path' \" in window 1\n";
		$command .= "	end if\n";
		$command .= "end tell\n";
		$command .= "EOF\n";

		// Execute the osascript
		$this->print_message("Launching Terminal...");
		exec($command);
	}

	// Launches via Xcode for iOS devices
	private function launch_ios_device () {
		global $preferences;
		
		// Verify settings
		if (!file_exists($this->target_loader->sdks[PLATFORM_IPHONE_DEVICE])) $this->print_error("The iOS device SDK can't be found.", true);
		
		$xcode_project_configuration = $this->target_loader->get_string_value("xcode_active_configuration");
		$xcode_project_path = $this->target_loader->get_string_value("xcode_project");
		$xcode_project_name = basename($xcode_project_path);
		$xcode_project_bundle = "$xcode_project_path/build/$xcode_project_configuration-iphoneos/$xcode_project_name.app";
		$xcode_project_file = "$xcode_project_path/$xcode_project_name.xcodeproj";
		$xcode_project_path_osascript = str_replace("/", ":", $project_path);
		$xcode_project_path_osascript = trim($project_path_osascript, ":");
		
		// Missing Xcode project
		if (!file_exists($xcode_project_path)) $this->print_error("The Xcode helper project can not be located at \"$xcode_project_path\".", true);
		
		// Copy resources into Xcode bundle
		$source = $this->target_loader->bundle;
		$dest = dirname($xcode_project_bundle);
		$this->print_message("Copying \"$source\" to Xcode project at \"$dest\".");
		
		exec("/bin/cp -R \"$source\" \"$dest\" ");
		
		// Remove resources that will be generated by Xcode
		unlink("$xcode_project_bundle/info.plist");
		unlink("$xcode_project_bundle/Info.plist");

		// Code sign the bundle
		// ??? DEPRECATED
		/*
		$identity = $this->target_loader->get_string_value("xcode_code_signing_identity");
		$this->print_message("Code signing the bundle as \"$identity\".");
		$command = "/usr/bin/codesign -f -s \"$identity\" \"$xcode_project_bundle\"";
		
		$this->print_message($command);
		if ($result = exec($command)) {
		}
		*/
		
		// Build the Xcode project
		$xcodebuild = $preferences->get_string_value("xcodebuild");
		if (!file_exists($xcodebuild)) $this->print_error("xcodebuild utility can not be located at \"$xcodebuild\".", true);
		
		$this->print_message("Building Xcode project.");
		$command = "\"$xcodebuild\" -activetarget -configuration $xcode_project_configuration -project=\"$xcode_project_name\" build";
		//$this->print_message($command);
		$this->launch_process($command, $xcode_project_path, true);
		
		// Open the Xcode project (in the background)
		$xcode_app = $preferences->get_string_value("xcode");
		if (!file_exists($xcode_app)) $this->print_error("Xcode can not be located at \"$xcode_app\".", true);
		
		$command = "open -a \"$xcode_app\" \"$xcode_project_file\" -g";
		//$this->print_message($command);
		exec($command);
		
		// Run the project from AppleScript
		$this->print_message("Installing application to device via Xcode.");
		$command = <<<TEXT
tell application \"Xcode\"
	open \"$xcode_project_path_osascript:$xcode_project_name.xcodeproj\"
	tell project \"$xcode_project_name\"
		debug
	end tell
end tell
TEXT;
		exec_async("/usr/bin/osascript -e \"$command\"");
	}
	
	// Launches the iOS simulator
	private function launch_ios_simulator () {
		global $preferences;
				
		// Get the simulator support folders
		$path = expand_tilde_path($preferences->get_string_value("iphone_sim_support"));
		$version = $this->target_loader->simulator_sdk_version;
		$uid = exec("/usr/bin/uuidgen");
		$binary = $this->target_loader->bundle_binary;
		
		$source = $this->target_loader->bundle;
		$destination = "$path/$version/Applications/$uid";
		
		// Create the application folder
		if (!file_exists($destination)) {
			if (!mkdir($destination, 0777, true)) {
				$this->print_error("There was an error creating the simulator application folder at \"$destination\".", true);
			} else {
				mkdir("$destination/Documents", 0777, true);
				mkdir("$destination/Library", 0777, true);
				mkdir("$destination/Library/Caches", 0777, true);
				mkdir("$destination/Library/Preferences", 0777, true);
				mkdir("$destination/tmp", 0777, true);
			}
		}
		
		// Copy the bundle into the simulator SDK folder
		$destination = "$destination/".basename($this->target_loader->bundle);
		
		// Delete the old bundle
		if (file_exists($destination)) exec("rm -R \"$destination\"");
		
		// ??? make copy_directory glue to cp -R which is safer on OS X
		$result = exec("/bin/cp -R \"$source\" \"$destination\"");
		if ($result == "") {
		//if (copy_directory($source, $destination)) {
			$this->print_message("Copied the bundle to \"$destination\"");
		} else {
			$this->print_error("There was an error copying the bundle to \"$destination\"", true);
		}
		
		// ??? how do we terminate the last iOS sim instance? killall is not working
				
		// Launch the simulator app
		$app = expand_tilde_path($preferences->get_string_value("iphone_sim_app"));
		
		// Launch the simulator
		//exec("/usr/bin/open \"$app\"");
		
		// Launch a single instance of the app
		$simulator_path = "$app/Contents/MacOS/iPhone Simulator";
		
		if (file_exists($simulator_path)) {
			exec_async("\"$simulator_path\" -SimulateApplication \"$destination/$binary\"");
		} else {
			$this->print_error("The iOS simulator app can't be found at \"$simulator_path\"", true);
		}
	}
	
	/**
	 * Compiling Utilities
	 */
	
	// Compiles a XIB into a NIB and writes it to the destination directory
	private function compile_xib ($xib, $nib) {
		global $preferences;
				
		// Get the ibtool path from preferences
		// ??? make get_string_value convert macros get_string_value(key, [convert])
		$ibtool = expand_tilde_path($preferences->get_string_value("ibtool"));
		
		// Execute the tool
		if (!file_exists($ibtool)) {
			$this->print_error("ibtool can not be found at \"$ibtool\". Please set the correct location in the preferences.", true);
		} else {
			$this->print_message("Converting ".basename($xib). " to ".basename($nib));
			$sdk = "";//"--sdk /Developer/SDKs/MacOSX10.6.sdk";
			
			exec("\"$ibtool\" --errors --warnings --notices --output-format human-readable-text --compile \"$nib\" \"$xib\" $sdk");
			
			// Delete the xib which was copied into the bundle because it will be compiled to nib
			$xib_dest = dirname($nib)."/".remove_file_extension(basename($nib)).".xib";
			if (file_exists($xib_dest)) unlink($xib_dest);
		}
	}
	
	// Sets the executable build phase version and moves to build folder (for universal binaries)
	private function set_executable_version ($version) {
		
		$executable = $this->target_loader->executable;
		$output = $this->target_loader->output;
		
		$source = $this->target_loader->executable_directory."/$executable";
		$destination = "$output/$executable.$version";
		
		rename($source, $destination);
		
		$this->executable_build[$version] = $destination;
	}
	
	// Moves the executable into the bundle
	private function move_executable () {
		
		$executable = $this->target_loader->executable;
		$binary = $this->target_loader->binary;
		$bundle = $this->target_loader->bundle;
		$bundle_name = $this->target_loader->bundle_binary;
		
		$source = $this->target_loader->executable_directory."/$executable";
		$destination = "$binary/$bundle_name";
		
		//exec("/usr/bin/cp $source $destination");
		//if (copy($source, $destination)) {
		if (rename($source, $destination)) {
			$this->print_message("Moved executable to $destination.");
		} else {
			$this->print_error("There was an error moving the executable from $source to $destination", true);
		}
	}
	
	// Copies a info.plist file with macros
	private function copy_info_plist ($source, $destination) {
		if (copy($source, "$destination/".basename($source))) {
			$this->print_message("Copied Info.plist to bundle");
		} else {
			$this->print_error("There was an error copying the info.plist from $source to $destination", true);
		}
	}
	
	// Copies a info.plist file with macros
	private function copy_localization ($source, $destination) {
		
		$localization_name = basename($source);
		$destination_base = $destination;
		$destination = "$destination/$localization_name";
		
		if (copy_directory($source, $destination)) {
			$contents = directory_contents($destination);
			
			foreach ($contents as $path) {
				
				$file = basename($path);
				$name_clean = substr($file, 0, strrpos($file, ".")); 
				
				// Compile XIBs
				if (eregi("\.(xib)+$", $file)) {
					$this->compile_xib($path, "$destination/$name_clean.nib");
				} else if (is_file($path)) { // Copy files of any type
					copy($path, "$destination/$file");
				}
			}

			$this->print_message("Copied localization $localization_name to bundle");
		} else {
			$this->print_error("There was an error copying the localization $localization_name from $source to $destination_base", true);
		}
		
	}
	
	
	// Copies and compiles any resources required for the target bundle
	private function copy_resources () {
		
		$directory = $this->target_loader->resources;
		$bundle = $this->target_loader->bundle;
		
		$this->print_message("Copying resources into $bundle...");

		// Iterate the target resources directory
		if ($handle = opendir($directory)) {
		    while (false !== ($file = readdir($handle))) {
		        if ($file != "." && $file != "..") {
				
					$name_clean = substr($file, 0, strrpos($file, ".")); 
					$path = "$directory/$file";
					
					// Copy depending on target platform
					switch ($this->target_loader->platform) {
						
						case PLATFORM_PPC:
						case PLATFORM_INTEL: {
							// copy .lproj directories from Resource folder into /Contents/Resources
							$resource_path = "$bundle/Contents/Resources";
							
							// Copy info.plist
							if ($file == "Info.plist") $this->copy_info_plist($path, "$bundle/Contents");
							
							// Copy localization folders
							if (eregi("\.(lproj)+$", $file)) $this->copy_localization($path, $resource_path);
							
							// Compile XIBs
							if (eregi("\.(xib)+$", $file)) $this->compile_xib("$directory/$name_clean.xib", "$resource_path/$name_clean.nib");
							
							break;
						}
						
						case PLATFORM_IPHONE_SIMULATOR: {
							$resource_path = $bundle;
							
							if (eregi("\.(lproj)+$", $file)) { // Copy localization folders
								$this->copy_localization($path, $resource_path);
							} else if (eregi("\.(xib)+$", $file)) { // Compile XIB's into bundle
								$this->compile_xib("$directory/$name_clean.xib", "$resource_path/$name_clean.nib");
							} else if (is_file($path)) { // Copy files of any type
									copy($path, "$resource_path/$file");
							}
							
							break;
						}
						
						case PLATFORM_IPHONE_DEVICE: {
							$resource_path = $bundle;
							
							if (eregi("info\.plist$", $file)) { // Convert info.plist to binary
								// ??? ignore info.plist so Xcode can build it
								//$dest_path = "$resource_path/$file";
								//copy($path, $dest_path);
								//exec("/usr/bin/plutil -convert binary1 \"$dest_path\" -o \"$dest_path\"");
							} else if (eregi("\.(lproj)+$", $file)) { // Copy localization folders
								$this->copy_localization($path, $resource_path);
							} else if (eregi("\.(xib)+$", $file)) { // Compile XIB's into bundle
								$this->compile_xib("$directory/$name_clean.xib", "$resource_path/$name_clean.nib");
							} else if (is_file($path)) { // Copy files of any type
									copy($path, "$resource_path/$file");
							}
							
							break;
						}
						
					}

		        }
		    }
		    closedir($handle);
		}
		
	}
		
	// Append additional command line options
	private function append_compiler_options ($command) {
		
		// System version macro
		$system_version = $this->osx_system_version();
		$command .= "-dMAC_SYSTEM_VERSION:=$system_version ";
		
		// Debugging options
		if ($this->launch_mode == self::LAUNCH_MODE_DEBUG) {
			$command .= $this->target_loader->advanced_debugging." ";
		}
		
		// Platform specific options
		switch ($this->target_loader->platform) {
			case PLATFORM_IPHONE_DEVICE: {
				$command .= "-XR".$this->target_loader->sdks[SDK_IPHONE_DEVICE]." ";
				$command .= "-XX ";
				$command .= "-Cfvfpv2 ";
				
				// Add debugging options since we run the project by debugging
				$command .= $this->target_loader->advanced_debugging." ";	
				
				break;
			}
			
			case PLATFORM_IPHONE_SIMULATOR: {
				// Add -XR for iPhone SDK
				$command .= "-XR".$this->target_loader->sdks[SDK_IPHONE_SIMULATOR]." ";
				$command .= "-XX ";
				$command .= "-Tiphonesim ";
				break;
			}
		}
		
		return $command;
	}	
		
	// Invoked when the first error is encountered
	private function invoke_default_error ($error) {
		global $preferences;
		
		// Show error popup
		//$path = $_ENV["TM_BUNDLE_SUPPORT"]."/cocoa_dialog.php";
		//exec_async("php \"$path\" kind=\"bubble\" title=\"FPC\" text=\"this is text\"");
		
		if ($preferences->get_boolean_value("error_hud")) $this->cocoa_dialog->bubble("FPC", $error["message"]);
		
		// Jump to the error		
		textmate_file($error["path"], $error["line"]);
	}
		
	// Invoke the FPC compiler with our command 
	private function compile ($command) {
		
		// Increment build phase
		$this->build_phase++;
		
		// Append extra command line options
		$command = $this->append_compiler_options($command);
		
		// Verify the target before compiling
		$this->target_loader->verify();
		
		// Notify of univeral build phase
		if ($this->target_loader->platform == PLATFORM_UNIVERSAL) {
			$this->print_message("--- Build phase $this->build_phase of universal binary");
		}
		
		if ($handle = popen($command, "r")) {
			$errors = array();
			$output = "";
			$fatal = false;

			// Show the FPC command in the console
			if (($this->print_raw_output) && ($this->target_loader->advanced_show_fpc_command)) $this->print_message($command);
			
			// Read the output line by line
		    while (!feof($handle)) {
					$buffer = fgets($handle, 1024);
			
					if ($this->print_raw_output) $this->print_message($buffer);
			
					// Parse the status from the compiler output and return errors
					if ($error = $this->parse_status($buffer, &$fatal, &$full_error)) {
					
						// Go to the first error
						if ((count($errors) == 0) && ($full_error) && (file_exists($full_error["path"]))) {
							$this->invoke_default_error($full_error);
						}
					
						$errors[] = $error;
					}
		    }
			
			// Close process
			pclose($handle);
			
			// Print errors or finishing building
			if ((count($errors) > 0) && ($fatal)) {
				$this->print_errors($errors);
				die;
			} else {
				
				// Compile the second phase for universal platforms
				if (($this->target_loader->platform == PLATFORM_UNIVERSAL) && ($this->build_phase == 1)) {
					
					$this->set_executable_version(1);
					
					$this->compile($this->target_loader->get_full_command(2, $this->launch_mode));
					return;
				}
				
				// Compile the final phase for universal platforms
				if (($this->target_loader->platform == PLATFORM_UNIVERSAL) && ($this->build_phase == 2)) {
					
					$this->set_executable_version(2);
					
					$ppc = $this->executable_build[1];
					$intel = $this->executable_build[2];
					$universal = $this->target_loader->executable_directory."/".$this->target_loader->executable;
					
					$this->print_message("Creating universal binary");
					
					// Invoke lipo to create universal binary
					exec("/usr/bin/lipo -output \"$universal\" \"$intel\" \"$ppc\" -create");
					
					// Delete the ppc/intel binaries
					unlink($ppc);
					unlink($intel);
				}
				
				// Index symbols after compiling and no errors
				// This step is skipped for universal platforms to save time
				if (($this->target_loader->platform != PLATFORM_UNIVERSAL) && ($this->target_loader->advanced_index_symbols)) {
					$this->index_symbols();
				}
				
				// Move the executable into the bundle
				$this->move_executable();
				
				// Copy/compile all required resources into the application bundle
				if (file_exists($this->target_loader->resources)) {
					$this->copy_resources();
				}
				
				// Notify the compilation was successful
				$this->print_message("Compilation Successful!");
				
				// Launch the debugger
				if ($this->launch_mode == self::LAUNCH_MODE_DEBUG) {
					switch ($this->target_loader->platform) {
						case PLATFORM_PPC:
						case PLATFORM_INTEL: {
							$this->launch_debugger();
							break;
						}
						
						case PLATFORM_UNIVERSAL: {
							$this->print_error("Debugging for universal targets is not support, compile as PPC or Intel instead.");
							break;
						}

						case PLATFORM_IPHONE_SIMULATOR: {
							$this->print_error("Debugging for the iPhone simulator is not supported.");
							break;
						}
						
					}
				}
				
				// Launch the target bundle
				if (($this->launch_mode == self::LAUNCH_MODE_CONSOLE) || ($this->launch_mode == self::LAUNCH_MODE_TERMINAL)) {
					switch ($this->target_loader->platform) {
						case PLATFORM_PPC:
						case PLATFORM_UNIVERSAL:
						case PLATFORM_INTEL: {
							$this->launch_bundle();
							break;
						}

						case PLATFORM_IPHONE_SIMULATOR: {
							$this->launch_ios_simulator();
							break;
						}
						
						case PLATFORM_IPHONE_DEVICE: {
							$this->launch_ios_device();
							break;
						}
					}
				}
			}
		}
	}
	
	/**
	 * Build Commands
	 */
	
	public function build () {
		$this->launch_mode = self::LAUNCH_MODE_BUILD;
		$this->compile($this->target_loader->get_full_command(1, $this->launch_mode));
	}
	
	public function run ($terminal) {
		if ($terminal) {
			$this->launch_mode = self::LAUNCH_MODE_TERMINAL;
		} else {
			$this->launch_mode = self::LAUNCH_MODE_CONSOLE;
		}
		
		$this->compile($this->target_loader->get_full_command(1, $this->launch_mode));
	}

	public function debug () {
		$this->launch_mode = self::LAUNCH_MODE_DEBUG;
		$this->compile($this->target_loader->get_full_command(1, $this->launch_mode));
	}

	// Indexes the target symbols
	public function index_symbols () {
		if ($this->print_raw_output) $this->print_message("Indexing symbols (this may take a while)...\n");
		
		// Load the symbol parser
		$parser = new SymbolParser($this->target_loader->get_symbols_directory(), $this->target_loader->get_resolved_symbols());
		
		// ??? this needs to change accept file names!
		//$parser->set_ignore_files($this->target_loader->ignore_ppu);
		
		$parser->print_messages = true;
		$parser->print_prefix = "[%%MESSAGE%%]";
		
		if ($parser->parse(false)) {
			//$parser->generate_auto_complete_table(true, $this->project);
		}
	}

	function __construct ($project_path, $target_name) {
		
		//$this->launch_mode = self::LAUNCH_MODE_TERMINAL;
				
		$this->cocoa_dialog = new CocoaDialog($_ENV["TM_SUPPORT_PATH"]."/bin/CocoaDialog.app");
		
		$this->project = $project_path;
		$this->target = $target_name;
		
		$this->target_loader = new TargetLoader($project_path, $target_name);
		
		// Create the build directory
		$output = $this->target_loader->output;
		
		if (!file_exists($output)) mkdir($output, 0777);
	}
	
}

// Run from command line
if ($input["project"]) {
	$compiler = new CompilerHelper($input["project"], $input["target"]);
	
	switch ($input["mode"]) {
		case "run": {
			$compiler->run(true);
			break;
		}
		case "build": {
			$compiler->build();
			break;
		}
		case "debug": {
			$compiler->debug();
			break;
		}
	}
}

?>