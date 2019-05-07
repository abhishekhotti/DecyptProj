<?php
require_once '../login.php';
$connection = new mysqli($hn, $un, $pw, $db);

if(isset($_POST['username']) && isset($_POST['password']))
{
    if($_POST['username']!="" && $_POST['password']!="")
    {
        $_SERVER['PHP_AUTH_USER'] = $_POST['username'];
        $_SERVER['PHP_AUTH_PW'] = $_POST['password'];
    }
}

if ($connection->connect_error) 
{
    die($connection->connect_error);
}
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) 
{
    $un_temp = mysql_entities_fix_string($connection, $_SERVER['PHP_AUTH_USER']);
    $pw_temp = mysql_entities_fix_string($connection, $_SERVER['PHP_AUTH_PW']);
    $query = "SELECT * FROM users WHERE username='$un_temp'";
    $result = $connection->query($query); 
    if (!$result) 
    {
        die($connection->error);
    }
    elseif(isset($_POST['sign']))
    {
        if($result->num_rows === 0 && $_POST['sign'] == "signup" && isset($_POST['email']))
        {
            if($_POST['email']!="")
            {
                $email = mysql_entities_fix_string($connection, $_POST['email']);
                $salt1 = "qm&h*"; 
                $salt2 = "pg!@";
                $token = hash('ripemd128', "$salt1$pw_temp$salt2");
                $query = "INSERT INTO users (username, password, email) VALUES ('$un_temp','$token', '$email')";
                $connection->query($query);
                die ("Your profile has been created, please <a href=loginForm.php>click here</a> to log in");
            }        
        }
        elseif($result->num_rows && $_POST['sign'] == "signin")
        {
            $row = $result->fetch_array(MYSQLI_NUM);
            $result->close();
            $salt1 = "qm&h*"; 
            $salt2 = "pg!@";
            $token = hash('ripemd128', "$salt1$pw_temp$salt2");
            if ($token == $row[1]) 
            {
                session_start();
                $_SESSION['username'] = $un_temp;
                $_SESSION['email'] = $row[2];
                echo "Hi $row[0], you are now logged in";
                die ("<p><a href=continue.php>Click here to continue</a></p>");
            }
        }
    }
}

echo "Invalid username/password/email combination.<br />Would you like to try <a href='loginForm.php'>logging in</a> again or <a href='signup.html'>sign up</a>?";
$connection -> close();

function mysql_entities_fix_string($connection, $string)
{
    return htmlentities(mysql_fix_string($connection, $string));
}
 function mysql_fix_string($connection, $string)
 {
     if (get_magic_quotes_gpc()) {
         $string = stripslashes($string);
     }
     return $connection->real_escape_string($string);
 }
?>