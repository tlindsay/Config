<?

require_once("cocoa_dialog.php");
require_once("common.php");
require_once("plist.php");

class ProjectManager {
	
	private $cocoa_dialog;			// Path to the CocoaDialog application
	private $standard_templates;	// Path to standard template directory
	private $user_templates = "/Library/Application Support/TextMate/Pascal Project Templates";		// Path to user template directory;
	
	const MACRO_PROJECT = "[%project%]";
	
	// Replaces all occurences of the macro array in the file
	private function replace_macros ($file, $macros) {
		$contents = file_get_contents($file);
		$contents = str_replace(array_keys($macros), array_values($macros), $contents);
		file_put_contents($file, $contents);
	}
	
	// Opens the project in TextMate
	private function open_project ($project) {
		exec("/usr/bin/open -a TextMate '$project'");
	}
	
	// Copies the template into the new location
	private function copy_template ($template, $location) {
		
		// Duplicate file
		if (file_exists($location)) {
			die("There is already a file at the location.\n");
		}
		
		//Cocoa Application.tmproj
		$name = basename($location);
		
		if (copy_directory($template, $location)) {
			
			// Replace macros in files
			$this->replace_macros("$location/project.tmproj", array(self::MACRO_PROJECT => $name));
			
			// Rename project file to new name
			rename("$location/project.tmproj", "$location/$name.tmproj");
			
			//print("Successfully created the new project.\n");
			$this->open_project("$location/$name.tmproj");
		} else {
			print("There was an error copying the project template to the new location.\n");
		}
	}
	
	// Prompts the user to create a project	from an existing directory
	public function create_existing () {
		$dialog = new CocoaDialog($this->cocoa_dialog);
		if ($selection = $dialog->select_folder("Choose folder", "Choose existing folder to create new TextMate project from.", false, null)) {
			
			// Get the name of the existing project
			$project_name = basename($selection);
			
			// Get path to new project
			$project = "$selection/$project_name.tmproj";
			
			// Prevent duplicate project
			if (file_exists($project)) {
				die("There is already an existing TextMate project at this location.");
			}
			
			// Use the Empty template as our base
			$template = $this->standard_templates."/Empty";
			if (file_exists($template)) {
				
				// Make Targets directory
				@mkdir("$selection/Targets", 0777);
				
				// Copy default target
				copy("$template/Targets/development.xml", "$selection/Targets/development.xml");
				
				// Copy TextMate project file
				@copy("$template/project.tmproj", $project);
				$this->replace_macros($project, array(self::MACRO_PROJECT => $project_name));
				
				print("Successfully created the new project.\n");
				
				$this->open_project($project);
			} else {
				die("Can't find project template to start from!");
			}
		}
	}
	
	// Prompts the user to create a new project	
	public function create_new () {
		$dialog = new CocoaDialog($this->cocoa_dialog);
		$templates = array();
		
		// Find items in standard project templates
		$contents = directory_contents($this->standard_templates);
		foreach ($contents as $path) {
			if ($path != $this->standard_templates) {
				$items[] = basename($path);
				$templates[basename($path)] = $path;
			}
		}
		
		// Find items in user project templates
		$contents = directory_contents($this->user_templates);
		foreach ($contents as $path) {
			if ($path != $this->user_templates) {
				$items[] = basename($path);
				$templates[basename($path)] = $path;
			}
		}

		// Prompt to select template
		if ($selection = $dialog->standard_dropdown("New Project", "Choose a project template to start from.", $items, false, false, false, false)) {
			//print($selection);
			
			// Prompt to save
			if ($path = $dialog->save_file("Save Project", "Choose a location to save the new project.", null, null, null)) {
				$this->copy_template($templates[$selection], $path);
			}
		}
	}
				
	function __construct ($cocoa_dialog_path, $template_path) {
		$this->standard_templates = $template_path;
		$this->cocoa_dialog = $cocoa_dialog_path;
	}
	
}

//$project = new ProjectManager("/Applications/TextMate.app/Contents/SharedSupport/Support/bin/CocoaDialog.app", "/Users/ryanjoseph/Library/Application Support/TextMate/Bundles/Pascal.tmbundle/Support/Project Templates");
//$project->create_new();
//$project->create_existing();

?>