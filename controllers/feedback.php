<?php

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    die();
}

require "../models/feedback.php";

require "../models/database.php";

require "../models/user.php";

require "error.php";


$authorizedUsers = array("display");

$logfile = fopen("../feedbackpost.log", "w") or die("Unable to open logfile!");
fwrite($logfile, $_SERVER['REQUEST_METHOD']);
fclose($logfile);

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
            writeError("Cannot retrieve requested traffic data: " . $spaces->errMsg);
            
            header("HTTP/1.0 500 Internal Server Error");
        }
    }


}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $logfile = fopen("../feedbackpost.log", "a") or die("Unable to open logfile!");
    fwrite($logfile, "password sent: " . $_SERVER['PHP_AUTH_PW'] . "\n");
    fwrite($logfile, "return value from setuser: " . $user->setUser($_SERVER['PHP_AUTH_PW']) . "\n");
    fwrite($logfile, "username from database: " . $user->userName . "\n");

    fwrite($logfile, "is there any error message: " . $user->errMsg . "\n");
    fclose($logfile);
    if (isset($_SERVER['PHP_AUTH_PW'])) {
        
        $user = new User($conn);

        if (!$user->setUser($_SERVER['PHP_AUTH_PW'])) {
            
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

    if (!isset($_POST["feedBackLevel"])) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $feedBackValues = array("1","2","3","4","5");

    if (!in_array($_POST["feedBackLevel"], $feedBackValues)) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //if we make it to this point, insert the feedback

    if ($feedback->insertFeedBack($_POST["feedBackLevel"])) {
        header("HTTP/1.0 200 OK");
    } else {
        writeError("Cannot authenticate user for post: " . $user->errMsg);
        
        header("HTTP/1.0 500 Internal Server Error");
        die();
    }

    
}





?>