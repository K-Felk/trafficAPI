<?php

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    die();
}

require "../models/trafficLabels.php";

require "../models/database.php";

require "../models/spaces.php";

require "../models/user.php";

require "../models/traffic.php";

require "error.php";

//list of users authorized to insert feedback data
$authorizedUsers = array("display");

$headers = apache_request_headers();

$database = new Database();

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $conn = $database->getEditConnection();
} else {
    $conn = $database->getViewConnection();
}

//make sure we can connect to the database
if (!$conn) { 
    writeError("Cannot Connect to database: " . $database->errMsg);
    
    header("HTTP/1.0 500 Internal Server Error");
    die();   
}

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    $traffic = new trafficLabels($conn);



    if (isset($traffic->trafficLabels)) {
        header('Content-Type: application/json');
        echo json_encode($traffic->trafficLabels);

    } else {
        if ($traffic->errMsg == "No Labels Found.") {
            header("HTTP/1.0 404 Page Not Found");
        } else {
            writeError("Cannot retrieve requested traffic data: " . $traffic->errMsg);
            header("HTTP/1.0 500 Internal Server Error");
        }
    }

}
//put requests need to have a POST parameter of "initials" for the initials of the person entering the data, 
//and a message body composed of json, the first aray must be  with exactly 16 entries, each with a "space" parameter and a "level" paramter, and
//an optional "comments" parameter
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if (isset($_SERVER['PHP_AUTH_PW'])) {
        if ($_SERVER['PHP_AUTH_PW'] == "") {
            writeError("Blank Password Supplied");
            header("HTTP/1.0 403 Authentication Failed");
            die();
        }

        $user = new User($conn);

        if (!$user->setUser($_SERVER['PHP_AUTH_PW'])) {
            writeError($user->diagnostic);
            if ($user->errMsg == "No user Found.") {
                
                header("HTTP/1.0 403 Authentication Failed");
                die();
            } else {
                writeError("Cannot retrieve user data: " . $user->errMsg);
            
                header("HTTP/1.0 500 Internal Server Error");
                die();
            }
        }
    } else {
        header("HTTP/1.0 401 Unauthorized");
        die();
    }

    if (!in_array($user->userName, $authorizedUsers)) {
        header("HTTP/1.0 401 Unauthorized");
        die();
    }

    $contents = file_get_contents('php://input');

    //is there a json payload, and it it correctly formed?
    if (!$contents) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $trafficData = json_decode($contents, JSON_OBJECT_AS_ARRAY);

    if (is_null($trafficData) || $trafficData === false) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //make sure the initials are set, and it's 2-3 characters
    if (!isset($trafficData["initials"])) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    if (strlen($trafficData["initials"]) <= 1 || strlen($trafficData["initials"]) >= 4) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }
    //grab the initials and remove them from the main data structure

    $initials = $trafficData["initials"];

    unset($trafficData["initials"]);

    //start checking the structure of the data, it needs to have exactly 16 entries

    if (count($trafficData) != 16) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //pull space and traffic labels so that we can verify that the input data is correct
    $spaceLabels = new Spaces($conn);
    $trafficLabels = new TrafficLabels($conn);

    if (is_null($spaceLabels->spaces) || is_null($trafficLabels->trafficLabels)) {
        errorWrite("Cannot retrieve requested traffic data: " . $trafficLabels->errMsg);
                
        header("HTTP/1.0 500 Internal Server Error");
        die();
    }

    $trafficIds = array();

    foreach ($trafficLabels->trafficLabels as $entry) {
        $trafficIds[] = $entry["ID"];
    }


    $spaceIds = array();

    foreach ($spaceLabels->spaces as $entry) {
        $spaceIds[] = $entry["ID"];
    }

    //loop through data, ensure that required values are set, and they they contain legal values
    foreach ($trafficData as $data) {
        if (!isset($data["level"])) {
            header("HTTP/1.0 400 Bad Request");
            die();

        
        } else if (!isset($data["space"])) {
            header("HTTP/1.0 400 Bad Request");
            die();
        }

        //now check that the supplied values are legal

        if (!in_array($data["space"], $spaceIds)) {
            header("HTTP/1.0 400 Bad Request");
            die(); 
        }

        if (!in_array($data["level"], $trafficIds)) {
            header("HTTP/1.0 400 Bad Request");
            die(); 
        }

        //comments don't have to be set.  If they aren't, set an empty string

        if (!isset($data["comments"])) {
            $data["comments"] = "";
        }

        //at this point, we should have valid traffic data.

    }

    $traffic = new Traffic($conn);

    $result = $traffic->saveTraffic($initials, $trafficData);

    if (!$result) {
        writeError("Cannot save traffic data: " . $traffic->errMsg);
        writeError($traffic->diagnostic);

        header("HTTP/1.0 500 Internal Server Error");
        
    } else {
        header("HTTP/1.0 200 OK");
    }
}

?>