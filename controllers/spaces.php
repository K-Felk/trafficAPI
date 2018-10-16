<?php

require "../models/spaces.php";

require "../models/database.php";

require "error.php";

$headers = apache_request_headers();

$database = new Database();

$conn = $database->getConnection();

//make sure we can connect to the database
if (!$conn) { 
    writeError("Cannot Connect to database: " . $database->errMsg);
    
    header("HTTP/1.0 500 Internal Server Error");
    die();   
}

$spaces = new Spaces($conn);



if (isset($spaces->spaces)) {
    header('Content-Type: application/json');
    echo json_encode($spaces->spaces);

} else {
    if ($spaces->errMsg == "No Labels Found.") {
        header("HTTP/1.0 404 Page Not Found");
    } else {
        writeError("Cannot retrieve requested traffic data: " . $spaces->errMsg);
        
        header("HTTP/1.0 500 Internal Server Error");
    }
}



?>