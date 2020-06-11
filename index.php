<?php
    session_start();

    # Session variables
    $_SESSION['whoami']; // String
    $_SESSION['logged']; // Boolean
    $_SESSION['activedir']; // String
    $_SESSION['step1']; // Integer
    $_SESSION['step2']; // Integer
    $_SESSION['step3']; // Integer
    $_SESSION['page']; // Integer
    $_SESSION['analysisSession']; // Boolean

    # Login function
    if (isset($_POST['login']))
    {
        $creds = $_POST['email'] . ',' . $_POST['password'];
        $read = fopen('users/userInfo.csv', 'r');
        $found = FALSE;
        while (!feof($read))
        {
            $line = fgets($read);
            if (strcmp($line, $creds) == 1 || strcmp($line, $creds) == 0)
            {
                $_SESSION['logged'] = TRUE;
                $_SESSION['whoami'] = $_POST['email'];
                $_COOKIE['whoami'] = $_POST['email'];
                $_SESSION['step1'] = 0;
                $_SESSION['step2'] = 0;
                $found = TRUE;
                $_SESSION['page'] = 1;
            }
        }
    }

    # Logout function
    if (isset($_POST['logout']))
    {
        $_SESSION['logged'] = FALSE;
        $_SESSION['whoami'] = null;
        $_SESSION['page'] = -1;
        $_SESSION['step1'] = -1;
        $_SESSION['step2'] = -1;
        session_destroy();
    }

    # Paging function
    if (isset($_POST['page1']))
    {
        $_SESSION['page'] = 1;
        $_SESSION['step1'] = 0;
        $_SESSION['step2'] = 0;
    }
    if (isset($_POST['page2']))
    {
        $_SESSION['page'] = 2;
        $_SESSION['step1'] = 0;
        $_SESSION['step2'] = 0;
    }
    if (isset($_POST['page3']))
    {
        $_SESSION['page'] = 3;
        $_SESSION['step1'] = 0;
        $_SESSION['step2'] = 0;
    }
    if (isset($_POST['page4']))
    {
        $_SESSION['page'] = 4;
        $_SESSION['step1'] = 0;
        $_SESSION['step2'] = 0;
    }

    # Upload function
    if (isset($_POST['upload']))
    {
        $target_dir = 'users/dirs/' . $_SESSION['whoami'] . '/files/' . $_FILES['fileToUpload']['name'];
        move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_dir);
        shell_exec('dos2unix ' . $target_dir);
    }

    # Delete function
    if (isset($_POST['delete']))
    {
        foreach ($_POST['filetoDel'] as $file)
        {
            unlink($file);
        }
    }

    # Restart Form
    if (isset($_POST['restart']))
    {
        $_SESSION['step1'] = 0;
        $_SESSION['calcMessage1'] = NULL;
    }

    # MassBlast Calculation Step 1
    if (isset($_POST['nextStep']))
    {
        if ($_POST['focusFile'] == NULL || $_POST['compareWith'] == NULL)
        {
            $_SESSION['calcMessage1'] = '<b>Please select all parameters.</b><br><br>';
        }
        else
        {
            $_SESSION['focusFile'] = $_POST['focusFile'];
            $_SESSION['compareWith'] = $_POST['compareWith'];
            $_SESSION['step1'] = 1;
            $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/files/' . $_SESSION['focusFile'], 'r');
            $tmp = 0;
            while (!feof($read))
            {
                $line = fgets($read);
                if (strpos($line, '>') !== false)
                {
                    $tmp += 1;
                }
            }
            $_SESSION['pepCount'] = $tmp;
            $_SESSION['step1'] = 1;
            $_SESSION['calcMessage1'] = NULL;
        }
    }

    # MassBlast Calculation Step 2
    if (isset($_POST['nextStep2']))
    {
        if ($_POST['lowerBound'] >= $_POST['higherBound'] || $_POST['lowerBound'] == NULL || $_POST['higherBound'] == NULL)
        {
            $_SESSION['calcMessage1'] = '<b>The indices entered were invalid. Please make sure the starting indices is less than the ending indices.</b><br><br>';
        }
        else
        {
            $_SESSION['lowerBound'] = $_POST['lowerBound'];
            $_SESSION['higherBound'] = $_POST['higherBound'];
            $_SESSION['step1'] = 2;
            $_SESSION['calcMessage1'] = NULL;
        }
    }

    # MassBlast Calculation Step 3
    if (isset($_POST['nextStep3']))
    {
        if ($_POST['filterBy'] == NULL)
        {
            $_SESSION['calcMessage1'] = "<b>Please fill all parameters.</b><br><br>";
        }
        else
        {
            $_SESSION['filterBy'] = $_POST['filterBy'];
            $_SESSION['step1'] = 3;
            $_SESSION['calcMessage1'] = NULL;
        }
    }

    # MassBlast Calculation Step 4
    if (isset($_POST['nextStep4']))
    {
        if($_POST['lowFilter'] >= $_POST['highFilter'] && ($_SESSION['filterBy'] != "None"))
        {
            $_SESSION['calcMessage1'] = '<b>The bounds entered are invalid. Please make sure the lower bound is less than the upper bound.</b><br><br>';
            echo $_POST['highFilter'] . ' || ' . $_POST['lowFilter'];
        }
        else
        {
            //$_SESSION['saveas'] = $_POST['saveas'];
            //$_SESSION['lowFilter'] = $_POST['lowFilter'];
            //$_SESSION['highFilter'] = $_POST['highFilter'];
            $_SESSION['step1'] = 4;
            $_SESSION['calcMessage1'] = NULL;
            $write = fopen('users/dirs/' . $_SESSION['whoami'] . '/tmp/request', 'w');
            fwrite($write, $_SESSION['whoami'] . "\n" . $_SESSION['focusFile'] . "\n");
            for ($x = 0; $x < sizeof($_SESSION['compareWith']); $x++)
            {
                fwrite($write, $_SESSION['compareWith'][$x] . "\n");
            }
            fwrite($write, $_SESSION['lowerBound'] . "||" . $_SESSION['higherBound'] . "\n" . $_SESSION['filterBy'] . "\n" . $_POST['lowFilter'] . '||' . $_POST['highFilter'] . "\n" . $_POST['saveas']);
            fclose($write);
            $write = fopen('activeDir', 'w');
            fwrite($write, $_SESSION['whoami']);
            fclose($write);
            shell_exec('env/bin/python src/MassBlast.py > output &');
        }    
    }

    # Delete Commits
    if (isset($_POST['deleteCommit']))
    {
            unlink($_POST['toDel']);
    }

    # Data to be unzipped
    if (isset($_POST['view']))
    {
        $write2 = fopen('activeDir', 'w');
        fwrite($write2, $_SESSION['whoami']);
        fclose($write2);
        $_SESSION['zipOI'] = $_POST['zipfile'];
        $_SESSION['zipFile'] = '<a href="' . $_POST['zipfile'] . '" Download>Click here</a>' . ' to download data and plots of <b>' . explode('/', $_SESSION['zipOI'])[sizeof(explode('/', $_SESSION['zipOI'])) - 1] . '</b>';
        $_SESSION['step2'] = 1;
        echo shell_exec('env/bin/python src/Extract.py ' . $_SESSION['zipOI'] . ' ' . 'users/dirs/' . $_SESSION['whoami'] . '/view 2>&1');
        $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/hm-score.html', 'r');
        $table = '';
        while (!feof($read))
        {
            $table .= fgets($read);
        }
        $_SESSION['iframe']  = $table;
        $compared = [];
        $indices = '';
        $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/request.txt', 'r');
        fgets($read);
        fgets($read);
        while (!feof($read))
        {
            $line = explode("\n", fgets($read))[0];
            if (strpos($line, '.faa'))
            {
                array_push($compared, $line);
                continue;
            }
            else
            {
                $indices = explode('||', $line);
                break;
            }
        }
        $_SESSION['indicesIB'] = $indices;
        $_SESSION['comparedWithIB'] = $compared;
        $_SESSION['toPrint'] = '';
    }

    # iFrame's are swapped out with HTML due to iFrame unable to update in relation to fileIO
    # Provide iFrame of heatmap
    if (isset($_POST['hm-score']))
    {
        $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/hm-score.html', 'r');
        $table = '';
        while (!feof($read))
        {
            $table .= fgets($read);
        }
        $_SESSION['iframe']  = $table;
    }
    if (isset($_POST['hm-id']))
    {
        $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/hm-id.html', 'r');
        $table = '';
        while (!feof($read))
        {
            $table .= fgets($read);
        }
        $_SESSION['iframe']  = $table;
    }

    # Indvidual Blast Results
    if (isset($_POST['indvBlast']))
    {
        $_SESSION['warningIB'] = '';
        if ($_POST['peptideIndex'] > intval($_SESSION['indicesIB'][1]) || $_POST['peptideIndex'] < intval($_SESSION['indicesIB'][0]))
        {
            $_SESSION['warningIB'] = '<b>Please enter a valid Peptide Index between ' . $_SESSION['indicesIB'][0] . '-' . $_SESSION['indicesIB'][1] . '</b><br><br>';
        }
        else
        {
            $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/request.txt', 'r');
            fgets($read);
            fgets($read);
            $index = 0;
            while (!feof($read))
            {
                $line = fgets($read);
                $index++;
                if (trim($_POST['compareWith']) == trim($line))
                {
                    break;
                }
            }
            fclose($read);
            echo $index;
            $read = fopen('users/dirs/' . $_SESSION['whoami'] . '/view/allMB.txt', 'r');
            fgets($read);
            $toPrint = '';
            $allMB = '';
            while (!feof($read))
            {
                $allMB .= fgets($read) . '<br>';
            }
            $allMB = explode('===---===---===', $allMB);
            $allMB = $allMB[$index - 1];
            $allMB = explode('Query=', $allMB);
            $toPrint = $allMB[$_POST['peptideIndex']];
            $_SESSION['toPrint'] = '<h3>Individual Blast Report:</h3><hr>' . $toPrint;
        }
    }

    # Leave Feedback
    if (isset($_POST['leaveFB']))
    {
        $write = fopen('feedback', 'a');
        $line = '=====' . "\n" . $_SESSION['whoami'] . "\n" . $_POST['feedback'] . "\n" . '=====' . "\n";
        fwrite($write, $line);
        fclose($write);
    }
?>
<html>
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <title>MB-v3</title>
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
        .img
        {
            margin: 20px;
        }
        .iframe
        {
            overflow: hidden;
            overflow-x: auto;
        }
    </style>
    <body>
        <div id="body">
            <h1 style="display:inline; font-weight:normal">
                <b>MassBlast</b>
            </h1>
            <img id="img2" src="img/nsf-logo.png" height="59px" width="59px" style="display: inline; float:right">
            <img src="img/usc.jpeg" height="59px" width="59px" style="display: inline; float:right">
            </br>
            Work of Dr. Homayoun Valafar, Dr. Traci L. Testerman, JJ Satti
        </div>
        <div id="body">
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Login menu
                    if (!$_SESSION['logged'])
                    {
                        echo '<h3>Login:</h3><hr>' . 
                        'Email: <input type="email" name="email" Required><br><br>' .
                        'Password: <input type="text" name="password" Required><br><br>' . 
                        '<input type="submit" name="login" value="Login"><br><br>';
                        echo '<a href="create.php">Click here</a> to create an account.';
                    }
                ?>
            </form>
        </div>
        <div id="body1">
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Navigation tools
                    if ($_SESSION['logged'])
                    {
                        echo '<b>Profile:</b> ' . $_SESSION['whoami'] .
                        '<input type="submit" name="logout" class="page" value="Logout">' . 
                        '<input type="submit" name="page1" class="page" value="Manage Files">' . 
                        '<input type="submit" name="page2" class="page" value="Analyze">' . 
                        '<input type="submit" name="page3" class="page" value="View Results">' . 
                        '<input type="submit" name="page4" class="page" value="Leave Feedback">';
                    }
                ?>
            </form>
        </div>
        <div id="body">
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Upload menu
                    if ($_SESSION['logged'] && $_SESSION['page'] == 1)
                    {
                        echo '<h3>Upload:</h3><hr>' .
                        '<input type="file" name="fileToUpload"><br><br>' .
                        '<input type="submit" name="upload" value="Upload">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="mutlipart/form-data">
                <?php
                    # Delete menu
                    if ($_SESSION['logged'] && $_SESSION['page'] == 1)
                    {
                        echo '<h3>Delete:</h3><hr>' .
                        'Select the file(s) to delete:<br><br>' .
                        '<select name="filetoDel[]" multiple="multiple">';
                        $files = scandir('users/dirs/' . $_SESSION['whoami'] . '/files');
                        for ($x = 2; $x < sizeof($files); $x++)
                        {
                            echo '<option value="' .  'users/dirs/' . $_SESSION['whoami'] . '/files/' . $files[$x] . '">' . $files[$x] . '</option>';
                        }
                        echo '</select><br><br>' . 
                        '<input type="submit" name="delete" value="Delete">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="mutlipart/form-data">
                <?php
                    # Inventory
                    if ($_SESSION['logged'] && $_SESSION['page'] == 1)
                    {
                        echo '<h3>Inventory:</h3><hr>' . 
                        '<table border="1">' . 
                        '<tr><th>File:</th><th>Date Uploaded:</th><tr>';
                        $files = scandir('users/dirs/' . $_SESSION['whoami'] . '/files');
                        for ($x = 2; $x < sizeof($files); $x++)
                        {
                            echo '<tr><td>' . $files[$x] . '</td><td>' . date("d F Y", filemtime('users/dirs/' . $_SESSION['whoami'] . '/files/' . $files[$x])) . '</td></tr>';
                        } 
                        echo '</table>';
                    }
                ?>
            </form>
        </div>
        <div id="body">
            <?php
                if ($_SESSION['logged'] && $_SESSION['page'] == 2)
                {
                    echo '<h3>Generate Data:</h3><hr>';
                }
            ?>
            <form aciton="" method="post" enctype="mutlipart/form-data">
                <?php
                    # Restart Form
                    if ($_SESSION['logged'] && $_SESSION['step1'] != 0 && $_SESSION['page'] == 2)
                    {
                        echo '<input type="submit" name="restart" value="Restart Form">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # MassBlast Calculation Step 1
                    if ($_SESSION['logged'] && $_SESSION['step1'] == 0 && $_SESSION['page'] == 2)
                    {
                        echo $_SESSION['calcMessage1'];
                        echo 'Please select the fasta file containing the data of focus:<br><br>' . 
                        '<select name="focusFile">';
                        $file = scandir('users/dirs/' . $_SESSION['whoami'] . '/files');
                        for ($x = 2; $x < sizeof($file); $x++)
                        {
                            echo '<option value="' . $file[$x] . '">' . $file[$x] . '</option>';
                        }
                        echo '</select><br><br>' . 
                        'Please select the fasta file to compare the file of focus with:<br><br>' . 
                        '<div style="overflow:scroll; width:15%; height:100px; background-color:#cfcfcf">';
                        for ($x = 2; $x < sizeof($file); $x++)
                        {
                            echo '<input type="checkbox" name="compareWith[]" value="' . $file[$x] . '">' . $file[$x] . '<br>';
                        }
                        echo '</div><br>' . 
                        '<input type="submit" name="nextStep" value="Submit">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # MassBlast Calculation Step 2
                    if ($_SESSION['logged'] && $_SESSION['step1'] == 1 && $_SESSION['page'] == 2)
                    {
                        echo $_SESSION['calcMessage1'];
                        echo 'The selected focus file contains: <b>' . $_SESSION['pepCount'] . '</b> peptide sequences. Enter the range of peptide sequences to analyze.<br><br>' .  
                        'Start: <input type="number" min="1" max="' . $_SESSION['pepCount'] . '" name="lowerBound" required><br><br>' . 
                        'End: <input type="number" min="1" max="' . $_SESSION['pepCount'] . '" name="higherBound" required><br><br>' .
                        'Please note the range is inclusive.<br><br>' .  
                        '<input type="submit" name="nextStep2" value="Submit">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # MassBlast Calculation Step 3
                    if ($_SESSION['logged'] && $_SESSION['step1'] == 2 && $_SESSION['page'] == 2)
                    {
                        echo $_SESSION['calcMessage1'];
                        echo 'Please select how the data should be filtered:<br><br>' . 
                        '<input type="radio" name="filterBy" value="None">None<br>' . 
                        '<input type="radio" name="filterBy" value="Score">Score<br>' . 
                        '<input type="radio" name="filterBy" value="ID">ID<br>' .
                        '<input type="radio" name="filterBy" value="Positive">Positive<br>' .
                        '<input type="radio" name="filterBy" value="Gap">Gap<br><br>' .  
                        '<input type="submit" name="nextStep3" value="Submit">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # MassBlast Calculation Step 4
                    if ($_SESSION['logged'] && $_SESSION['step1'] == 3 && $_SESSION['page'] == 2)
                    {
                        echo $_SESSION['calcMessage1'];
                        if ($_SESSION['filterBy'] != "None")
                        {
                            echo 'Please enter the <b>' . $_SESSION['filterBy'] . '</b> values MassBlast must filter between:<br><br>' . 
                            'Lower Bound:<input type="number" name="lowFilter" min="0" step=".01"><br><br>' . 
                            'Upper Bound:<input type="number" name="highFilter" min="0" step=".01"><br><br>';
                        }
                        echo 'Save the data as:<br><br>' . 
                        '<input type="text" name="saveas" required>.zip<br><br>' . 
                        '<input type="submit" name="nextStep4" value="Submit">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # MassBlast waiting for request
                    if ($_SESSION['logged'] && $_SESSION['step1'] == 4 && $_SESSION['page'] == 2)
                    {
                        echo 'MassBlast is processing your request. Please wait until your data appears in the results tab before submitting another request.<br><br>' . 
                        'It will appear in the View Results tab as an option when completed.';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Delete MassBlast commits
                    if ($_SESSION['logged'] && $_SESSION['page'] == 2)
                    {
                        echo '<h3>Delete Data:</h3><hr>' . 
                        'Select the data to delete:<br><br>' . 
                        '<select name="toDel">';
                        $files = scandir('users/dirs/' . $_SESSION['whoami'] . '/zip');
                        for ($x = 2; $x < sizeof($files); $x++)
                        {
                            echo '<option value="' .  'users/dirs/' . $_SESSION['whoami'] . '/zip/' . $files[$x] . '">' . $files[$x] . '</option>';
                        }
                        echo '</select><br><br>' . 
                        '<input type="submit" name="deleteCommit" value="Submit">';
                    }
                ?>
            </form>
        </div>
        <div id="body">
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Select the zip file to analyze
                    if ($_SESSION['logged'] && $_SESSION['page'] == 3)
                    {
                        echo '<h3>View Data:</h3><hr>' . 
                        'Select the save to view:<br><br>' . 
                        '<select name="zipfile">';
                        $files = scandir('users/dirs/' . $_SESSION['whoami'] . '/zip');
                        for ($x = 2; $x < sizeof($files); $x++)
                        {
                            echo '<option value="' .  'users/dirs/' . $_SESSION['whoami'] . '/zip/' . $files[$x] . '">' . $files[$x] . '</option>';
                        }
                        echo '</select><br><br>' . 
                        '<input type="submit" name="view" value="View Data">';
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Give the option to choose plots
                    if ($_SESSION['logged'] && $_SESSION['step2'] == 1 && $_SESSION['page'] == 3)
                    {
                        echo '<h3>Select Data:</h3><hr>' . 
                        'Select the unit for the heatmap to display: ' . 
                        '<input type="submit" name="hm-score" value="Score">' . 
                        '<input type="submit"" name="hm-id" value="ID"><br><br>';
                        echo '<div class="iframe" style="border:1 solid black;">' . $_SESSION['iframe'] . '</div><br>' . 
                        $_SESSION['zipFile'];
                    }
                ?>
            </form>
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Individual Blast Menu
                    if ($_SESSION['logged'] && $_SESSION['step2'] == 1 && $_SESSION['page'] == 3)
                    {
                        echo '<h3>Individual Blast:</h3><hr>' . 
                        $_SESSION['warningIB'] . 
                        'Please enter the following parameters to view BLAST results of individual peptide sequences.<br><br>' . 
                        'Peptide Index:<input type="number" min="' . $_SESSION['indicesIB'][0] . '" max="' . $_SESSION['indicesIB'][1] . '" step="1" name="peptideIndex"><br><br>' . 
                        'Compared With:' . 
                        '<select name="compareWith">';
                        for ($a = 0; $a < sizeof($_SESSION['comparedWithIB']); $a++)
                        {
                            echo '<option value="' . $_SESSION['comparedWithIB'][$a] . '">' . $_SESSION['comparedWithIB'][$a] . '</option>';
                        }
                        echo '</select>' . '<br><br>' . 
                        '<input type="submit" name="indvBlast">';
                    }
                ?>
            </form>
            <?php
                # Print out Indvidual Blast
                if ($_SESSION['logged'] && $_SESSION['step2'] == 1 && $_SESSION['page'] == 3)
                {
                    echo $_SESSION['toPrint'];
                }
            ?>
        </div>
        <div id="body">
            <form action="" method="post" enctype="multipart/form-data">
                <?php
                    # Allow for feedback 
                    if ($_SESSION['logged'] && $_SESSION['page'] == 4)
                    {
                        echo '<h3>Leave Feedback:</h3><hr>' . 
                        'Please leave any comments regarding any improvements to MassBlast or errors encountered while using MassBlast:<br><br>' .
                        '<textarea name="feedback" rows="5" cols="50" Required></textarea><br><br>' .
                        '<input type="submit" name="leaveFB" value="Submit">';
                    }
                ?>
            </form>
        </div>
    </body>
</html>