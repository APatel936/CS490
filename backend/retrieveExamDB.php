<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: retrieveExamDB.php
 * Desc: Retrieves all contents from exam DB
 */

error_reporting(0); //Suppress error reporting

//Establish credentials to connect to database
define("username", 'sc855'); // Is also database name
define("password", 'ySOy7VopQ');
define("server", 'sql.njit.edu');
define("retrieveQuery", 'SELECT cs100_QuestionBank.*, cs100_ExamTable.points FROM cs100_QuestionBank, cs100_ExamTable WHERE cs100_ExamTable.questionID=cs100_QuestionBank.questionID'); //Main query that will be used to retrieve DB contents

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

//$functionName = $_POST['Message'];
//echo empty($functionName) ? retrieveExam(retrieveQuery) : retrieveSpecific($functionName);
echo retrieveExam(retrieveQuery);
/**
 * @func retrieveExam
 * @description Returns all rows in database as encoded JSON obj string
 * @param $query : Query string that will return all rows
 * @return false|string
 */
function retrieveExam($query)
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

$connection->close();