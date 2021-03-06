<!DOCTYPE html>
<html>
<head>
	<title>Login Page</title>
    <style>
    .signup {
        border:5px solid #999999; font: normal 14px helvetica; color: #763293; display: inline-block;
        }
    </style>
    <link rel="stylesheet" href="styles.css">
	<script>
	function validate(form) {
		fail = validateUsername(form.username.value)
		fail += validatePassword(form.password.value)
		fail += validateEmail(form.email.value)
		
		if (fail == "") return true
		else { alert(fail); return false }
	}
    function validateUsername(field)
    {
        val = "";
        if (field == "") 
            val += "No Username was entered.\n"
        if (field.length < 3)
            val += "Usernames must be at least 3 characters.\n"
        if (/[^a-zA-Z0-9_-]/.test(field))
            val += "Only a-z, A-Z, 0-9, - and _ allowed in Usernames.\n"
        return val
    }
        
    function validatePassword(field)
    {
        val=""
        if (field == "")
            val += "No Password was entered.\n"
        if (field.length < 3)
            val += "Passwords must be at least 3 characters.\n"
        if (!/[a-z]/.test(field) || ! /[A-Z]/.test(field) ||!/[0-9]/.test(field))
            val += "Passwords require one each of a-z, A-Z and 0-9.\n"
        return val
    }
    function validateEmail(field)
    {
        val = ""
        if (field == "") 
            val += "No Email was entered.\n"
        if (!((field.indexOf(".") > 0) && (field.indexOf("@") > 0)) || /[^a-zA-Z0-9.@_-]/.test(field))
            val += "The Email address is invalid.\n"
        return val
    }
	</script>
</head>
<body>
    <div class = "contain">
        <table class = "signup" border="0" cellpadding="2" cellspacing="5" bgcolor="#eeeeee">
            <th colspan="2" align="center">Login Form</th>
            <form method="post" action = "authenticate.php" onsubmit="return validate(this)">
                <tr><td>Username</td>
                    <td><input type="text" maxlength="16" name="username"></td></tr>
                <tr><td>Password</td>
                    <td><input type="password" maxlength="12" name="password"></td></tr>
                <input type = "hidden" value = "signin" name="sign" >
                <tr><td colspan="2" align="center"><input type="submit"
                    value="Signup"></td></tr>
            </form>
        </table>
    </div>
</body>
</html>
