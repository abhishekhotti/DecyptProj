<?php
require_once "../login.php";
echo"<br />";
echo <<<Begin
<link rel="stylesheet" href="styles.css">
Hello, welcome to our <strong>Decryptiod Project</strong> Home Page!!<br />
We have implemented 4 common encryption algorithms:<br />
<ul>
    <li> <a href = "simpleSub.php"> Simple Substitution </a> </li>
    <li> <a href = "doubleTrans.php"> Double Transposition </a> </li>
    <li>RC4</li>
    <li>DES</li>
</ul>
Click on one the links to see it work on your plain text, or convert your cipher text into plain text
<br/>
<br/>
Begin;
createDatabases($hn, $un, $pw, $db);
function createDatabases($hn, $un, $pw, $db)
{
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error)
            die($conn->connect_error);
    $result = $conn->query("SHOW TABLES LIKE 'cipher'");
    if($result->num_rows === 0){
         $cipher = "CREATE TABLE cipher (
                id int AUTO_INCREMENT KEY,
                userN char(64),
                cipherU varchar(64),
                time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                keyVal varchar(64),
                start varchar(64)
            )";
        $conn->query($cipher);    
    }
}
?>
