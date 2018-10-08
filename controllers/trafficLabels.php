<?php

require "../models/trafficLabels.php";

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

$traffic = new trafficLabels($conn);



if (isset($traffic->trafficLabels)) {
    header('Content-Type: application/json');
    echo json_encode($traffic->trafficLabels);

} else {
    if ($traffic->errMsg == "No Labels Found.") {
        header("HTTP/1.0 404 Page Not Found");
    } else {
        $handle = fopen("../error.log", "a");
        fwrite($handle, "Cannot retrieve requested traffic data: " . $traffic->errMsg);
        fclose($handle);
        header("HTTP/1.0 500 Internal Server Error");
    }
}



?>