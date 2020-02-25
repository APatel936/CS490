<?php
    
    //Author: Aadarsh Patel
    //Layer: Middle
    //File: middleLogin.php
    //Desc: Checks database and checks python scripts, returns JSON
    
    $ch = curl_init();
    $username = $_POST['Username'];
    $password = $_POST['Password'];
    
    
    $sendToFrontJSON = new stdClass;
    
    curl_setopt($ch, CURLOPT_URL, "http://afsaccess2.njit.edu/~sc855/cs490/RC/loginDB.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "Username=$username&Password=$password");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $server_output = curl_exec($ch);
    
//    echo $server_output;
    
    $jsonObj = json_decode($server_output);
    $rtnCode = $jsonObj -> returnCode;   //bad = 1    good = 0
    
    if ($rtnCode == 0){ //good cred
        $sendToFrontJSON -> credentials=0;
        
        $rtnRole = $jsonObj -> role;
        $sendToFrontJSON -> role=$rtnRole;
        
    }
    else {  //bad creds
        $sendToFrontJSON -> credentials=1;
    }
    
    $x = json_encode($sendToFrontJSON);
    echo "$x";
    curl_close($ch);

?>
