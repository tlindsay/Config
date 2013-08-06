<?php

// /Developer/Platforms/iPhoneOS.platform/Developer/Documentation/DocSets/com.apple.adc.documentation.AppleiOS4_2.iOSLibrary.docset

// /Library/Developer/Documentation/DocSets/com.apple.adc.documentation.AppleSnowLeopard.CoreReference.docset

///Developer/Xcode 3/Documentation/DocSets/com.apple.adc.documentation.AppleLegacy.CoreReference.docset

// Parses a book.json file for methods
function parse_book ($parent, $child, $book, &$data) {
	
	// Load the root child
	if ((!$parent) && (!$child)) {
		print("Parsing ".basename(dirname($book))."<br/>\n");
		
		$data = array();
		$data["methods"] = array();
		
		$child = json_decode(file_get_contents($book), true);
		
		// Get the book title
		$data["title"] = $child["title"];
		
		// The first section href which is usually "Overview" or "Class at a glance"
		$data["overview"] = $child["sections"][0]["href"];

	}
	
	// Iterate entires
	$entry = array();
	foreach ($child as $key => $value) {
		
		// Build entry
		$entry[$key] = $value;
		
		// Sections to find methods/functions
		$sections = array("Functions", "Instance Methods", "Class Methods");

		// append book
		if (in_array($parent["title"], $sections)) {
			
			$method["name"] = $value["title"];
			$method["url"] = "file://".dirname($book)."/".$value["href"];
			
			// Convert Objective-C selectors to Objective Pascal format (without trailing _)
			$method["name"] = str_replace(":", "_", $method["name"]);
			$method["name"] = rtrim($method["name"], "_");
			
			$data["methods"][] = $method;
		}
		
		if (is_array($value)) {
			parse_book($entry, $value, $book, $data);
		}
	}
}

// Parses the entire docset for all books
function parse_docset ($directory, &$books) {
	
	if (!$books) $books = array();
	
	if ($handle = @opendir($directory)) {
		while (($file = readdir($handle)) !== false) {
			if (($file != '.') && ($file != '..') && ($file[0] != '.')) {
				$path = "$directory/$file";
				
				// Add entry
				if ($file == "book.json") {
					
					// Parse the book
					parse_book(null, null, $path, $data);
					
					$entry["path"] = $path;										// Full file path of the book
					$entry["methods"] = $data["methods"];			// Methods
					$entry["name"] = $data["title"];					// Title of the book
					$entry["overview"] = $data["overview"];		// Overview href (append to path)

					$books[] = $entry;
				}
				
				// Recurse directory
				if (is_dir($path)) parse_docset($path, $books);
			}
		}
		closedir($handle);
	}
}

// Queries a json book (decoded into array) for a symbol name and returns the docset file URL 
function query_book ($query, $book) {
	$results = array();
	
	foreach ($book as $entry) {
		
		// Search book title
		if (preg_match("/$query/i", $entry["name"])) {
			$result = array();
			$result["name"] = $entry["name"];
			$result["url"] = "file://".dirname($entry["path"])."/".$entry["overview"];
			
			$results[] = $result;
		}
		
		// Search methods
		foreach ($entry["methods"] as $key => $value) {
			
			if (preg_match("/$query/i", $value["name"])) {
				
				$result = array();
				$result["name"] = $value["name"];
				$result["url"] = $value["url"];
				$result["reference_title"] = $entry["name"];
				$result["reference_url"] = "file://".dirname($entry["path"])."/".$entry["overview"];

				$results[] = $result;
			}

		}
	}
	
	return $results;
}

/*
$GLOBALS["argv"][] = "-index";
$GLOBALS["argv"][] = "-docset=\"/Library/Developer/Documentation/DocSets/com.apple.adc.documentation.AppleSnowLeopard.CoreReference.docset\"";
$GLOBALS["argv"][] = "-out=\"/Users/ryanjoseph/Desktop/dump.json\"";
//$GLOBALS["argv"][] = "-query=\"APPlicationShouldHandleReopen:hasVisibleWindows:\"";
*/

$input = script_input();

//print_r($input);die();

$docset_root = "/Contents/Resources/Documents/documentation";

// Missing docset
if (!file_exists($input["docset"])) {
	die("Fatal: The docset ".basename($input["docset"])." can not be found.");
}

// Missing index, build the index first
if (!file_exists($input["out"])) {
	print("Building the docset <i>".basename($input["docset"])."</i> index first to <code>".$input["out"]."</code>...<hr>\n");
	$input["index"] = true;
}

// Index the docset
if ($input["index"]) {
	parse_docset($input["docset"].$docset_root, $books);
	
	if (count($books) > 0) {
		file_put_contents($input["out"], json_encode($books));
		print("Saved output.<br />\n");
	} else {
		die("The docset didn't contain any books. Please make sure it has been installed properly from Xcode.<br />\n");
	}
}

// Query the index
if ($input["query"]) {
	$book = json_decode(file_get_contents($input["out"]), true);
	$results = query_book($input["query"], $book);
	
	if ($results) {
		
		// Present a list of available symbols
		if (count($results) > 1) {
			
			print("Documentation for <i>".$input["query"]."</i> returned ".count($results)." results.");
			print("<ol style=\"font-size: 10pt\">");
			foreach ($results as $result) {
				
				$docset = $input["docset"];
				$url = $result["url"];
				$name = $result["name"];
				
				// Convert the docset url to a link
				$link_name = $result["reference_title"];
				$link_url = $result["reference_url"];

				// Print the link
				if ($link_name) {
					print("<li><a href=\"$url\"><b>$name</b></a> in <a href=\"$link_url\">$link_name</a></li>");
				} else {
					print("<li><a href=\"$url\"><b>$name</b></a></li>");
				}
			}
			print("</ol>");
			
		} else { // Open the only result
			$url = $results[0]["url"];
			print("<meta http-equiv='Refresh' content='0;URL=$url'>");
		}
	} else {
		print("Documentation for the symbol <i>".$input["query"]."</i> can not be found.");
	}
}

?>