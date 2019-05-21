<?php
require_once 'login.php';
echo "<link rel='stylesheet' href='styles.css'>";
session_start();
if(!isset($_SESSION['username']))
    die("You are not logged in. <br />Please <a href='loginForm.html'>Log in</a> or <a href='signup.html'>Sign up</a>?");
echo <<<Begin
    <a href = "selectCipher.php">Main Page</a>
    <br />
    Hello, welcome to the <strong>RC4 Cipher</strong> Page!!<br />
    There are a few rules you have to follow for this page:<br />
    <ul>
        <li> The file needs to be in txt format, and can only contain LETTERS. No numbers, or special characters (spaces are ok)</li><br/>
        <li> If you want to <strong>encrypt</strong> the information in a file </li>
        <ol>
            <li> Set a starting keystring (ONLY LETTERS)</li>
            <ul>
                <li>
                    If you are ok with using the same key to encrypt all your files, then do not enter any value in the keystring textfield after your first keystring submission
                </li>
                <li>
                 If you want to use a new key, then enter a value in the keystring field. However, keep in mind that we will <strong>UPDATE</strong> our key database for your username. So if you encrypted a file previously, and you want to encrypt another file with a <strong>NEW</strong> keystring, then the decrypt the previous file first, because the old key will be lost forever when you submit a keystring value.
                </li>
            </ul>
            <li> Select <strong>'Encrypt'</strong> from the drop down </li>
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
$un_temp = $_SESSION['username'];
$conn = new mysqli($hn, $un, $pw, $db);

function checkData($conn, $un_temp)
{
    if(isset($_POST['input']))
        $key = $_POST['input'];
    else
        $key = "";
    $key = strtolower(mysql_entities_fix_string($conn, $key));
    $query = "SELECT * from cipher where userN = '$un_temp' and cipherU = 'rc4'";
    $result = $conn->query($query);
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $token = hash('ripemd128', "$salt1$key$salt2");
    if($result->num_rows == 0)
    {
        $insert = "INSERT INTO cipher (userN, cipherU, keyVal, start) VALUES ('$un_temp', 'rc4', '$key', '$token')";
        $conn -> query($insert);
    }
    else if($key!="")
    {
        $update = "UPDATE cipher
                    SET keyVal = '$key', start = '$token' WHERE userN = '$un_temp' AND cipherU = 'rc4'";
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
            $key = $r['keyVal'];
        }
    }
    $result -> close();
    $conn -> close();
    return $key;
}

if(isset($_FILES['filename']) && isset($_POST["selectPart"]) && $_POST["selectPart"] == "encrypt" && $_FILES['filename']['type'] == "text/plain")
{
    $key = checkData($conn, $un_temp);
    $str = fopen($_FILES['filename']['tmp_name'], "r") or die("Unable to open file!");
    echo "<br/> The file you submitted contained this information:<br/>";
    $file = fread($str,filesize($_FILES['filename']['tmp_name']));
    echo "<strong>$file</strong><br/>";
    $cipher = rc4($key, $file);
    destroyArrays();
    echo "<br />After encrypting your file with the key of \"$key\". The cipher text is <br /><strong>$cipher</strong><br /><br />Please copy and save the ciphertext in a text file for decryption later on<br /><br />";
}
elseif(isset($_POST["input"]) && isset($_FILES['filename']) && isset($_POST["selectPart"]) && $_POST["selectPart"] == "decrypt" && $_FILES['filename']['type'] == "text/plain")
{
    $key = $_POST["input"];
    $keyVal = strtolower(mysql_entities_fix_string($conn, $key));
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $token = hash('ripemd128', "$salt1$keyVal$salt2");
    $query = "SELECT * from cipher where userN = '$un_temp' and cipherU = 'rc4'";
    $result = $conn->query($query);
    for($i = 0; $i < $result->num_rows; ++$i)
    {
        $result -> data_seek($i);
        $r = $result->fetch_array(MYSQLI_ASSOC);
        $key = $r['start'];
    }
    
    if($key==$token)
    {
        $str = fopen($_FILES['filename']['tmp_name'], "r") or die("Unable to open file!");
        $file = fread($str,filesize($_FILES['filename']['tmp_name']));
        echo "The file you submitted contained this information:<br/><strong>$file</strong><br/>";
        $plain = rc4($keyVal, $file);
        echo "<br />After running the decryption algorithm with the key of \"$keyVal\", the plaintext we got for your input file is:<br /><strong>$plain</strong><br />";
    }
    else{
        echo "<br/><strong>The keystring you entered did not match our hash you used during encryption, please try again.</strong><br/>";
    }
}
else{
    echo "You are probably seeing this message because you forgot to submit one of the following:
    <ul>
        <li> The key to be used </li>
        <li> Did not select a value from the dropdown </li>
        <li> Did not submit a text file </li>
    </ul>
    Please input <strong>ALL</strong> the required information and then hit submit to get a valid output! <br />";
}

function rc4($key, $str) {
  // Permutation array
	$s = array();
    $k = array();
	for ($i = 0; $i < 128; $i++) {
		$s[$i] = $i;
        $k[$i] = ord($key[$i % strlen($key)])%127;
	}
	$j = 0;
	for ($i = 0; $i < 128; $i++) {
		$j = ($j + $s[$i] + $k[$i]) % 127;
    // swap
		$temp = $s[$i];
		$s[$i] = $s[$j];
		$s[$j] = $temp;
	}
	$i = 0;
	$j = 0;
	$res = '';
    $a = 0;
  while ($a < strlen($str)) {
      $i = ($i + 1) % 127;
      $j = ($j + $s[$i]) % 127;
    // swap
      $temp = $s[$i];
      $s[$i] = $s[$j];
      $s[$j] = $temp;
      $t = ($s[$i] + $s[$j]) % 127;
      $keyStreamByte = $s[$t];
    // use keystream byte like a one-time pad
      //echo $keyStreamByte." ";
      $res .= $str[$a] ^ chr($keyStreamByte);
      $a += 1;
  }
	return $res;
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
<br/>
        <html>
            <head>
                <title>RC4</title>
            </head>
            <body>
                <form 
                    method = "post" 
                    action ="rc4.php"
                    enctype='multipart/form-data'
                >
                    What is the key string you would like to use?
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
                    <br />
                    <input type = "submit">
                </form>
            </body>
        </html>
_END;
?>