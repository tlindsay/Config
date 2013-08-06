<?php 
	require_once("preferences.php");
	require_once("common.php");
	
	$script_path = $_ENV['TM_BUNDLE_SUPPORT']."/preference_editor.php";
	
	// Save the preferences
	$input = script_input();
	if ($GLOBALS["argv"][1] == "-save") {

		foreach ($input as $key => $value) {
			$preferences->set_value($key, $value);
		}
		
		if ($preferences->save()) {
			print("Saved preferences.");
		} else {
			print("Failed to save preferences.");
		}
		die;
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<!-- Common Styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/gui.css" type="text/css" rel="stylesheet" media="screen"/>

<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/form.js" type="text/javascript" charset="utf-8"></script>
<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/popup_alert.js" type="text/javascript" charset="utf-8"></script>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/styles.css" type="text/css" rel="stylesheet" />

<script type="text/javascript">
window.resizeTo(540, 545);
</script>

</head>
<body>

<!-- Toolbar -->	
<div class="toolbar">
	<p class="title">Global Preferences</p>
	<input class="toolbar-item button seperator" type="submit" value="Save" onclick="save('<?php echo $script_path; ?>');"/>
</div>

<div class="main-pane">
<form>
<table class="sectional">
<?
	$sections = 0;
	
	foreach ($preferences->get_all() as $entry) {
			
		if ($entry->kind == PREFERENCE_SECTION) {
			
			if ($sections > 0) print("<tr><td class=\"section spacer\"></td></tr>\n");
			
			if ($entry->description) {
				print("<tr><td valign=\"middle\" colspan=\"2\" class=\"section header\"><span class=\"section header\">$entry->name</span><br /><span class=\"section description\">$entry->description</span></td></tr>\n");
			} else {
				print("<tr><td valign=\"middle\" colspan=\"2\" class=\"section header\"><span class=\"section header\">$entry->name</span></td></tr>\n");
			}
			
			print("<tr><td class=\"section spacer\"></td></tr>\n");
			$sections ++;
			
			continue;
		}

		if ($entry->kind == PREFERENCE_FIELD) {
			print("<tr><td valign=\"middle\" class=\"section-sub\">$entry->name</td><td><input type=\"text\" size=\"45\" value=\"$entry->value\" name=\"$entry->key\"></td></tr>\n");
		}
		
		if ($entry->kind == PREFERENCE_CHECKBOX) {
			if ($entry->value == "on") {
				$selected = "checked=\"checked\"";
			} else {
				$selected = "";
			}
			print("<tr><td valign=\"middle\" class=\"section-sub\">$entry->name</td><td><input type=\"checkbox\" $selected name=\"$entry->key\"></td></tr>\n");
		}
		
		if ($entry->kind == PREFERENCE_TEXTBOX) {
			print("<tr><td valign=\"top\" class=\"section-sub\">$entry->name</td><td><textarea cols=\"44\" rows=\"10\" name=\"$entry->key\">$entry->value</textarea></td></tr>\n");
		}
		
		if ($entry->kind == PREFERENCE_LIST) {
			print("<tr><td valign=\"middle\" class=\"section-sub\">$entry->name</td><td><select name=\"$entry->key\">\n");

			foreach ($entry->values->value as $value) {
				$index += 1;
				
				if ($index == $entry->value) {
					print("<option value=\"$index\" selected=\"selected\">$value</option>");
				} else {
					print("<option value=\"$index\">$value</option>");
					
				}
			}
			
			print("</select></td></tr>\n");
			
		}
		
		// Descriptions
		if ($entry->description) {
			print("<tr><td></td><td class=\"note\">$entry->description</td></tr>\n");
		}
	}
?>	

<tr><td class="section footer"></td></tr>

</table>	
</form>	
</div>

<!-- PopupAlert -->	
<div id="alert-background">
	<div id="alert-box" onclick="hide_alert();">
		<p id="alert-message"></p>
	</div>
</div>

</body>
</html>