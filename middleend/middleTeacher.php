<?php
    
    //Author: Aadarsh Patel
    //Layer: Middle
    //File: middleTeacher.php
    //Desc: check form field, redirects accordingly, returns server output
    
    $ch = curl_init();
    $JsonFromFront = $_POST['Message'];
    
    $jsonObj = json_decode($JsonFromFront);
    
    $formData = $jsonObj -> Form;
    
    if ($formData == "CreateQuestion")
    {
        $temp = urlencode($JsonFromFront);
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/insertQuestionDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$temp");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    elseif ($formData == "QuestionList")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/retrieveQuestionDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    elseif ($formData == "CreateExam")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/insertExamDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$JsonFromFront");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    elseif ($formData == "StudentList")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/retrieveSubmissionDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    elseif ($formData == "UpdateGrade")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/insertSubmissionDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$JsonFromFront&Alter=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    elseif ($formData == "ReleaseScore")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/insertSubmissionDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$JsonFromFront&Release=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }
    else {
        $server_output = "incorrect form data";
    }

    echo $server_output;
    curl_close($ch);

?>
