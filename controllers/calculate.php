<?php

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    die();
}


//checks all date range data, if it's valid, returns a reformatted array of values. 
//if not, returns false.
function checkConvertDates($dates) {

    
    foreach ($dates as $key => $range) {

        //there must be two values for each range, and the 
        //first one must be the start time, and the second the end time
        if (count($range) != 2) {
            return false;
        }
    
        $startTime = strtotime($range[0]);
        $endTime = strtotime($range[1]);

        //if they can't be converted to dates, bad request
        if (!$startTime || !$endTime) {
            return false;
        }

        if ($endTime <= $startTime) {
            return false;

        }

        //convert the dates into the correct format for the search
        //reassign values to the array

        $range[0] = date("Y-m-d", $startTime);
        $range[1] = date("Y-m-d", $endTime);

        $dates[$key] = $range;

    }
    return $dates;
}

function checkHours($hours) {
    foreach ($hours as $key => $range) {
        if (count($range) != 2) {
            return false;
        }

        $start = intval($range[0]);
        $end = intval($range[1]);

        if ($start < 0 || $end < 0 ) {
            return false;
        }

        if ($start > 24 || $end > 24 ) {
            return false;
        }

        if ($end < $start) {
            return false;
        }

        $hours[$key] = array($start, $end);

    }
    return $hours;

}

require "../models/entry.php";
require "../models/database.php";
require "error.php";

//takes multiple date/time parameters are returns mode and/or average of trafffic for those paramaters for each space.
$database = new Database();

$conn = $database->getViewConnection();

//make sure we can connect to the database
if (!$conn) { 
    writeError("Cannot Connect to database: " . $database->errMsg);
    header("HTTP/1.0 500 Internal Server Error");
    die();   
}

$entry = new entry($conn);

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $contents = file_get_contents('php://input');

    //is there a json payload, and it it correctly formed?
    if (!$contents) {
        
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $params = json_decode($contents, JSON_OBJECT_AS_ARRAY);

    if (is_null($params) || $params === false) {
        header("HTTP/1.0 400 Bad Request");
        die();
    }

    //is there at least one "include" date range?

    if (!array_key_exists("include", $params)) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    $include = $params["include"];

    $includeChecked = checkConvertDates($include);

    if ($includeChecked === false) {
        header("HTTP/1.0 408 Bad Request");
        die();
    }


    
    //if there are exclude dates, validate them.  Otherwise, create an empty array.
    if (array_key_exists("exclude", $params)) {

        
         $excludeChecked = checkConvertDates($exclude);

         if ($excludeChecked === false) {
            header("HTTP/1.0 400 Bad Request");
            die();
         }

    } else {
        $excludeChecked = array();
    }

    if (array_key_exists("hoursInclude", $params)) {

        $hoursInclude = $params["hoursInclude"];

        $hoursIncludeChecked = checkHours($hoursInclude);

        if ($hoursIncludeChecked === false) {
            header("HTTP/1.0 400 Bad Request");
            die();

        }

    } else {
        $hoursIncludeChecked = array();
    }

    if (array_key_exists("hoursExclude", $params)) {

        $hoursExclude = $params["hoursExclude"];

        $hoursExcludeChecked = checkHours($hoursExclude);

        if ($hoursExcludeChecked === false) {
            header("HTTP/1.0 400 Bad Request");
            die();

        }

    } else {
        $hoursExcludeChecked = array();
    }

    //do we finally have all our parameters?

    $averages = $entry->getByDateAverages($includeChecked, $excludeChecked, $hoursIncludeChecked, $hoursExcludeChecked);

    if ($averages === false) {
        writeError("Cannot calculate averages: " . $entry->errMsg);
            
        header("HTTP/1.0 500 Internal Server Error");
    } else {
        echo json_encode($averages);
    }

    
    

}






?>
