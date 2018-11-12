<?php

//if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
//    die();
//}


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

        if ($endTime < $startTime) {
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

    //there must be a "mode" entry to tell us wether we are looking for mode or average

    if (!array_key_exists("mode", $params)) {
        header("HTTP/1.0 400 Bad Request");
        die();

    }

    //it must contain either "mode" or "average"

    if ($params["mode"] != "mode" && $params["mode"] != "average") {
        header("HTTP/1.0 400 Bad Request");
        die();
    }
    //mode inquiries require a spaceID which must be between 1 and 12

    if ($params["mode"] == "mode") {
        if (!array_key_exists("spaceID", $params)) {
            header("HTTP/1.0 400 Bad Request");
            die();
    
        }

        if ((int) $params["spaceID"] > 12 || (int) $params["spaceID"] < 1) {
            header("HTTP/1.0 400 Bad Request");
            die();
        }


    }

    //is there an "include" date range?
    if (array_key_exists("include", $params)) {
        $include = $params["include"];
        $includeChecked = checkConvertDates($include);
        if ($includeChecked === false) {
            header("HTTP/1.0 400 Bad Request");
            die();
         }
    } else {
        $includeChecked = array();
    }

    
    //if there are exclude dates, validate them.  Otherwise, create an empty array.
    if (array_key_exists("exclude", $params)) {
        $exclude = $params["exclude"];
        
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
    if ($params["mode"] == "average") {
        $result = $entry->getByDateAverages($includeChecked, $excludeChecked, $hoursIncludeChecked, $hoursExcludeChecked);
    } else {
        $result = $entry->getMode($includeChecked, $excludeChecked, $hoursIncludeChecked, $hoursExcludeChecked, (int) $params["spaceID"]);
    }
    if ($result === false) {

        if ($entry->errMsg == "No entries found") {
            echo json_encode("No entries found");
        } else {
            writeError("Cannot calculate: " . $entry->errMsg);
            
            header("HTTP/1.0 500 Internal Server Error");
        }
    } else {
        echo json_encode($result);
    }

    
    

} else {
    //this endpoint only accepts post requests
    header("HTTP/1.0 400 Bad Request");
    die();

}






?>
