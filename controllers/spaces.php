<?php

require "../models/spaces.php";

require "../models/database.php";

$headers = apache_request_headers();

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

$spaces = new Spaces($conn);



if (isset($spaces->spaces)) {
    header('Content-Type: application/json');
    echo json_encode($spaces->spaces);

} else {
    if ($spaces->errMsg == "No Labels Found.") {
        header("HTTP/1.0 404 Page Not Found");
    } else {
        $handle = fopen("../error.log", "a");
        fwrite($handle, "Cannot retrieve requested traffic data: " . $spaces->errMsg);
        fclose($handle);
        header("HTTP/1.0 500 Internal Server Error");
    }
}



?>