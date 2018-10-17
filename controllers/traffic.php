<?php

require "../models/traffic.php";

require "../models/database.php";

require "../models/trafficLabels.php";
require "../models/spaces.php";

require "error.php";

$headers = apache_request_headers();

$database = new Database();

$conn = $database->getViewConnection();


//authorized users, in case it's a post request
$authorized = array("display");



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



?>