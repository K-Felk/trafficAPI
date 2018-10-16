<?

//shared functiuon to write errors
function writeError($errMsg) {
    $date = date("D M d, Y G:i");
    $handle = fopen("../error.log", "a");
    fwrite($handle, $date . "  " . $errMsg . "\n");
    fclose($handle);

}

?>