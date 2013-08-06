<?php 

/**
 * System Utilities
 */
function exec_async ($command) {
	//exec($command." > /dev/null &");
	
	pclose(popen($command." /dev/null &", "r"));
}

/**
 * TextMate Utilities
 */

// Selects the file and line number
function textmate_file ($path, $line) {
	exec($_ENV["TM_SUPPORT_PATH"]."/bin/mate \"".$path."\" -l ".$line);	
}

// Brings to the current file to front
function textmate_current_file () {
	exec($_ENV["TM_SUPPORT_PATH"]."/bin/mate \"".$_ENV["TM_FILEPATH"]."\"");
}

/**
 * String Utilities
 */ 

// Escapes quotes in a string so it can be passed safely to JavaScript
function javascript_escape ($str) {
	return preg_replace("/\r?\n/", "\\n", addslashes($str));
}

/**
 * File System Utilities
 */

// Returns the current logged in user directory (i.e. /Users/johndoe)
function user_directory () {
	$user = exec("whoami");
	return "/Users/$user";
}

// Expands the ~ in a path to the current users directory
function expand_tilde_path ($path) {
	return str_replace("~", user_directory(), $path);
}

// Removes the extension from a file name
function remove_file_extension ($file_name) {  
	$ext = strrchr($file_name, '.');  
	if($ext !== false) {  
		$file_name = substr($file_name, 0, -strlen($ext));  
	}  
	return $file_name;  
}

/**
 * Directory Utilities
 */
function directory_contents ($directory) {
	
	if (file_exists($directory)) {
		$directories[] = $directory;
	} else {
		$directories = array();
	}
	
	if ($handle = @opendir($directory)) {
		while (($file = readdir($handle)) !== false) {
			if (($file != '.') && ($file != '..') && ($file[0] != '.')) {
				$directories[] = "$directory/$file";
			}
		}
		closedir($handle);
	}
	
	return $directories;
}

function delete_directory($dirname) {
	if (is_dir($dirname)) 
	$dir_handle = opendir($dirname);
	if (!$dir_handle)
	return false;
	while($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file))
			unlink($dirname."/".$file);
			else
			delete_directory($dirname.'/'.$file); 
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	return true;
}

function copy_directory ($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                copy_directory($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
	return true;
} 

/**
 * Command line utilities
 */

// Returns script user input as array
function script_input () {
	$input = array();
	
	foreach($GLOBALS["argv"] as $key => $value) {
		$value = ltrim($value, "-");
		$pair = explode("=", $value);
		if (count($pair) == 2) {
			$pair[1] = trim($pair[1], "\"");
			$line_array = explode("\n", $pair[1]);
			if (count($line_array) > 1) {
				$input[$pair[0]] = $line_array;
			} else {
				$input[$pair[0]] = $pair[1];
			}
		} else { // Single values (i.e. -run)
			$input[$value] = true;
		}
	}
	
	return $input;
}

?>