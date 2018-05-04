<?php
session_start();

function errHandle($errNo, $errStr, $errFile, $errLine) {
    $msg = "$errStr in $errFile on line $errLine";
    header("HTTP/1.1 500 Internal Server Error");
    throw new ErrorException($msg, $errNo);
}

set_error_handler('errHandle');

$path = get_include_path();
set_include_path(__DIR__);

// Parser
require('Parser.php');

// API
require('API.php');
require('ParserAPI.php');

// Parser dependencies
require('RandomColor.php');
require('SemanticItem.php');
require('SyntaxItem.php');
require('Concept.php');
require('ClosureGenerator.php');
require('Relationship.php');
require('RelationshipAttribute.php');
require('Relationships.php');
require('SNOMEDSearch.php');

// Database connection
require('MySQL.php');

set_include_path($path);