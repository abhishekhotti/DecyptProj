<?php // setupusers.php
require_once '../login.php';
$connection = new mysqli($hn, $un, $pw, $db);
if ($connection->connect_error) 
    die($connection->connect_error);
$query = "CREATE TABLE users(
    username VARCHAR(32) PRIMARY KEY NOT NULL,
    password VARCHAR(32) NOT NULL,
    email VARCHAR(64) NOT NULL,
    )";
$result = $connection->query($query);
    if (!$result) die(printMe());
function printMe()
{
    echo "Please continue on to <a href = 'loginForm.html'>loginForm</a>. Tables were just created<br />";
}

?>
