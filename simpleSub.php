<?php
session_start();
echo <<<Begin
<link rel="stylesheet" href="styles.css">
    <a href = "selectCipher.php">Main Page</a>
    <br />
    Hello, welcome to the <strong>Substitution Cipher</strong> Page!!<br />
    There are a few rules you have to follow for this page:<br />
    <ul>
        <li> The file needs to be in txt format, and can only contain LETTERS. No numbers, or special characters (spaces are ok)</li><br/>
        <li> If you want to <strong>encrypt</strong> the information in a file </li>
        <ol>
            <li> (Optional) Set a starting keystring (can be any word, ONLY LETTERS)</li>
            <ul>
            <li>If you are ok with using the same key to encrypt all your files, then do not enter any value in the keystring textfield after your first keystring submission</li>
            <li>If you want to use a new key, then enter a value in the keystring field. However, keep in mind that we will <strong>UPDATE</strong> our key database for your username. So if you encrypted a file previously, and you want to encrypt another file with a <strong>NEW</strong> keystring, then the decrypt the previous file first, because the old key will be lost forever when you submit a keystring value.</li>
            </ul>
            <li> Select 'Encrypt' from the drop down </li>
            <li> Choose the txt file you want to encrypt </li>
        </ol>
        <br />
        <li> If you want to <strong>decrypt</strong> the information in a file </li>
        <ol>
            <li><strong>YOU NEED TO ENTER THE SAME KEYSTRING YOU USED TO ENCRYPT YOUR FILE</strong></li>
            <ul>
                <li>We double check the keystring value you entered with the hash we created when you first encrypted your file. If they are the same, then we can decrypt your file</li>
            </ul>
            <li> The special requirement to use the decryption function is that you need to have atleast <strong>encrypted 1 file</strong> in order to have your key stored in the database. Otherwise, we will not be able to decrypt your file</li>
            <li> Select 'Decrypt' from the drop down </li>
            <li> Choose the txt file you want to decrypt </li>
        </ol>
    </ul>
    Hit Submit and VIOLA! The file is either encrypted or decrypted according to your drop down selection. Remember to copy and save the output in a text file.<br/>
Begin;
$file="";
require_once 'login.php';
$un_temp = $_SESSION['username'];
$conn = new mysqli($hn, $un, $pw, $db);
if(isset($_FILES['filename']) && isset($_POST["selectPart"]) && $_POST["selectPart"] == "encrypt" && $_FILES['filename']['type'] == "text/plain")
{
    $myfile = fopen($_FILES['filename']['tmp_name'], "r") or die("Unable to open file!");
    $file = fread($myfile,filesize($_FILES['filename']['tmp_name']));
    $file = strtolower($file);
    if(isset($_POST['input']))
        $str = $_POST['input'];
    else
        $str = "";
    $str = strtolower(mysql_entities_fix_string($conn, $str));
    $letters = getKey($str);
    $query = "SELECT * from cipher where userN = '$un_temp' and cipherU = 'substitutionCipher'";
    $result = $conn->query($query);
    
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $token = hash('ripemd128', "$salt1$str$salt2");
    if($result->num_rows == 0)
    {
        $insert = "INSERT INTO cipher (userN, cipherU, keyVal, start) VALUES ('$un_temp', 'substitutionCipher', '$letters', '$token')";
        $conn -> query($insert);
    }
    else if($str!="")
    {
        $update = "UPDATE cipher
                    SET keyVal = '$letters', start = '$token' WHERE userN = '$un_temp' AND cipherU = 'substitutionCipher'";
        $conn -> query($update);
        $conn->commit();
        echo "Your key in our database has been changed, if you encrypted files previously, the old key has been destroyed.<br/>";
    }
    else
    {
        for($i = 0; $i < $result->num_rows; ++$i)
        {
            $result -> data_seek($i);
            $r = $result->fetch_array(MYSQLI_ASSOC);
            $letters = $r['keyVal'];
            $result -> close();
        }
    }
    cipherMe($file, $letters);
    $result -> close();
    $conn -> close();
    destroyArrays();
}
else if(isset($_FILES['filename']) && isset($_POST["selectPart"]) && $_POST["selectPart"] == "decrypt")
{
    $myfile = fopen($_FILES['filename']['tmp_name'], "r") or die("Unable to open file!");
    $file = fread($myfile,filesize($_FILES['filename']['tmp_name']));
    $file = strtolower($file);
    $file = preg_replace('/[^a-z]+/i', ' ', $file);
    
    $query = "Select keyVal, start from cipher where userN = '$un_temp' AND cipherU = 'substitutionCipher'";
    $result = $conn -> query($query);
    $rows = $result->num_rows;
    for($i = 0; $i < $result->num_rows; ++$i)
    {
        $result -> data_seek($i);
        $r = $result->fetch_array(MYSQLI_ASSOC);
        $letters = $r['keyVal'];
        $start = $r['start'];
    }
    if(isset($_POST['input']))
        $str = $_POST['input'];
    else
        $str = "";
    $str = strtolower(mysql_entities_fix_string($conn, $str));
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $token = hash('ripemd128', "$salt1$str$salt2");
    if($token == $start)
        decipherMe($file, $letters);
    else
        echo "<script type='text/javascript'>alert('Your input keystrings did not match, please enter the right key string in order to have your input file be decrypted properly');</script>";
    $result -> close();
    $conn -> close();
    destroyArrays();
}
else if(isset($_FILES['filename']) || isset($_POST["selectPart"]) || isset($_POST['input']))
{
    echo <<<_issue
        Please read the instructions carefully and follow them. </br>
        Some of the most common mistakes are:
        <ul>
            <li>You may not have submitted a txt file</li>
            <li>You may not have selected a drop down option</li>
            <li>You may have tried to decrypt a file, but your key has not been created in our database. So please encrypt a file first for us to create your key and store it in our database</li>
            <li>If you were trying to decrypt, enter the starting keystring correctly</li>
        </ul>
_issue;
}
function getKey($str)
{
    $letters = "abcdefghijklmnopqrstuvwxyz";
    $result = implode(array_unique(str_split($str)),'');
    foreach(str_split($result) as $v)
    {
        $letters = str_replace($v,'',$letters);
    }
    while(strlen($letters) > 0)
    {
        $num = mt_rand(0, strlen($letters)-1);
        $result .= $letters{$num};
        $letters = str_replace($letters{$num},'',$letters);
    }
    return $result;
}
function decipherMe($file, $key)
{
    $res = "";
    echo "The text you passed in was:<br /><strong>$file</strong><br /><br />With the key stored in our database, the decipherd text is:";
    for($i = 0; $i < strlen($file); $i++)
    {
        if($file{$i}!=' ')
        {
            $ch = strpos($key, $file{$i}); 
            $res .= chr($ch+97);
        }
        else
            $res .= ' ';
    }
    echo "<br /><strong>$res</strong><br /><br />If the text is garbled, you either used a different key to encrypt the plain text or submitted the wrong text file to be decrypted. <br/>To fix it, encrypt your plain text with a new key, then save the encrypted text in a txt file, and then submit it to be decrypted";
}


function cipherMe($file, $result)
{
    $file = preg_replace('/[^a-z]+/i', ' ', $file); 
    echo "<br />Your input file was:<br/><strong>$file</strong><br /><br />";
    $res = "";
    for($i = 0; $i < strlen($file); $i++)
    {
        //$file = str_replace($result{$i-97}, chr($i), $file);
        if($file{$i}!=' ')
        {
            $ch = ord($file{$i})-97;
            $res .= $result{$ch};
        }
        else
            $res .= ' ';
    }
    echo "Your encrypted file is:<br/><strong>$res</strong><br/> <strong><br/>Please remember to copy and save it someplace.</strong><br/><br/>";
}

function destroyArrays()
{
    $_POST = array();
    $_FILES = array();
}

function mysql_entities_fix_string($conn, $string)
{
    return htmlentities(mysql_fix_string($conn, $string));
}

function mysql_fix_string($conn, $string)
{
    if (get_magic_quotes_gpc()) {
        $string = stripslashes($string);
    }
    return mysqli_real_escape_string($conn, $string);
}

echo <<<_END
<br/><br/>
        <html>
            <head>
                <title>Form Test</title>
            </head>
            <body>
                <form 
                    method = "post" 
                    action ="simpleSub.php"
                    enctype='multipart/form-data'
                >
                    What is the key string you want to start off with?
                    <br />
                    <input 
                        type = "text" 
                        name = "input"
                        text = null
                    >
                    <br/>
                    <select name = "selectPart">
                        <option>--- Select to either Encrypt/Decrypt ---</option>
                        <option value="encrypt">Encrypt</option>
                        <option value="decrypt">Decrypt</option>
                    </select>
                    <br/>
                    Select File: 
                    <input type='file' name='filename' size='10'>
                    <br /><br />
                    <input type = "submit">
                </form>
            </body>
        </html>
_END;
?>