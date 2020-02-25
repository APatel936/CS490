<?php
    
    //Author: Aadarsh Patel
    //Layer: Middle
    //File: middleStudent.php
    //Desc: Checks database and checks python scripts, returns JSON
    
    ini_set('display_errors', 1); error_reporting(E_ALL);

    
    $ch = curl_init();
    $JsonFromFront = $_POST['Message'];

    $jsonObj = json_decode($JsonFromFront);

    $formData = $jsonObj -> Form;

    if ($formData == "ExamList")
    {
        curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/retrieveExamDB.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
    }

    elseif ($formData == "StudentSubmissions")
    {
        $creds = $jsonObj -> Student;
        $submitted = $jsonObj -> Submission;  #grab submissions field from json from front

        foreach($submitted as $el){   #for each submission element
            $masterComment = "";
            $AutoGraded = new stdClass;
            $functionID = $el ->ID; #gets function ID to query DB for more info later
                        
            $AutoGraded -> questionID=$functionID;
            $AutoGraded -> student=$creds;
 
            #getting test cases ----------------------------------------------------------------------------------------
            #below curl is used to grab the test cases/ answers for a given function
            curl_setopt($ch, CURLOPT_URL, "http://afsaccess2/~sc855/cs490/RC/retrieveQuestionDB.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$functionID");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $question_output = curl_exec($ch);
            
            $retrieveQuestionJson = json_decode($question_output);
            $rowArray = $retrieveQuestionJson -> rowArray;
            $inputArray = [];  //test cases  - to input into given function
            $outputArray = [];  //test case answers - should match to

            foreach($rowArray as $cases){
                $testInput = $cases -> testCase;
                array_push($inputArray, $testInput);
                $testOutput = $cases -> answer;
                array_push($outputArray, $testOutput);
            }


            #for name of the function
            curl_setopt($ch, CURLOPT_URL, "http://afsaccess2/~sc855/cs490/RC/retrieveQuestionDB.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $dataDumpQuestion = curl_exec($ch);
            
            $retrieveJson = json_decode($dataDumpQuestion);
            $allRows = $retrieveJson -> rowArray;
            $funcName;
            $constraint;
            foreach($allRows as $row){
                $tempQID = $row -> questionID;
                if($tempQID == $functionID)
                {
                    $funcName = $row -> functionName;
                    $constraint = $row -> constraints;
                }
            }
            #getting test cases ----------------------------------------------------------------------------------------

            $rtnCode = $retrieveQuestionJson -> returnCode;

            if (rtnCode == 0){  #success
                $studentCode = $el -> Answer;
                $AutoGraded -> studentSubmission=$studentCode;
                
                $alwaysPrintFlag = 0;
                $constraintPrintFlag =0;
                $printSearch = "/print\s*/";   //always check if print in function
                if (preg_match($printSearch, $studentCode))
                {
                    $alwaysPrintFlag = 1;
                }

                if ($constraint == "for")
                {
                    $forSearch = "/for\s*/";
                    if (preg_match($forSearch, $studentCode))
                    {
                        $masterComment .= "Constraint: For loop found";
                        $AutoGraded -> checkConstraint=0;
                    }
                    else
                    {
                        $masterComment .= "Constraint: For loop not found";
                        $AutoGraded -> checkConstraint=3;
                    }
                }

                if ($constraint == "while")
                {
                    $whileSearch = "/while\s*/";
                    if (preg_match($whileSearch, $studentCode))
                    {
                        $masterComment .= "Constraint: While loop found";
                        $AutoGraded -> checkConstraint=0;
                    }
                    else
                    {
                        $masterComment .= "Constraint: While loop not found";
                        $AutoGraded -> checkConstraint=3;
                    }
                }

                if ($constraint == "print")
                {
                    $printSearch = "/print\s*/";
                    if (preg_match($printSearch, $studentCode))
                    {
                        $constraintPrintFlag = 1;
                        $masterComment .= "Constraint: print found";
                        $AutoGraded -> checkConstraint=0;
                    }
                    else
                    {
                        $masterComment .= "Constraint: print not found";
                        $AutoGraded -> checkConstraint=3;
                    }
                }
                
                if ($constraint == "none")
                {
                    $masterComment .= "Contraint: No contraints";
                    $AutoGraded -> checkConstraint=0;
                    
                }
                
                //colon search --------------------------------------------
                $colonSearch = "/\s*:\s*/";
                
                $lines = explode("\n", $studentCode);
                
                if (preg_match($colonSearch, $lines[0], $matches))
                {
                    $masterComment .= ",Syntax: Function colon found";
                    $AutoGraded -> checkSyntax=0;
                }
                else
                {
                    $masterComment .= ",Syntax: Function colon not found";
                    $AutoGraded -> checkSyntax=2;
                    
                    //handle no colon found here
                    $correctedStudentCode = "";
                    $correctedStudentCode .= $lines[0];
                    $correctedStudentCode .= ":\n";
                    $length = count($lines);
                    for ($i = 1; $i < $length; $i++){
                        $correctedStudentCode .= $lines[$i]."\n";
                    }
                    $studentCode = $correctedStudentCode;
                }
                //colon search --------------------------------------------

                $functionSearch = "/def\s+$funcName/";
                if (preg_match($functionSearch, $studentCode))
                {
                    $masterComment .= ",Function Name: Good function name found";
                    $AutoGraded -> checkFunctionName=0;
                }
                else
                {
                    $BadfunctionName = "/(?<=def\s).+?(?=\()/";
                    preg_match($BadfunctionName, $studentCode, $match);
                                
                    $masterComment .= ",Function Name: Bad function name found";
                    $AutoGraded -> checkFunctionName =2;
                                    
                    $funcName = $match[0];
                }

                $cmdPrint = "print";
                if ($alwaysPrintFlag == 1 && $constraintPrintFlag == 1){
                    $cmdPrint = "";
                }
                
                $echoCommand = "echo '#!/bin/bash \n\n$studentCode' > buffer.py";  //make file 'buffer.py' that hosts students code
                shell_exec($echoCommand);
                
                $correctAnswers = 0;
                $wrongAnswers = 0;
                for($i = 0; $i < count($inputArray); $i++){
                    
                    $pythonComm = '"import buffer; '.$cmdPrint.'(buffer.'.$funcName.'('.$inputArray[$i].'));"';
                    $command = '/afs/cad/linux/anaconda3.6/anaconda/bin/python3 -c ' . $pythonComm;  //works with dynamic inputs
                    $output = shell_exec($command);
                    
                    if (trim($output) == trim($outputArray[$i])){
                        $correctAnswers = $correctAnswers + 1;
                    }
                    else{
                        $wrongAnswers = $wrongAnswers + 1;
                    }
                    
                }
                
                $inputCount = count($inputArray);
                if ($correctAnswers == $inputCount){
                    $masterComment .= ",Output: all test cases pass";
                    $AutoGraded -> checkTestCases=0;
                }
                elseif($correctAnswers > 0) {
                    $masterComment .= ",Output: some test cases pass";
                    $AutoGraded -> checkTestCases=$wrongAnswers;
                }
                else {
                    $masterComment .= ",Output: all test cases fail";
                    $AutoGraded -> checkTestCases=$wrongAnswers;
                }
                $masterComment .= ",Notes: ";
                $AutoGraded -> comments=$masterComment;


            } //end ofrtnCode = 0

            $x = json_encode($AutoGraded);
            $temp = urlencode($x);
            curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/insertSubmissionDB.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "Message=$temp");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);
            
        }//end of for each submission
    }//end of elseif

    echo $server_output;
    curl_close($ch);

?>
