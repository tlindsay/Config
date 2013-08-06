<?php

/**
 * Clears comments from the selection
 */

// Read input from TextMate
$scope = file_get_contents('php://stdin');

// Line breaks
$scope = trim($scope, " 	\n");

// Multi line comments
$scope = trim($scope, "{}");

// Single line comments
$scope = ltrim($scope, "/");

// (* *) style comments
$scope = trim($scope, "(*)");

// White space
$scope = trim($scope, " 	");

print($scope);

?>