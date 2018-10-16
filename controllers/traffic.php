<?php

require "../models/traffic.php";

require "../models/database.php";

require "../models/trafficLabels.php";
require "../models/spaces.php";

$headers = apache_request_headers();

$database = new Database();

$conn = $database->getConnection();

function writeError($errMsg) {
    $handle = fopen("../error.log", "a");
    fwrite($handle, "Cannot Connect to database: " . $database->errMsg);
    fclose($handle);

}

//make sure we can connect to the database
if (!$conn) { 
    writeError("Cannot Connect to database: " . $database->errMsg);
    header("HTTP/1.0 500 Internal Server Error");
    die();   
}

$traffic = new Traffic($conn);



//requesting traffic data, either for an entry
//trafficAPI/entries/entryID/traffic
//or for a space
//trafficapi/spaces/spaceID/traffic
//or a specific entry and space
//trafficapi/entries/entryID/spaces/spaceID/traffic
if ($_SERVER['REQUEST_METHOD'] === "GET") {
    if (isset($_GET["spaceID"]) && isset($_GET["entryID"])) {

        if ($traffic->setFromDatabase($_GET["entryID"], $_GET["spaceID"])) {
            header('Content-Type: application/json');
            echo json_encode($traffic->getArray());
        }  else {
            if ($traffic->errMsg == "No entries found for that ID number and space.") {
                header("HTTP/1.0 404 Page Not Found");
            } else {
                writeError("Cannot retrieve requested traffic data: " . $traffic->errMsg);
                header("HTTP/1.0 500 Internal Server Error");
            }
        }      

    } else if (!isset($_GET["spaceID"]) && isset($_GET["entryID"])) {
        $results = $traffic->getAllByEntry($_GET["entryID"]);
        if ($results) {
            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            if ($traffic->errMsg == "No entries found for that ID number.") {
                header("HTTP/1.0 404 Page Not Found");
            } else {
                errorWrite("Cannot retrieve requested traffic data: " . $traffic->errMsg);
                header("HTTP/1.0 500 Internal Server Error");
            }
        }


    } else if (isset($_GET["spaceID"]) && !isset($_GET["entryID"]) && !isset($_GET["end"]) && !isset($_GET["start"])) {
        $results = $traffic->getAllBySpace($_GET["spaceID"]);
        if ($results) {
            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            if ($traffic->errMsg == "No entries found for that ID number.") {
                header("HTTP/1.0 404 Page Not Found");
            } else {
                writeError("Cannot retrieve requested traffic data: " . $traffic->errMsg);
                header("HTTP/1.0 500 Internal Server Error");
            }
        }
    } elseif ((isset($_GET["start"]) || isset($_GET["end"])) &&  isset($_GET["spaceID"])) {

        
        $results = $traffic->getByDate($_GET["start"],$_GET["end"],$_GET["spaceID"]);
        if ($results) {
            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            if ($traffic->errMsg == "No entries found.") {
                header("HTTP/1.0 404 Page Not Found");
            } else {
                errorWrite("Cannot retrieve requested traffic data: " . $traffic->errMsg);
                header("HTTP/1.0 500 Internal Server Error");
            }
        }
    } else {
        header("HTTP/1.0 404 Page Not Found");
    }

}

//put requests need to have a POST parameter of "initials" for the initials of the person entering the data, 
//and a message body composed of json with exactly 16 entries, each with a "space" parameter and a "level" paramter, and
//an optional "comments" parameter
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    //make sure the initials are set, and it's 2-3 characters
    if (!isset($_POST["initials"])) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    if (strlen($_POST["initials"]) <= 1 || strlen($_POST["initials"]) >= 4) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    $contents = file_get_contents('php://input');
    
    //is there a json payload, and it it correctly formed?
    if (!$contents) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $trafficData = json_decode($contents);

    if (is_null($trafficData) || $trafficData === false) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //start checking the structure of the data, it needs to have exactly 16 entries

    if (count($trafficData) != 16) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }
    

    //pull space and traffic labels so that we can verify that the input data is correct
    $spaceLabels = new Spaces($conn);
    $trafficLabels = new TrafficLabels($conn);

    if (is_null($spaceLabels->spaces) || is_null($trafficLabels->trafficLabels)) {
        errorWrite("Cannot retrieve requested traffic data: " . $traffic->errMsg);
                
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

    $result = $traffic->saveTraffic($POST["initials"], $trafficData);

    if (!$result) {
        writeError("Cannot retrieve requested traffic data: " . $traffic->errMsg);
        header("HTTP/1.0 500 Internal Server Error");
        die();
    }

    



}


?>