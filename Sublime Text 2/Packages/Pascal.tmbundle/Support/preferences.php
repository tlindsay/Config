<?php 

require_once("common.php");

// Preferences Kinds
define("PREFERENCE_FIELD", "field");
define("PREFERENCE_CHECKBOX", "checkbox");
define("PREFERENCE_TEXTBOX", "textbox");
define("PREFERENCE_LIST", "list");
define("PREFERENCE_SECTION", "section");		// Pseudo type which creates a section in the editor

class Preferences {
	
	// Private Variables
	private $preferences = array();
	private $path;
	private $xml;
	
	// Accessors
	public function get_all () {
		return $this->preferences;
	}
	
	public function get_kind ($key) {
		return $this->preferences[$key]->kind;
	}
		
	public function get_integer_value ($key) {
		return (int)$this->preferences[$key]->value;
	}
	
	public function get_string_value ($key) {
		return $this->preferences[$key]->value;
	}	
	
	public function get_array_value ($key, $delimiter) {
		if ($this->contains_key($key)) {
			return explode($delimiter, $this->get_string_value($key));
		} else {
			return null;
		}
	}	
	
	public function get_boolean_value ($key) {
		if ($this->get_string_value($key) == "on") {
			return true;
		} else {
			return false;
		}
	}
	
	public function contains_key ($key) {
		if (isset($this->preferences[$key])) {
			return true;
		} else {
			return false;
		}
	}
	
	private function add_value ($key, $value) {
	}
	
	// Sets a target value
	public function set_value ($key, $value) {
		
		if (is_array($value)) $value = implode($value, "\n");
		
		for ($i=0; $i < count($this->xml->pref); $i++) { 
			if ($this->xml->pref[$i]->key == $key) {
				$this->xml->pref[$i]->value = $value;
			}
		}
	}

	// Methods
	public function save () {
		if (file_put_contents($this->path, $this->xml->asXML())) {
			return true;
		} else {
			return false;
		}
	}
	
	// Converts a SimpleXML object to a key-value array
	private function xml_to_array ($xml) {
		$array = array();
		foreach ($xml->pref as $pref) {
			$array[(string)$pref->key] = $pref;
		}
		return $array;
	}
	
	// Constructor
	function __construct() {
		$support_path = expand_tilde_path("~/Library/tmpascal"); 
		@mkdir($support_path);
		$this->path = $support_path."/preferences.xml";
		
		// Copy the default preferences into the support folder
		if (!file_exists($this->path)) {
			copy($_ENV['TM_BUNDLE_SUPPORT']."/preferences.xml", $this->path);
		}
		
		// Load the XML into an array
		$this->xml = new SimpleXMLElement(file_get_contents($this->path));
		
		foreach ($this->xml as $node => $pref) {
			if ((string)$pref->kind == PREFERENCE_SECTION) {
				$key = strtolower((string)$pref->name);
				$key = str_replace(" ", "_",$key);
				
				$this->preferences[$key] = $pref;
			} else {
				$this->preferences[(string)$pref->key] = $pref;
			}
		}
		
		//print_r($this->preferences);
	}
}

// Always load a preference object when the file is required
$preferences = new Preferences();

?>