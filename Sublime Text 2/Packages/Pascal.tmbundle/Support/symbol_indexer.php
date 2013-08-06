<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>

<!-- Common Styles -->
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/gui.css" type="text/css" rel="stylesheet" media="screen"/>

</head>
<body>

<div class="toolbar">
	<p class="title">Symbol Indexer</p>
</div>

<div class="main-pane">
<?php
require_once("symbol_parser.php");
require_once("target_loader.php");

$target = new TargetLoader($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
$parser = new SymbolParser($target->get_symbols_directory(), $target->get_resolved_symbols());

$parser->print_messages = true;
$parser->print_html = true;

print("<div style=\"padding: 20px; font-size: 7pt; font-family: monospace;\">");

$parser->parse(false);
?>
</div>

</body>
</html>
