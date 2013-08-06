<?php 

require_once("common.php");

/**
 * Class for displaying common GUI elements (in HTML)
 */

class GUI {
	
	
	/**
	 * Extra CSS styles
	 */
	public $table_row_style = "";
	
	/**
	 * Private
	 */
	private $table_row_index;
	
	/**
	 * Tables
	 */
		
	public function table_open ($style) {
		if ($style) {
			print("<table class=\"table-view\" style=\"$style\">\n");
		} else {
			print("<table class=\"table-view\">\n");
		}
		$this->table_row_index = 0;
	}
	
	public function table_close () {
		print("</table>\n");
	}
	
	public function table_add_row ($text, $optional_style) {		
		if ($this->table_row_index&1) {
			$style = "table-view-row-odd";
		} else {
			$style = "table-view-row";
		}
		
		print("<tr><td class=\"$style\" style=\"$this->table_row_style; $optional_style\">$text</td></tr>\n");
		
		$this->table_row_index += 1;
	}
	
	public function table_add_row_link ($href, $content, $optional_style) {
		$line = "<a class=\"table-view-row\" href=\"$href\">$content</a>";
		$this->table_add_row($link, $optional_style);
	}
	
	public function table_add_header ($text) {
		print("<tr><td class=\"table-view-row-header\" style=\"$this->table_row_style\">$text</td></tr>\n");
		$this->table_row_index += 1;
	}

	/**
	 * Messages
	 */
	// This message will fill the entire main pane and is meant to be the only error	
	public function print_error ($message, $fatal) {
		if ($fatal) {
			die("<p class=\"error\">Invalid query.</p>\n");
		} else {
			print("<p class=\"error\">Invalid query.</p>\n");
		}
	}	
		
	// Constructor
	function __construct() {
	}
}

// Always load a GUI object when the file is required
$gui = new GUI();

?>