<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: insertExamDB.php
 * Desc: Takes information from cURL to insert exam to exam db
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
    echo insertExam($decodedQuery);
}


/**
 * @func insertExam
 * @description Takes json object and parses it to insert fields into DB
 * @param $query : A JSON object of all columns fields that would be inserted into single row
 * @return false|string
 */
function insertExam($query)
{
    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    //From now till insertion to DB, any number of things can go wrong -- implement safe checking before proceeding
    $statement = $connection->prepare("INSERT INTO sc855.cs100_ExamTable(questionID, points) VALUES (?, ?)");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }

    $returnCheck = $statement->bind_param("ss", $questionID, $points);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {
        $questionArray = $query->Exam;

        foreach ($questionArray as $question) {

            $questionID = $question->questionID;
            $points = $question->points;

            $returnCheck = $statement->execute();
            if ($returnCheck === FALSE) {
                $returnJSON->returnCode = 1;
                $returnJSON->message = '(FAIL) Failed to execute query. May be due to connection to database. Run --debugMode locally';
                return json_encode($returnJSON);
            } else {
                $returnJSON->returnCode = 0;
                $returnJSON->message = 'Insertion success';
            }
        }

        $statement->close();
        return json_encode($returnJSON);
    }
}

$connection->close();