<?php

require "../models/feedback.php";

require "../models/database.php";

require "../models/user.php";

if (empty($_SERVER['HTTPS'])) {
    header("HTTP/1.0 500 Internal Server Error");
    die(); 
}

//list of users authorized to insert feedback data
$authorizedUsers = array("display");

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


//requesting data from the API
if ($_SERVER['REQUEST_METHOD'] === "GET") {
    //are you requesting data by date range?
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
    //if not, get everything
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

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_SERVER['PHP_AUTH_PW'])) {
        $user = new User($conn);

        if (!$user->setUser($_SERVER['PHP_AUTH_PW'])) {
            if ($user->errMsg == "No user Found.") {
                header("HTTP/1.0 403 Authentication Failed");
                die();
            } else {
            $handle = fopen("../error.log", "a");
            fwrite($handle, "Cannot retrieve user data: " . $user->errMsg);
            fclose($handle);
            header("HTTP/1.0 500 Internal Server Error");
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

    if (!isset($_GET["feedBackLevel"])) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $feedBackValues = array("1","2","3","4","5");

    if (!in_array($_GET["feedBackLevel"], $feedBackValues)) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //if we make it to this point, insert the feedback

    if ($feedback->insertFeedBack($_GET["feedBackLevel"])) {
        header("HTTP/1.0 200 OK");
    } else {
        $handle = fopen("../error.log", "a");
        fwrite($handle, "Cannot authenticate user for post: " . $user->errMsg);
        fclose($handle);
        header("HTTP/1.0 500 Internal Server Error");
        die();
    }

    
}





?>