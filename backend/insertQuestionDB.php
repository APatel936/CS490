<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: insertQuestionDB.php
 * Desc: Takes information from cURL to insert values into database
 * Returns: Insertion success or fail
 */

error_reporting(0);

//Establish credentials to connect to database
define("username", 'sc855'); // Is also database name
define("password", 'ySOy7VopQ');
define("server", 'sql.njit.edu');

$connection = mysqli_connect(server, username, password, username);

$debugMode = ($argc > 1 && $argv[1] == '--debug');
$debugQuery = ($argc > 2 && $argv[2] == '--debugQuery');

//Connection checking: Check if --debug cla is passed for custom terminal output
if ($debugMode) {
    var_dump($argv);
    var_dump($argc);

    if (!$connection) {
        echo "Connection failed. Check credentials" . "\n";
        die(1);
    } else {
        echo "Connection successfully established with <" . server . ">\n";
        die(0);
    }
} else if (!$connection) {
    $errorJSON = json_encode(array('returnCode' => -1, 'message' => 'Connection to database failed'));
    echo $errorJSON;
    die(1);
}

$insertQuery = $_POST['Message'];
$decodedQuery = json_decode($insertQuery);

//Debug json formatting issues from middle
if ($debugQuery) {
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
            break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            echo ' - Unknown error';
            break;
    }
} else {
    echo insertQuestion($decodedQuery);
}


/**
 * @func insertQuestion
 * @description Takes json object and parses it to insert fields into DB
 * @param $query : A JSON object of all columns fields that would be inserted into single row
 * @return false|string
 */
function insertQuestion($query)
{
    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';


    //From now till insertion to DB, any number of things can go wrong -- implement safe checking before proceeding
    $statement = $connection->prepare("INSERT INTO sc855.cs100_QuestionBank(topic, difficulty, functionName, constraints, functionDesc) VALUES (?, ?, ?, ?, ?)");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }

    $returnCheck = $statement->bind_param("sssss", $topic, $difficulty, $functionName, $constraint, $functionDescription);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {
        //Parsing front end json obj and execute
        $topic = $query->Topic;
        $difficulty = $query->Difficulty;
        $functionName = $query->Name;
        $constraint = $query->Constraint;
        $functionDescription = $query->Description;
        $parameters = $query->Parameters;
        $testCases = $query->TestCases;

        $returnCheck = $statement->execute();
        if ($returnCheck === FALSE) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = '(FAIL) Failed to execute query. May be due to connection to database. Run --debugMode locally';
            return json_encode($returnJSON);
        } else {
            $insertID = $connection->insert_id;

            if (json_decode(insertParameters($parameters, $insertID))->returnCode===0 && json_decode(insertTests($testCases, $insertID))->returnCode===0){
                $returnJSON->returnCode = 0;
                $returnJSON->message = 'Insertion success';
            }
        }
    }

    $statement->close();

    return json_encode($returnJSON);

}

/**
 * @param $params
 * @param $id
 * @return false|string
 */
function insertParameters($params, $id)
{

    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    $statement = $connection->prepare("INSERT INTO sc855.cs100_ParameterTable(questionID, parameter) SELECT questionID, ? FROM cs100_QuestionBank WHERE questionID=?");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }

    $returnCheck = $statement->bind_param("ss", $parameter, $questionID);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {

        $parameters = $params;
        $paramArr = explode(',', $parameters);
        foreach($paramArr as $param){
            $parameter = $param;
            $questionID = $id;

            $returnCheck = $statement->execute();
            if ($returnCheck === FALSE) {
                $returnJSON->returnCode = 1;
                $returnJSON->message = '(FAIL) Failed to execute query. May be due to connection to database. Run --debugMode locally';
                return json_encode($returnJSON);
            }
        }

        $returnJSON->returnCode = 0;
        $returnJSON->message = 'Insertion success';

    }
    $statement->close();

    return json_encode($returnJSON);

}

/**
 * @param $tests
 * @param $id
 * @return false|string
 */
function insertTests($tests, $id)
{

    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    $statement = $connection->prepare("INSERT INTO sc855.cs100_TestCaseTable(questionID, testCase, answer) SELECT questionID, ? , ? FROM cs100_QuestionBank WHERE questionID=?");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }

    $returnCheck = $statement->bind_param("sss", $testCase, $answer, $questionID);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {

        $testArr = explode(';', $tests);
        $regex = '/(?<=\().+?(?=\))/';

        foreach($testArr as $case){
            preg_match($regex, $case, $match); //To get test case
            $temp = explode(":", $case); //To get answer

            $testCase = $match[0];
            $answer = $temp[1];
            $questionID = $id;

            $returnCheck = $statement->execute();
            if ($returnCheck === FALSE) {
                $returnJSON->returnCode = 1;
                $returnJSON->message = '(FAIL) Failed to execute query. May be due to connection to database. Run --debugMode locally';
                return json_encode($returnJSON);
            }

        }

        $returnJSON->returnCode = 0;
        $returnJSON->message = 'Insertion success';

    }
    $statement->close();

    return json_encode($returnJSON);
}

$connection->close();