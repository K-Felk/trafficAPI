<?php

require "../models/entry.php";

require "../models/database.php";

$headers = apache_request_headers();

$database = new Database();
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $conn = $database->getEditConnection();
} else {
    $conn = $database->getViewConnection();
}

if (!$conn) {

    
    $handle = fopen("../error.log", "a");
    fwrite($handle, "Cannot Connect to database: " . $database->errMsg);
    fclose($handle);
    header("HTTP/1.0 500 Internal Server Error");
    die();


    
}

$entry = new Entry($conn);


//requesting entry data, either all of it:
//trafficAPI/entries
//or a specific request number:
//trafficapi/entries/23456
//or request by a date range
//trafficapi/entries/bydate?start=startdate&end=enddate
//
if ($_SERVER['REQUEST_METHOD'] === "GET") {
    if (isset($_GET) && isset($_GET['entryID'])) {
        $entryID = $_GET['entryID'];
        if ($entry->setFromDatabase($entryID)) {
            header('Content-Type: application/json');
            echo json_encode($entry->getArray());

        } else {
            if ($entry->errMsg == "No entries found for that ID number.") {
                header("HTTP/1.0 404 File Not Found");
            } else {
                $handle = fopen("../error.log", "a");
                fwrite($handle, "Cannot retrieve requested entry: " . $entry->errMsg);
                fclose($handle);
                header("HTTP/1.0 500 Internal Server Error");
            }

        }    
    } else if (isset($_GET["start"]) || isset($_GET["end"])) {

        
        if (!isset($_GET["start"])) {
            $_GET["start"] = "";
        }
        if (!isset($_GET["end"])) {
            $_GET["end"] = "";
        }


        $result = $entry->getByDate($_GET["start"], $_GET["end"]);
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            if ($entry->errMsg == "No entries found.") {
                header("HTTP/1.0 404 File Not Found");
            } else {
                $handle = fopen("../error.log", "a");
                fwrite($handle, "Cannot retrieve requested entry: " . $entry->errMsg);
                fclose($handle);
                header("HTTP/1.0 500 Internal Server Error");
            }
        }
    

        //get all entries from the table and dump them in json format
    } else {

        $entries = $entry->getAll();
        if (!$entries) {
            if ($entry->errMsg == "No entries found.") {
                header("HTTP/1.0 404 File Not Found");
            } else {
                $handle = fopen("../error.log", "a");
                fwrite($handle, "Cannot retrieve requested entry: " . $entry->errMsg);
                fclose($handle);
                header("HTTP/1.0 500 Internal Server Error");
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode($entries);
        }
    }

}











?>