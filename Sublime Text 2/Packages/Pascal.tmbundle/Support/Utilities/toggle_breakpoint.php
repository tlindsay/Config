<?php

/**
 * Adds/removes a breakpoint to the line
 */

// Read input from TextMate
$scope = file_get_contents('php://stdin');
$break_pattern = "/\/\/break$/i";
//$scope = "some line";

if (preg_match($break_pattern, $scope)) {
	echo preg_replace($break_pattern, "", $scope);
} else {
	$scope = trim($scope, "\n");
	$scope .= "//break";
	print($scope);
}

?>