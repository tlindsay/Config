/*
  Simple popup alert for WebKit by Ryan Joseph
*/

function center_div (div) { 
	 div.style.position = 'absolute'; 
	 div.style.left = ((window.innerWidth / 2) - (div.offsetWidth / 2)) + 'px';  
	 div.style.top = ((window.innerHeight / 2) - (div.offsetHeight / 2)) + 'px'; 
} 

function show_alert (text) {
	var box = document.getElementById('alert-box');
	var background = document.getElementById('alert-background');
	var message = document.getElementById('alert-message');
	
	message.innerHTML = text;
	
	box.style.visibility = 'visible';
	background.style.visibility = 'visible';
	message.style.visibility = 'visible';
	
	center_div(box);
}

function hide_alert (message) {
	var box = document.getElementById('alert-box');
	var background = document.getElementById('alert-background');
	var message = document.getElementById('alert-message');

	box.style.visibility = 'hidden';
	background.style.visibility = 'hidden';
	message.style.visibility = 'hidden';
}