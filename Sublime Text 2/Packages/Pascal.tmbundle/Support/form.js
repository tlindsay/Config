
// Form utilities for AJAX pages using the TextMate object

// Returns a string with all form keys=>values
function tm_form_options () {
	var form = new Array();
	
	var inputs = document.getElementsByTagName('input');
	for(var i=0;i<inputs.length;i++) form.push(inputs[i]);
	
	var textareas = document.getElementsByTagName('textarea');
	for(var i=0;i<textareas.length;i++) form.push(textareas[i]);
		
	var selects = document.getElementsByTagName('select');
	for(var i=0;i<selects.length;i++) form.push(selects[i]);
		
	var options = "";
	
	for(var i=0;i<form.length;i++) {
		if (form[i].name && form[i].value) options += form[i].name+"='"+form[i].value+"' ";
	}
	
	return options;
}

// Saves the window and displays an alert (from popup_alert.js)
function save (script) {
	
	var options = tm_form_options();
	
	// Make the PHP command to run this script
	var command = "/usr/bin/php \""+script+"\" -save "+options;

	// Execute the command using TextMate.system
	var output = TextMate.system(command, null).outputString;

	// Show popup alert (load popup_alert.js before calling this function!)
	show_alert(output);
}

var buffer = null;

function read_stdout (text) {
	buffer += text;
	
	// Appending the buffer directly to innerHTML had unexepected results so I can only assume
	// the text was not directly appended and followed some other HTML rules.
	document.getElementById('page-content').innerHTML = buffer;
}

function tm_terminated (object) {
	TextMate.isBusy = false;
	
	// invoke the user defined script terminated function
	// ??? how do we use callbacks in javascript?!
	script_terminated();
}

function show_pane (id) {
	var elem = document.getElementById(id);
	elem.style.display = 'block';
}

function hide_pane (id) {
	var elem = document.getElementById(id);
	elem.style.display = 'none';
}

function reload_on_return (event, script, terminated_callback) {
	if (event.keyCode == 13) reload(script, terminated_callback);
}

function reload (script, terminated_callback) {
	
	var content_element = document.getElementById('page-content');
	var options = tm_form_options();
	buffer = "";
	
	// Clear the content
	content_element.innerHTML = "";
	
	// Make the PHP command to run this script
	var command = "/usr/bin/php \""+script+"\" -reload "+options;

	// Execute the command using TextMate.system
	TextMate.isBusy = true;
	obj = TextMate.system(command, tm_terminated);
	obj.onreadoutput = read_stdout;
}