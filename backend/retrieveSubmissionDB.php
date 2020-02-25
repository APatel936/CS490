<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: retrieveSubmissionDB.php
 * Desc: Given a specific student via cURL, grabs row from submission table in DB
 * Returns: Student submission details from submission table in DB
 */

error_reporting(0); //Suppress error reporting

//Establish credentials to connect to database
define("username", 'sc855'); // Is also database name
define("password", 'ySOy7VopQ');
define("server", 'sql.njit.edu');
define("dumpQuery", 'SELECT cs100_QuestionBank.functionDesc, cs100_SubmissionBank.*, cs100_ExamTable.points AS pointsTotal FROM cs100_SubmissionBank, cs100_ExamTable, cs100_QuestionBank WHERE cs100_SubmissionBank.questionID=cs100_ExamTable.questionID AND cs100_SubmissionBank.questionID=cs100_QuestionBank.questionID;');

$connection = mysqli_connect(server, username, password, username);

//Connection checking: Check if --debug cla is passed for custom terminal output
if ($argc > 1 && $argv[1] === '--debug') {
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

$student = $_POST['Student'];

echo empty($student) ? retrieveAllSubmission(dumpQuery) : retrieveSubmissionByName($student);

/**
 * @func retrieveAllSubmission
 * @desc Returns all rows in submission table
 * @param $query : Dumping query
 * @return false|string
 */
function retrieveAllSubmission($query)
{

    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';
    $returnJSON->rowArray = [];

    if ($queryResult = $connection->query($query)) {
        if ($queryResult->num_rows === 0) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = 'Database is empty, no rows returned';
        } else {
            $returnJSON->returnCode = 0;
            $returnJSON->message = 'Returning: ' . $queryResult->num_rows . ' rows';
            while ($row = $queryResult->fetch_assoc()) {
                array_push($returnJSON->rowArray, $row);
            }
        }
        $queryResult->free();
    } else {
        echo "(FAIL) Query failed";
    }
    return json_encode($returnJSON);
}

/**
 * @func retrieveSubmission
 * @description Queries student over submission table
 * @param $name : Student name
 * @return false|string
 */
function retrieveSubmissionByName($name)
{
    global $connection;

    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';
    $returnJSON->rowArr = [];

    /** @noinspection SqlResolve */
//    $statement = 'SELECT * FROM sc855.cs100_SubmissionBank WHERE student="' . $name . '"';
    $statement = 'SELECT cs100_QuestionBank.functionDesc, cs100_SubmissionBank.*, cs100_ExamTable.points AS pointsTotal FROM cs100_SubmissionBank, cs100_ExamTable, cs100_QuestionBank WHERE cs100_SubmissionBank.questionID=cs100_ExamTable.questionID AND cs100_SubmissionBank.questionID=cs100_QuestionBank.questionID AND cs100_SubmissionBank.student="' . $name . '"';

    if ($queryResult = $connection->query($statement)) {
        if ($queryResult->num_rows === 0) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = 'No student found with that name';
        } else {
            while($row = $queryResult->fetch_assoc()){
                $returnJSON->returnCode = 0;
                $returnJSON->message = 'Submission details for ' . $name;
                array_push($returnJSON->rowArr, $row);
            }
        }
    }

    $queryResult->free();
    return json_encode($returnJSON);
}

$connection->close();