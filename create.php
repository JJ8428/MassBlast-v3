<?php
    $create_account_message = '';

    # Create account function
    if (isset($_POST['create_account']))
    {
        if (strcmp($_POST['email1'], $_POST['email2']) == 0 && strcmp($_POST['password1'], $_POST['password2']) == 0)
        {
            $account_exists = FALSE;
            $read = fopen('users/userInfo.csv', 'r');
            while (!feof($read))
            {
                $line = fgets($read);
                $cred = $_POST['email1'] . ',' . $_POST['password1'];
                if (strcasecmp($cred, $line) == 0 || strcasecmp($cred, $line) == 1)
                {
                    $account_exists = TRUE;
                    fclose($read);
                    break;
                }
            }
            if ($account_exists)
            {
                $create_account_message = ' An account with that email already exists.';
            }
            else
            {
                # Make an account and record credentials if account does not exist
                $write = fopen('users/userInfo.csv', 'a');
                fwrite($write, "\n" . $_POST['email1'] . ',' . $_POST['password1']);
                fclose($write);
                $create_account_message = ' <b>Account has been successfully created.</b>';
                mkdir('users/dirs/' . $_POST['email1'], 0777);
                mkdir('users/dirs/' . $_POST['email1'] . '/files', 0777);
                mkdir('users/dirs/' . $_POST['email1'] . '/results', 0777);
                mkdir('users/dirs/' . $_POST['email1'] . '/tmp', 0777);
                mkdir('users/dirs/' . $_POST['email1'] . '/view', 0777);
                mkdir('users/dirs/' . $_POST['email1'] . '/zip', 0777);
                header('Location: successfulCreated.html');
                exit();
            }
        }
        else
        {
            $create_account_message = ' Email or Password entries do not match.';
        }
    }
?>
<html>
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <title>PHTA-v2</title>
        <link rel="stylesheet" type="text/css" href="style.css">
    </head>
    <style>
        body
        {
            background-color: black;
        	font:14px/1.4 'Arial', 'Helvetica', sans-serif;
        }
        #body
        {
            background-color: white;
            padding: 0;
        }
        #body1
        {
            background-color: #C70039;
            color: white;
        }
        #body1 b
        {
            color: white;
        }
        .page
        {
            color: white;
            font-weight: bold;
            border-style: solid;
            background-color: #C70029;
            height: 30px;
            border-color: #C70039;
        }
        .page:hover
        {
            background-color: #73000a;
        }
        h3, b, hr
        {
            color: #73000a;
        }
    </style>
    <body>
        <div id="body">
            <h1 style="display:inline; font-weight:normal">
                <b>PH</b>enotype <b>T</b>ranscriptomic <b>A</b>ssociation <b>Calculator</b>
            </h1>
            <img id="img2" src="img/nsf-logo.png" height="59px" width="59px" style="display: inline; float:right">
            <img src="img/usc.jpeg" height="59px" width="59px" style="display: inline; float:right">
            </br>
            Work of Dr. Homayoun Valafar, Dr. Hippokratis Kiaris, Youwen Zhang, Naga Venkata Sai Jagjit (JJ) Satti
        </div>
        <div id="body">
            <form action="" method="post" enctype="multipart/form-data">
                <h3>Create Account</h3><hr>
                Email: <input type="email" name="email1"><br><br>
                Confirm Email: <input type="email" name="email2" require><br><br>
                Password: <input type="text" name="password1" require><br><br>
                Confirm Password: <input type="text" name="password2" require><br><br>
                <input type="submit" name="create_account" value="Create Account" Required>
                <?php
                    echo $create_account_message;
                ?>
            </form>
            <a href="index.php">Click here</a> to login.
        </div>
    </body>
</html>