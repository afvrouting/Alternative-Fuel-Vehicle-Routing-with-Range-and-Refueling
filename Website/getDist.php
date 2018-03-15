<?php

function getDist($lat, $lon, $point, $flag) {
    usleep(10000);
    $endpoint = 'http://dev.virtualearth.net/REST/v1/Routes?';
    $params = array(
    'key'=> 'AmQPhKl45GKdrmoOkZIsiUsL75DBRva9n3jfXmOkZnZNS23ZbKPSJa2O-9e3CeWU',
    'wp.0' => '',
    'wp.1' => '',
    'optimize' => 'time',
    'distanceUnit' => 'mi',


    );
    $station =  $lat . "," . $lon;
    $point = substr($point, 1,-1);
    if ($flag == "end") {
        $params['wp.0'] = $station;
        $params['wp.1'] = $point;
    } else {
        $params['wp.0'] = $point;
        $params['wp.1'] = $station;
    }
    $link = $endpoint . http_build_query($params);
    $json = file_get_contents($link);
    $data = json_decode($json);


        // If we got directions, output all of the HTML instructions
    if ($data->statusCode === 200) {
        $dist = (float) $data->resourceSets[0]->resources[0]->travelDistance;
        return $dist;
        #echo $row['id'] . " to " .  "end" . " is " .  $dist . "<br/>";
    }
    #else echo $data->statusCode;


}
function swap($point) {
    $point = str_replace(array("(", ")"), "", $point);
    $pointlist = explode(", ", $point);
    $point = "(" . $pointlist[1] . " " . $pointlist[0] . ")";
    return $point;
}
?>
