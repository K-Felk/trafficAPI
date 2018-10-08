<?php

require "../models/feedback.php";

require "../models/database.php";

if (empty($_SERVER['HTTPS'])) {
    header("HTTP/1.0 500 Internal Server Error");
    die(); 
}

$database = new Database();

$conn = $database->getConnection();

//make sure we can connect to the database
if (!$conn) { 
    $handle = fopen("../error.log", "a");
    fwrite($handle, "Cannot Connect to database: " . $database->errMsg);
    fclose($handle);
    header("HTTP/1.0 500 Internal Server Error");
    die();   
}

$feedback = new feedBack($conn);

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    if (isset($_GET["start"]) || isset($_GET["end"])) {
        if (isset($_GET["start"])) {
            $start = $_GET["start"];

        } else {
            $start = "";
        }

        if (isset($_GET["end"])) {
            $end = $_GET["end"];

        } else {
            $end = "";
        }
        $results = $feedback->getByDate($start, $end);

    } else {
        $results = $feedback->getAll();
        
    } 

    if ($results) {
        header('Content-Type: application/json');
        echo json_encode($results);
    } else {
        if ($feedback->errMsg = "No entries found.") {
            header("HTTP/1.0 404 Page Not Found");
        } else {
            $handle = fopen("../error.log", "a");
            fwrite($handle, "Cannot retrieve requested traffic data: " . $spaces->errMsg);
            fclose($handle);
            header("HTTP/1.0 500 Internal Server Error");
        }
    }


}





?>