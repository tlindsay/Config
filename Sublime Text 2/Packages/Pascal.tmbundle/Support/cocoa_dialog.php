<?

require_once("common.php");

// CocoaDialog PHP support class
class CocoaDialog {
	
	const DIALOG_FILE_SELECT = "fileselect";
	const DIALOG_FILE_SAVE = "filesave";
	const DIALOG_MESSAGE_BOX = "msgbox";
	const DIALOG_YES_NO_MESSAGE_BOX = "yesno-msgbox";
	const DIALOG_OK_MESSAGE_BOX = "ok-msgbox";
	const DIALOG_TEXT_BOX = "textbox";
	const DIALOG_PROGRESS_BAR = "progressbar";
	const DIALOG_INPUT_BOX = "inputbox";
	const DIALOG_STANDARD_INPUT_BOX = "standard-inputbox";
	const DIALOG_SECURE_INPUT_BOX = "secure-inputbox";
	const DIALOG_SECURE_STANDARD_INPUT_BOX = "secure-standard-inputbox";
	const DIALOG_DROPDOWN = "dropdown";
	const DIALOG_STANDARD_DROPDOWN = "standard-dropdown";
	const DIALOG_BUBBLE = "bubble";
	
	var $app;					// Path to the CocoaDialog application
	var $type;					// Type of dialog to display
	var $parameters = array();	// Array of all parameters
	var $async = false;
	
	/*
	http://cocoadialog.sourceforge.net/documentation.html#global_options
	
	‑‑title "text for title"	Sets the window's title
	‑‑string‑output	Makes yes/no/ok/cancel buttons return values as "Yes", "No", "Ok", or "Cancel" instead of integers. When used with custom button labels, returns the label you provided.
	‑‑no‑newline	By default, return values will be printed with a trailing newline. This will suppress that behavior. Note that when a control returns multiple lines this will only suppress the trailing newline on the last line.
	‑‑width integer	Sets the width of the window. It's not advisable to use this option without good reason, and some controls won't even respond to it. The automatic size of most windows should suffice.
	‑‑height integer	Sets the height of the window. It's not advisable to use this option without good reason, and some controls won't even respond to it. The automatic size of most windows should suffice.
	‑‑debug	 If you are not getting the results you expect, try turning on this option. When there is an error, it will print ERROR: followed by the error message.
	‑‑help	 Gives a list of options and a link to this page.
	*/
	
	
	// Standard bubble popup
	public function bubble ($title, $text) {
	
	$this->type = self::DIALOG_BUBBLE;
	$this->async = true;
	
	$this->parameters["title"] = $title;
	$this->parameters["text"] = $text;
	$this->parameters["x‑placement"] = "right";
	$this->parameters["y‑placement"] = "top";
	$this->parameters["icon"] = "info";
	$this->parameters["timeout"] = "4";

	/*
	Options for single or multiple bubbles:
	‑‑timeout numSeconds	The amount of time, in seconds, that the bubble(s) will be displayed. Clicking them will make them closer sooner. 
	Unlike other dialogs, bubbles time out by default. 
	Default value is 4.
	‑‑no‑timeout	Don't time out. By default the bubbles will time out after 4 seconds. With this option enabled, they will stay visible until the user clicks them.
	‑‑alpha alphaValue	The alpha value (controls transparency) for the bubble(s). A number between 0 and 1. 
	Default is 0.95.
	‑‑x‑placement placement	 This can be left, right, or center.
	‑‑y‑placement placement	 This can be top, bottom, or center.
	Options for a single bubble:
	‑‑text "body of the bubble"	required. The body text of the bubble.
	‑‑title "title of the bubble"	required. The title of the bubble.
	‑‑icon stockIconName	The name of the stock icon to use. This is incompatible with --icon-file 
	Default is cocoadialog
	‑‑icon‑file "/full/path/to/icon file"	The full path to the custom icon image you would like to use. Almost every image format is accepted. This is incompatible with the --icon option.
	‑‑text‑color colorHexValue	The color of the text on the bubble in 6 character hexadecimal format (like you use in html). Do not prepend a "#" to this value. Examples: "000000" for black, or "ffffff" for white. 
	The default is determined by your system, but should be 000000.
	‑‑border‑color colorHexValue	The color of the border in 6 character hexadecimal format (like you use in html). Do not prepend a "#" to this value. Examples: "000000" for black, or "ffffff" for white. 
	The default is 808080.
	‑‑background‑top colorHexValue	The color of the top of the background gradient in 6 character hexadecimal format (like you use in html). Do not prepend a "#" to this value. Examples: "000000" for black, or "ffffff" for white. 
	The default is B1D4F4.
	‑‑background‑bottom colorHexValue	The color of the bottom of the background gradient in 6 character hexadecimal format (like you use in html). Do not prepend a "#" to this value. Examples: "000000" for black, or "ffffff" for white. 
	The default is EFF7FD.
	Options for a multiple bubbles:
	‑‑texts List of bodies for the bubbles	required. A list of body texts to use in the bubbles. Example: "This is bubble 1" bubble2 "and bubble 3" 
	This must have the same number of items as the --titles list.
	‑‑titles List of titles for the bubbles	required. A list of titles to use in the bubbles. Example: "Title for bubble 1" "And bubble2" "Bubble 3" 
	This must have the same number of items as the --texts list.
	‑‑icons List of stock icon names	The names of the stock icons to use. This is incompatible with --icon-files. If there are less icon names provided than there are bubbles, it will use the default for the remaining. 
	Defaults are cocoadialog
	‑‑icon‑files List of full paths to icon files	A list of files to use as icons. This is incompatible with --icons. If there are less icon files provided than there are bubbles, it will use the default for the remaining. 
	Look at the Icons section to see how to mix custom icons with stock icons.
	‑‑text‑colors List of hex colors	See the single bubble section for details.
	‑‑border‑colors List of hex colors	See the single bubble section for details.
	‑‑background‑tops List of hex colors	See the single bubble section for details.
	‑‑background‑bottoms List of hex colors	See the single bubble section for details.
	‑‑independent	This makes clicking one bubble not close the others. The default behavior is to close all bubbles when you click one.
		*/
	
	$this->run();
}
	
	// Opens a standard drop down box
	public function standard_dropdown ($title, $message, $items, $pulldown, $exit_onchange, $no_cancel, $float) {
		
		$this->type = self::DIALOG_STANDARD_DROPDOWN;

		$this->parameters["title"] = $title;
		$this->parameters["text"] = $message;
		if ($pulldown) $this->parameters["pulldown"] = null;
		if ($exit_onchange) $this->parameters["exit‑onchange"] = null;
		if ($no_cancel) $this->parameters["no‑cancel"] = null;
		if ($float) $this->parameters["float"] = null;

		$this->parameters["items"] = array();

		foreach ($items as $value) {
			$this->parameters["items"][] = "\"$value\"";
		}
		
		/*
		‑‑text "text"	This is the text for the label above the dropdown box.
		‑‑items list of values	required. These are the labels for the options provided in the dropdown box. list of values should be space separated, and given as multiple arguments (ie: don't double quote the entire list. Provide it as you would multiple arguments for any shell program). The first item in the list is always selected by default. 
		Example: CocoaDialog dropdown --text "Favorite OS?" --items "GNU/Linux" "OS X" Windows Amiga "TI 89" --button1 "Ok"
		‑‑pulldown	Sets the style to a pull-down box, which differs slightly from the default pop-up style. The first item remains visible. This option probably isn't very useful for a single-function dialog such as those CocoaDialog provides, but I've included it just in case it is. To see how their appearances differ, just try them both.
		‑‑exit‑onchange	Makes the program exit immediately after the selection changes, rather than waiting for the user to press one of the buttons. This makes the return value for the button 4 (for both regular output and with --string-output).
		‑‑no‑cancel	Don't show a cancel button.
		‑‑float	Float on top of all windows.
		‑‑timeout numSeconds	The amount of time, in seconds, that the window will be displayed if the user does not click a button. 
		Does not time out by default.
		*/
		
		$response = $this->run();
		
		$parts = explode("\n", $response);
		
		// If the result was canceled return null
		if ($parts[0] == 2) return null;
		
		// Return the item
		return $items[$parts[1]];
	}
	
	// Opens a select folder dialog
	public function select_folder ($title, $message, $multiple_selection, $start_directory) {

		$this->type = self::DIALOG_FILE_SELECT;
		
		$this->parameters["title"] = $title;
		$this->parameters["text"] = $message;
		$this->parameters["select‑multiple"] = null;
		$this->parameters["select-directories"] = null;
		if ($only_directories) $this->parameters["select-only-directories"] = null;
		if ($start_directory) $this->parameters["with‑directory"] = $start_directory;
		
		/*
			‑‑text "main text message"	This is the text displayed at the top of the fileselect window.
			‑‑select‑directories	Allow the user to select directories as well as files. Default is to disallow it.
			‑‑select‑only‑directories	Allows the user to select only directories.
			‑‑packages‑as‑directories	Allows the user to navigate into packages as if they were directories, rather than selecting the package as a file.
			‑‑select‑multiple	Allow the user to select more than one file. Default is to allow only one file/directory selection.
			‑‑with‑extensions list of extensions	 Limit selectable files to ones with these extensions. list of extensions should be space separated, and given as multiple arguments (ie: don't double quote the list). 
			Example: CocoaDialog fileselect --with-extensions .c .h .m .txt 
			The period/dot at the start of each extension is optional.
			‑‑with‑directory directory	 Start the file select window in directory. The default value is up to the system, and will usually be the last directory visited in a file select dialog.
			‑‑with‑file file	 Start the file select window with file already selected. By default no file will be selected. This must be used with --with-directory. It should be the filename of a file within the directory.		
		*/
		
		return trim($this->run(), " 	\n");
	}
	
	// Opens a select file dialog
	public function select_file ($title, $message, $allow_directories, $multiple_selection, $start_file, $extensions, $start_directory) {

		$this->type = self::DIALOG_FILE_SELECT;
		
		$this->parameters["title"] = $title;
		$this->parameters["text"] = $message;
		if ($multiple_selection) $this->parameters["select‑multiple"] = null;
		if ($allow_directories) $this->parameters["select-directories"] = null;
		if ($start_file) $this->parameters["with‑file"] = $start_file;
		if ($extensions) $this->parameters["with-extensions"] = $extensions;
		if ($start_directory) $this->parameters["with‑directory"] = $start_directory;
		
		/*
			‑‑text "main text message"	This is the text displayed at the top of the fileselect window.
			‑‑select‑directories	Allow the user to select directories as well as files. Default is to disallow it.
			‑‑select‑only‑directories	Allows the user to select only directories.
			‑‑packages‑as‑directories	Allows the user to navigate into packages as if they were directories, rather than selecting the package as a file.
			‑‑select‑multiple	Allow the user to select more than one file. Default is to allow only one file/directory selection.
			‑‑with‑extensions list of extensions	 Limit selectable files to ones with these extensions. list of extensions should be space separated, and given as multiple arguments (ie: don't double quote the list). 
			Example: CocoaDialog fileselect --with-extensions .c .h .m .txt 
			The period/dot at the start of each extension is optional.
			‑‑with‑directory directory	 Start the file select window in directory. The default value is up to the system, and will usually be the last directory visited in a file select dialog.
			‑‑with‑file file	 Start the file select window with file already selected. By default no file will be selected. This must be used with --with-directory. It should be the filename of a file within the directory.		
		*/
		
		return $this->run();
	}
	
	// Opens a save dialog
	public function save_file ($title, $message, $start_file, $extensions, $start_directory) {
		
		$this->type = self::DIALOG_FILE_SAVE;
		
		$this->parameters["title"] = $title;
		$this->parameters["text"] = $message;
		if ($start_file) $this->parameters["with‑file"] = $start_file;
		if ($extensions) $this->parameters["with-extensions"] = $extensions;
		if ($start_directory) $this->parameters["with‑directory"] = $start_directory;

		/*
		‑‑text "main text message"	This is the text displayed at the top of the filesave window.
		‑‑packages‑as‑directories	Allows the user to navigate into packages as if they were directories, rather than selecting the package as a file.
		‑‑no‑create‑directories	Prevents the user from creating new directories.
		‑‑with‑extensions list of extensions	 Limit selectable files (including files the user creates) to ones with these extensions. list of extensions should be space separated, and given as multiple arguments (ie: don't double quote the list). 
		Example: CocoaDialog filesave --with-extensions .c .h .m .txt 
		The period/dot at the start of each extension is optional.
		‑‑with‑directory directory	 Start the file save window in directory. The default value is up to the system, and will usually be the last directory visited in a file dialog.
		‑‑with‑file file	 Start the file save window with file already selected. By default no file will be selected. This must be used with --with-directory. It should be the filename of a file within the directory.		
		*/
		
		return trim($this->run(), " 	\n");
	}		
	
	// Runs CocoaDialog once the command is configured from the various high-level functions
	private function run () {
		$output = "";
		
		// Build the command line string
		$command_line = $this->type;
		
		foreach ($this->parameters as $key => $value) {
			if ($value) {
				if (gettype($value) == "array") {
					$command_line .= " --$key ".implode(" ", $value);
				} else {
					$command_line .= " --$key \"$value\"";
				}
			} else {
				$command_line .= " --$key";
			}
		}
		
		$command = "$this->app/Contents/MacOS/CocoaDialog $command_line";
		//print("$command\n");
		
		// Launch the CocoaDialog app
		if ($this->async) {
			
			exec_async($command);
			
		} else {
			if ($handle = popen($command, "r")) {

			    while (!feof($handle)) {
			        $buffer = fgets($handle, 4096);
			        $output .= $buffer;
			    }

				pclose($handle);
			}
		}

		return $output;
	}
			
	function __construct ($app) {
		$this->app = $app;
	}
	
}

$input = script_input();
//print_r($GLOBALS);

switch ($input["kind"]) {
	
	case "bubble": {
		$cocoa_dialog = new CocoaDialog($_ENV["TM_SUPPORT_PATH"]."/bin/CocoaDialog.app");
		
		$cocoa_dialog->bubble($input["title"], $input["text"]);
		
		break;
	}
	
}
//$dialog = new CocoaDialog("/Applications/TextMate.app/Contents/SharedSupport/Support/bin/CocoaDialog.app");
//$dialog->bubble("Warning", "There was an error with some line in your code fool!");

//$path = $dialog->save_file("Save Project", "Choose a location to save the new project", null, null, null);
//print("path = $path");
?>