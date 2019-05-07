<?php
echo <<<Begin
<link rel="stylesheet" href="styles.css">
Hello, welcome to the <strong>Transposition Cipher</strong> Page!!<br />
There are a few rules you have to follow for this page:<br />
<ul>
    <li> The file needs to be in txt format, and can only contain LETTERS. No numbers, or special characters (spaces are ok)</li><br/>
    <li> If you want to <strong>encrypt</strong> the information in a file </li>
    <ol>
        <li> You <strong>need</strong> to set a starting key (can be any word, ONLY LETTERS)</li>
        <li> Select 'Encrypt' from the drop down </li>
        <li> Choose the txt file you want to encrypt </li>
    </ol>
    <br />
    <li> If you want to <strong>decrypt</strong> the information in a file </li>
    <ol>
        <li> The special requirement to use the decryption function is that you need to have atleast <strong>encrypted 1 file</strong> in order to have your key stored in the database. Otherwise, we will not be able to decrypt your file</li>
        <li> Select 'Decrypt' from the drop down </li>
        <li> Choose the txt file you want to decrypt </li>
    </ol>
</ul>
Hit Submit and VIOLA! The file is either encrypted or decrypted according to your drop down selection. Remember to copy and save the output in a text file.<br/>
<br/>
Begin;
$file="";
//echo print_r($_FILES)."<br/><br/>";
//echo print_r($_POST);

if(isset($_FILES['filename']) && isset($_POST['input']) && isset($_POST['input2']) && isset($_POST["selectPart"]) && $_FILES['filename']['type'] == "text/plain")
{
    require_once '../login.php';
    $conn = new mysqli($hn, $un, $pw, $db);
    $cypt = $_POST["selectPart"];
    $myfile = fopen($_FILES['filename']['tmp_name'], "r") or die("Unable to open file!");
    $file = fread($myfile,filesize($_FILES['filename']['tmp_name']));
    if($cypt === "encrypt")
        cipherMe($file, $conn, $un);
    else if($cypt === "decrypt" && (trim($_POST['input'])!="") || (trim($_POST['input2'])!=""))
        decipherMe($file, $conn, $un);
    else
        echo "Please enter two keystring values used during encryption inorder to decrypt the double transposed cipher text properly! We need to make sure you know the keys<br/><br/>";
    destoryArraysEver();
}
function cipherMe($file, $conn, $un)
{
    $key = strtolower(mysql_entities_fix_string($conn, $_POST['input']));
    $key = str_replace(' ','',$key);
    $key2 = strtolower(mysql_entities_fix_string($conn, $_POST['input2']));
    $key2 = str_replace(' ','',$key2);
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $token = hash('ripemd128', "$salt1$key$key2$salt2");
    $query = "SELECT * from cipher where userN = '$un' and cipherU = 'doubleTrans'";
    $result = $conn->query($query);
    if($result->num_rows == 0)
    {
        $insert = "INSERT INTO cipher (userN, cipherU, keyVal, start) VALUES ('$un', 'doubleTrans', '$key $key2', '$token')";
        $conn -> query($insert);
    }
    else if($key!="" && $key2!="")
    {
        $update = "UPDATE cipher
                    SET keyVal = '$key $key2', start = '$token' WHERE userN = '$un' AND cipherU = 'doubleTrans'";
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
            $letters = explode(' ',$r['keyVal']);
        }
        $key = $letters[0];
        $key2 = $letters[1];
    }
    echo "The plain text is: <br />".$file;
    $file = str_replace(' ', '!', $file);
    $arr = putInArray($file, $key);
    $firstTrans = transposeMe($arr, $key);
    //echo strlen($file).'<br />';
    $v = "";
    foreach ( $firstTrans as $var ) {
        foreach ($var as $x)
        {
          //  echo $x;
            $v.=$x;
        }
        //echo "<br/>";
    }
    $arr = putInArray($v, $key2);
    $secondTrans = transposeMe($arr, $key2);
    $finalStr = "";
    echo "<br>";
    foreach ( $secondTrans as $var ) {
        foreach ($var as $x){
       //     echo $x;
            $finalStr .= $x;
        }
     //   echo "<br/>";
    }
    //$finalStr = str_replace('-', ' ', $finalStr);
    echo "<br />The cipherText is:<br />".$finalStr."<br /><br />";
}

function destoryArraysEver()
{
    $_POST = array();
    $_FILES = array();
}

function transposeMe($inputArr, $str)
{
    $string = $str;
    $stringParts = str_split($string);
    sort($stringParts);
    $rows = count($inputArr);
    $firstTrans = array ();
    for($i = 0; $i < $rows; $i++)
        array_push($firstTrans, array());
    for($i = 0; $i < count($stringParts); $i++)
    {
        $posi = strpos($str,$stringParts[$i]);
        $from = '/'.preg_quote($stringParts[$i], '/').'/';
        $str = preg_replace($from,"#", $str, 1);
        for($j = 0; $j < $rows; $j++)
        {
            $firstTrans[$j][$i] = $inputArr[$j][$posi];
        }
    }
    return $firstTrans;
}

function decipherMe($file, $conn, $un)
{
    $query = "SELECT start from cipher where userN = '$un' and cipherU = 'doubleTrans'";
    $result = $conn->query($query);
    for($i = 0; $i < $result->num_rows; ++$i)
    {
        $result -> data_seek($i);
        $r = $result->fetch_array(MYSQLI_ASSOC);
        $compare = $r['start'];
    }
    $salt1 = "qm&h*"; 
    $salt2 = "pg!@";
    $key = strtolower(mysql_entities_fix_string($conn, $_POST['input']));
    $key2 = strtolower(mysql_entities_fix_string($conn, $_POST['input2']));
    $token = hash('ripemd128', "$salt1$key$key2$salt2");
    if($token !== $compare)
    {
        echo "The two keys you entered did not match our hash, please reenter the correct keys!! <br /><br/>";
        return;
    }
    echo "The cipherText is: <strong>$file</strong>";
    $arr = putInArray($file, $key2);
    $arr = transposeBack($arr, $key2);
    $v = "";
    foreach ($arr as $var) {
        foreach ($var as $x)
        {
           // echo $x;
            $v.=$x;
        }
    //    echo "<br />";
    }
    $arr = putInArray($v, $key);
    echo "<br />";
    $arr = transposeBack($arr, $key);
    echo "<br />The unciphered text is: <br />";
    $line = "";
    foreach ( $arr as $var ) {
        foreach ($var as $x)
        {
           // echo $x;
            $line.= $x;
        }
        //echo "<br />";
    }
    $line = str_replace('!', ' ', $line);
    $line = str_replace('-', ' ', $line);
    echo "<strong>$line</strong><br /><br />";
}

function transposeBack($inputArr, $str)
{
    $stringParts = str_split($str);
    sort($stringParts);
    $string = implode("",$stringParts);
    $rows = count($inputArr);
    $solvedArr = array();
    for($i = 0; $i < count($inputArr); $i++)
        array_push($solvedArr, array());
    for($i = 0; $i < strlen($str); $i++)
    {
        $ch = $str{$i};
        $findMe = strpos($string, $ch);
        $from = '/'.preg_quote($ch, '/').'/';
        $string = preg_replace($from,"#", $string, 1);
        for($v = 0; $v < count($inputArr); $v++)
            $solvedArr[$v][$i]=$inputArr[$v][$findMe];
    }
    return $solvedArr;
}

function putInArray($str, $key)
{
    $inputArr = array(
        array()
    );
    $row = 0;
    for($i = 0; $i < strlen($str); $i++)
    {
        array_push($inputArr[$row], $str{$i});
        if($i%strlen($key)==(strlen($key)-1) && $i!=0
          && (filesize($_FILES['filename']['tmp_name'])-1)!=$i)
        {
            array_push($inputArr, array());
            $row++; 
        }
    }
    while(count($inputArr[count($inputArr)-1]) < strlen($key))
    {
        array_push($inputArr[$row], "-");
    }
    return $inputArr;
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
        <html>
            <head>
                <title>Form Test</title>
            </head>
            <body>
                <form 
                    method = "post" 
                    action ="doubleTrans.php"
                    enctype='multipart/form-data'
                >
                    What is the first key you want to use?<br />
                    <input 
                        type = "text" 
                        name = "input"
                        text = null
                    >
                    <br/>
                    What is the second key you want to use?<br />
                    <input 
                        type = "text" 
                        name = "input2"
                        text = null
                    >
                    <br />
                    Do you want to Encrypt or Decrypt the file?<br />
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