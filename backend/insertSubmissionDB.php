<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: insertSubmissionDB.php
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
$debugQuery = ($argc > 1 && $argv[1] == '--debugQuery');

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
$alterFlag = $_POST['Alter'];
$releaseScores = $_POST['Release'];
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

    if (empty($alterFlag) && empty($releaseScores)) {
        echo insertSubmission($decodedQuery);
    } elseif (empty($alterFlag)) {
        echo releaseScore($decodedQuery);
    } else {
        echo alterSubmission($decodedQuery);
    }

}

/**
 * @func insertSubmission
 * @description Takes json object and parses it to insert fields into submission DB
 * @param $query : A JSON object of all column fields that would be inserted into submission DB
 * @return false|string
 */
function insertSubmission($query)
{
    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    //From now till insertion to DB, any number of things can go wrong -- implement safe checking before proceeding
    $statement = $connection->prepare("INSERT INTO sc855.cs100_SubmissionBank(questionID, student, submission, checkFunctionName, checkSyntax, checkConstraint, checkTestCases, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }
    $returnCheck = $statement->bind_param("ssssssss", $questionID, $student, $submission, $checkFunctionName, $checkSyntax, $checkConstraint, $checkTestCases, $comments);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {

        //TODO: Confirm spelling of json obj
        $questionID = $query->questionID;
        $student = $query->student;
        $submission = $query->studentSubmission;
        $checkFunctionName = $query->checkFunctionName;
        $checkSyntax = $query->checkSyntax;
        $checkConstraint = $query->checkConstraint;
        $checkTestCases = $query->checkTestCases;
        $comments = $query->comments;

        $returnCheck = $statement->execute();
        if ($returnCheck === FALSE) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = '(FAIL) Failed to execute query. May be due to incorrect query. Run --debugMode locally';
            return json_encode($returnJSON);
        } else {
            $returnJSON->returnCode = 0;
            $returnJSON->message = 'Insertion success';
        }
    }

    $statement->close();

    return json_encode($returnJSON);
}

/**
 * @func releaseScore
 * @desc releases score from that table
 * @param $query : Student name to release scores to
 * @return false|string
 */
function releaseScore($query)
{

    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    $statement = $connection->prepare("UPDATE sc855.cs100_SubmissionBank SET releaseScore=1 WHERE student=?");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }
    $returnCheck = $statement->bind_param("s", $name);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {

        $name = $query->student;

        $returnCheck = $statement->execute();
        if ($returnCheck === FALSE) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = '(FAIL) Failed to execute query. May be due to incorrect query. Run --debugMode locally';
            return json_encode($returnJSON);
        } else {
            $returnJSON->returnCode = 0;
            $returnJSON->message = 'Release success';
        }

    }

    $statement->close();

    return json_encode($returnJSON);

}


/**
 * @func alterSubmission
 * @desc alters submission from submission table
 * @param $query : Info to update in submission table
 * @return false|string
 */
function alterSubmission($query)
{

    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';

    $statement = $connection->prepare("UPDATE sc855.cs100_SubmissionBank SET checkFunctionName=?, checkSyntax=?, checkConstraint=?, checkTestCases=?, comments=? WHERE questionID=? AND student=?");
    if ($statement === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to prepare query statement. May be due to mismatched query parameters (backend issue)';
        return json_encode($returnJSON);
    }

    $returnCheck = $statement->bind_param("sssssss", $checkFunctionName, $checkSyntax, $checkConstraint, $checkTestCases, $comments, $questionID, $student);
    if ($returnCheck === FALSE) {
        $returnJSON->returnCode = 1;
        $returnJSON->message = '(FAIL) Failed to bind query parameters. May be due to invalid/mismatched information from json';
        return json_encode($returnJSON);
    } else {
        //TODO: This may be wrong, need to know json format from middle/front
        foreach ($query->Submission as $el) {

            $questionID = $el->questionID;
            $student = $el->student;
            $checkFunctionName = $el->checkFunctionName;
            $checkSyntax = $el->checkSyntax;
            $checkConstraint = $el->checkConstraint;
            $checkTestCases = $el->checkTestCases;
            $comments = $el->comments;

            $returnCheck = $statement->execute();
            if ($returnCheck === FALSE) {
                $returnJSON->returnCode = 1;
                $returnJSON->message = '(FAIL) Failed to execute query. May be due to incorrect query. Run --debugMode locally';
                return json_encode($returnJSON);
            } else {
                $returnJSON->returnCode = 0;
                $returnJSON->message = 'Update success';
            }
        }
    }
    $statement->close();

    return json_encode($returnJSON);
}

$connection->close();