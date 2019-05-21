<?php
echo <<<E
    <link rel="stylesheet" href="styles.css">
E;
require_once 'login.php';
$connection = new mysqli($hn, $un, $pw, $db);

if ($connection->connect_error) 
{
    die($connection->connect_error);
}
if (isset($_POST['username']) && isset($_POST['password']) && $_POST['username']!="" && $_POST['password']!="") 
{
    $un_temp = mysql_entities_fix_string($connection, $_POST['username']);
    $pw_temp = mysql_entities_fix_string($connection, $_POST['password']);
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
                $result -> close();
                $connection -> close();
                die ("Your profile has been created, please <a href=loginForm.html>click here</a> to log in");
            }        
        }
        elseif($result->num_rows != 0 && $_POST['sign'] == "signup" && isset($_POST['email']))
        {
            die("Unfortunately there already exists a user with that username. <br />You will need to pick a different username. <br />Please try <a href='signup.html'>signing up</a> again");
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
                $_SESSION=array();
                $_SESSION['username'] = $un_temp;
                header("Location: http://localhost/CS174/FinalProj/selectCipher.php");
            }
        }

    }
    
}
echo "Invalid username/password/email combination.<br />Would you like to try <a href='loginForm.html'>logging in</a> again or <a href='signup.html'>sign up</a>?";
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