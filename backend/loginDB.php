<?php
/**
 * Author: Shaurya Chandhoke
 * Layer: Backend
 * File: loginDB.php
 * Desc: Takes arguments from cURL which contain username and password to query
 *          Returns JSON object obtained from database response
 */

error_reporting(0); //Suppress error reporting

//Establish credentials to connect to database
define("username", 'sc855'); // Is also database name
define("password", 'ySOy7VopQ');
define("server", 'sql.njit.edu');

$connection = mysqli_connect(server, username, password, username);

//Connection checking: Check if --debug cla is passed for custom terminal output
if($argc > 1 && $argv[1] === '--debug'){
    var_dump($argv);
    var_dump($argc);

    if (!$connection) {
        echo "Connection failed. Check credentials" . "\n";
        die(1);
    } else {
        echo "Connection successfully established with <" . server . ">\n";
        die(0);
    }
}
else if(!$connection){
    $errorJSON = json_encode(array('returnCode'=>-1, 'message'=>'Connection to database failed'));
    echo $errorJSON;
    die(1);
}

//Parse parameters from post request
$userReq = $_POST['Username'];
$passReq = $_POST['Password'];

echo processQuery($userReq, $passReq);

/**
 * @func processQuery : Takes query to process to database
 * @param $username : Username to query through database
 * @param $password : Password to verify
 * @return false|string
 */
function processQuery($username, $password)
{
    global $connection;
    $returnJSON = new stdClass();
    $returnJSON->returnCode = -1;
    $returnJSON->message = 'undefined';
    $returnJSON->role = 'undefined';

    /** @noinspection SqlResolve */
    $query = 'SELECT * FROM cs100_LoginTable  WHERE username="'. $username .'"';

    if ($queryResult = $connection->query($query)) {
        if ($queryResult->num_rows === 0) {
            $returnJSON->returnCode = 1;
            $returnJSON->message = 'Credentials not found. Username may be incorrect';
        } else {
            while ($row = $queryResult->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $returnJSON->returnCode = 0;
                    $returnJSON->message = 'Credentials validated. Welcome ' . $username;
                    $returnJSON->role = $row['role'];
                    return json_encode($returnJSON);
                }
            }
            $returnJSON->returnCode = 1;
            $returnJSON->message = 'Credentials not found. Username and or Password may be incorrect';
        }
        $queryResult->free();
    }
    return json_encode($returnJSON);
}

$connection->close();