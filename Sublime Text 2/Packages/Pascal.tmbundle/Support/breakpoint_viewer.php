<html>
<head>
	<!-- Common styles -->
	<link href="file://<?php print($_ENV['TM_BUNDLE_SUPPORT']); ?>/css/main.css" type="text/css" rel="stylesheet" media="screen"/>
	
	<style type="text/css">

	td {
		max-height: 20px;
		padding-top: 2px;
		padding-bottom: 2px;
		padding-left: 8px;
		padding-right: 2px;
		background-color: #ecf3fd;
	}

	table {
		border-spacing: 1px 1px;
		overflow: auto;
		overflow-x:hidden;
		width: 100%;
		padding: 0px;
		margin: 0px;
		font-size: 8pt;
	}
	</style>
	
	<script type="text/javascript">

	window.resizeTo(300, 400);

	</script>
	
</head>
<body>

<?php

require_once($_ENV['TM_BUNDLE_SUPPORT']."/breakpoints.php");
require_once($_ENV['TM_BUNDLE_SUPPORT']."/common.php");

$breakpoints = new Breakpoints();
$breakpoints->load_target($_ENV["TM_PROJECT_DIRECTORY"], $_ENV["TARGET"]);
if ($results = $breakpoints->find()) {
	
	print("<p>Breakpoints in ".ucwords($_ENV["TARGET"])."</p>\n");
	
	$count = 0;

	print("<table>");
	print("<tr><td width=\"30px\"><b>#</b></td><td><b>File</b></td><td width=\"30px\"><b>Line</b></td></tr>\n");

	foreach ($results as $file => $array) {
		foreach ($array as $line) {
			$file_name = basename($file);
			$count++;
			print("<tr><td>$count</td><td><a href=\"txmt://open/?url=file://$file&line=$line\">$file_name</a></td><td><a href=\"txmt://open/?url=file://$file&line=$line\">$line</a></td></tr>\n");
		}
	}

	print("</table>");
} else {
	print("<p>There are no defined break points in the project.</p>");
}

?>

</body>
</html>
