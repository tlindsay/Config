<?php 
	require_once($_ENV['TM_BUNDLE_SUPPORT']."/target_loader.php");
	require_once($_ENV['TM_BUNDLE_SUPPORT']."/common.php");
	
	$script_path = $_ENV['TM_BUNDLE_SUPPORT']."/settings.php";
	
	$input = script_input();
	
	// Load the current target
	$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
	
	// Save the input values to the target
	if ($GLOBALS["argv"][1] == "-save") {
		$target->set_value("compiler", $input["compiler"]);
		$target->set_value("program", $input["program"]);
		$target->set_value("output", $input["output"]);
		$target->set_value("bundle", $input["bundle"]);
		$target->set_value("binary", $input["binary"]);
		$target->set_value("resources", $input["resources"]);
		
		$target->set_checkbox_value("advanced_index_symbols", $input["advanced_index_symbols"]);
		$target->set_checkbox_value("advanced_resolve_paths_recursively", $input["advanced_resolve_paths_recursively"]);
		$target->set_checkbox_value("advanced_show_fpc_command", $input["advanced_show_fpc_command"]);
		
		$target->set_value("advanced_debugging", $input["advanced_debugging"]);

		$target->set_value("xcode_active_configuration", $input["xcode_active_configuration"]);
		$target->set_value("xcode_project", $input["xcode_project"]);
		
		$target->set_value_with_array("debugger_breakpoints", $input["debugger_breakpoints"]);

		$target->set_sdk(SDK_IPHONE_SIMULATOR, $input["sdk_iphone_simulator"]);
		$target->set_sdk(SDK_IPHONE_DEVICE, $input["sdk_iphone_device"]);
		$target->set_sdk(SDK_UNIVERSAL, $input["sdk_universal"]);

		$target->set_platform($input["platform"], true);

		$target->set_options($input["options"]);
		$target->set_paths($input["paths"]);
		$target->set_frameworks($input["frameworks"]);
		$target->set_symbols($input["symbols"]);

		if ($target->save()) {
			print("Saved target settings.");
		} else {
			print("There was an error saving target settings.");
		}

		//print_r($target->xml);
		//print_r($input);
		die();
	}
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<!-- Common Styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/gui.css" type="text/css" rel="stylesheet" media="screen"/>

<!-- Scripts -->
<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/form.js" type="text/javascript" charset="utf-8"></script>
<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/tabber/tabber.js" type="text/javascript"></script>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/tabber/styles.css" type="text/css" rel="stylesheet" media="screen"/>
<script src="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/popup_alert.js" type="text/javascript" charset="utf-8"></script>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/PopupAlert/styles.css" type="text/css" rel="stylesheet" />

<script type="text/javascript">
window.resizeTo(560, 545);
</script>

</head>
<body>
		
<!-- Toolbar -->	
<div class="toolbar">
	<p class="title"><?php print(ucwords($target->name)." Settings"); ?></p>
	<input type="submit" value="Save" class="toolbar-item button seperator" onclick="save('<?php echo $script_path; ?>');"/>
</div>

<div class="main-pane">
<form>
<table class="sectional">

<!-- General -->
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">General</span></td></tr>
<tr><td class="section spacer"></td></tr>

<tr><td valign="middle" class="section-sub">Compiler</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["compiler"]); ?>" name="compiler"></td></tr>
<tr><td></td><td class="note">Path to the FPC compiler.</td></tr>

<tr><td valign="middle" class="section-sub">Program</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["program"]); ?>" name="program"></td></tr>
<tr><td></td><td class="note">Path to main program file.</td></tr>

<tr><td valign="middle" class="section-sub">Output</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["output"]); ?>" name="output"></td></tr>
<tr><td></td><td class="note">Path to build directory used for output.</td></tr>

<tr><td valign="middle" class="section-sub">Bundle</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["bundle"]); ?>" name="bundle"></td></tr>
<tr><td></td><td class="note">Path of the application bundle (if applicable).</td></tr>

<tr><td valign="middle" class="section-sub">Binary</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["binary"]); ?>" name="binary"></td></tr>
<tr><td></td><td class="note">Directory inside bundle where the compiled binary is copied.</td></tr>

<tr><td valign="middle" class="section-sub">Resources</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["resources"]); ?>" name="resources"></td></tr>
<tr><td></td><td class="note">Path of the resources directory which resources will be copied from into the bundle.</td></tr>

<tr><td valign="middle" class="section-sub">Platform</td><td valign="middle">
	<select name="platform">
		<option value="ppc" <?php print($target->is_platform_enabled(PLATFORM_PPC)); ?>>PPC</option>
		<option value="i386" <?php print($target->is_platform_enabled(PLATFORM_INTEL)); ?>>i386</option>
		<option value="universal" <?php print($target->is_platform_enabled(PLATFORM_UNIVERSAL)); ?>>Universal</option>
		<option value="iphone_simulator" <?php print($target->is_platform_enabled(PLATFORM_IPHONE_SIMULATOR)); ?>>iOS Simulator</option>
		<option value="iphone_device" <?php print($target->is_platform_enabled(PLATFORM_IPHONE_DEVICE)); ?>>iOS Device</option>
	</select>
</td></tr>
<tr><td></td><td class="note">Compiler platform target.</td></tr>

<!-- SDK -->
<tr><td class="section spacer"></td></tr>
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">SDKs</span><br /><span class="section description">Platform specific developer SDK's.</span></td></tr>
<tr><td class="section spacer"></td></tr>

<tr><td valign="middle" class="section-sub">Universal</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["sdk_universal"]); ?>" name="sdk_universal"></td></tr>
<tr><td valign="middle" class="section-sub">iOS Simulator</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["sdk_iphone_simulator"]); ?>" name="sdk_iphone_simulator"></td></tr>
<tr><td valign="middle" class="section-sub">iOS Device</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["sdk_iphone_device"]); ?>" name="sdk_iphone_device"></td></tr>

<!-- iOS Device -->
<tr><td class="section spacer"></td></tr>
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">iOS Device</span><br /><span class="section description">Settings for running on iOS devices via Xcode.</span></td></tr>
<tr><td class="section spacer"></td></tr>

<!--
<tr><td valign="middle" class="section-sub">Code Signing Identity</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["xcode_code_signing_identity"]); ?>" name="xcode_code_signing_identity"></td></tr>
<tr><td></td><td class="note">Identity of the iOS developer used when code signing the bundle.</td></tr>
-->

<tr><td valign="middle" class="section-sub">Active Configuration</td><td valign="middle">
<select name="xcode_active_configuration">
	<option value="Debug" <?php print($target->fields["xcode_active_configuration"]); ?>>Debug</option>
	<option value="Release" <?php print($target->fields["xcode_active_configuration"]); ?>>Release</option>
</select>
</td></tr>
<tr><td></td><td class="note">Active configuration of the Xcode project.</td></tr>

<tr><td valign="middle" class="section-sub">Xcode Project</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["xcode_project"]); ?>" name="xcode_project"></td></tr>
<tr><td></td><td class="note">Path to the Xcode helper project within the project directory.</td></tr>

<!-- Debugger -->
<tr><td class="section spacer"></td></tr>
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">Debugger</span></td></tr>
<tr><td class="section spacer"></td></tr>

<tr><td valign="middle" class="section-sub">FPC Options</td><td valign="middle"><input type="text" size="45" value="<?php print($target->fields["advanced_debugging"]); ?>" name="advanced_debugging"></td></tr>
<tr><td></td><td class="note">Command line options passed to FPC when compiling for debugging.</td></tr>

<tr><td valign="top" class="section-sub">GDB Options</td><td valign="middle"><textarea cols="45" rows="6" name="debugger_breakpoints"><?php print($target->fields["debugger_breakpoints"]); ?></textarea></td></tr>
<tr><td></td><td class="note">Command line options passed to GDB.</td></tr>

<!-- Advanced -->
<tr><td class="section spacer"></td></tr>
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">Advanced</span></td></tr>
<tr><td class="section spacer"></td></tr>

<tr><td valign="middle" class="section-sub">Index symbols</td><td valign="middle"><input type="checkbox" <?php print($target->get_checkbox_value("advanced_index_symbols")); ?> name="advanced_index_symbols"></td></tr>
<tr><td valign="middle" class="section-sub">Resolve paths recursively</td><td valign="middle"><input type="checkbox" <?php print($target->get_checkbox_value("advanced_resolve_paths_recursively")); ?> name="advanced_resolve_paths_recursively"></td></tr>
<tr><td valign="middle" class="section-sub">Show FPC command</td><td valign="middle"><input type="checkbox" <?php print($target->get_checkbox_value("advanced_show_fpc_command")); ?> name="advanced_show_fpc_command"></td></tr>

<!-- Paths & Options -->
<tr><td class="section spacer"></td></tr>
<tr><td valign="middle" colspan="2" class="section header"><span class="section header">Paths & Options</span></td></tr>
<tr><td class="section spacer"></td></tr>

<tr><td valign="top" class="section" colspan="2">
	
<div class="tabber">
	<div class="tabbertab">
		<h2>Paths</h2>
		<textarea cols="65" rows="10" name="paths"><?php print($target->fields["paths"]); ?></textarea>
		<p class="note">User defined source file paths.</p>
	</div>

	<div class="tabbertab">
		<h2>Frameworks</h2>
		<textarea cols="65" rows="10" name="frameworks"><?php print($target->fields["frameworks"]); ?></textarea>
		<p class="note">User defined framework paths.</p>
	</div>

	<div class="tabbertab">
		<h2>Options</h2>
		<textarea cols="65" rows="10" name="options"><?php print($target->fields["options"]); ?></textarea>
		<p class="note">Direct command line options to FPC.</p>
	</div>
	
	<div class="tabbertab">
		<h2>Symbols</h2>
		<textarea cols="65" rows="10" name="symbols"><?php print($target->fields["symbols"]); ?></textarea>
		<p class="note">Paths to search when indexing symbols.</p>
	</div>
</div>	
</td></tr>

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
