<?php

if (!isset($_GET['to']))
    header("refresh:0;url=index.html");
include("dbconnect.php");
include("getDist.php");
require("Dijkstra.php");
$con = new dbconnect();
$con->connect();
$g = new Graph();


#Get User variables
$to = $_GET["to"];
$from = $_GET["from"];
$range = $_GET['range'] * 1609;

$startrange = $_GET['init'] * $range / 100;
$endrange = $range;
if($_GET['round'] == 'true')
    $endrange = ($range / 2);


$rangemi = $_GET['range'];
    $endpoint = 'http://dev.virtualearth.net/REST/v1/Routes?';
    $params = array(
    'key'=> 'AmQPhKl45GKdrmoOkZIsiUsL75DBRva9n3jfXmOkZnZNS23ZbKPSJa2O-9e3CeWU',
    'wp.0' => substr($to,1,-1),
    'wp.1' => substr($from,1,-1),
    'optimize' => 'time',
    'distanceUnit' => 'mi',


    );

    
    $link = $endpoint . http_build_query($params);
    $json = file_get_contents($link);
    $data = json_decode($json);


        // If we got directions, output all of the HTML instructions
    if ($data->statusCode === 200) {
    $mindist = (float) $data->resourceSets[0]->resources[0]->travelDistance;
    $mintime = $data->resourceSets[0]->resources[0]->travelDuration;

#echo "The path with no stations is " . $mindist . " miles long<br/><h1>Start to Stations</h1>";
//need to switch from lat lon to lon lat
$to_pg = swap($to);
$from_pg = swap($from);
#var_dump($bblist);
$miny = $data->resourceSets[0]->resources[0]->bbox[0];
$maxy= $data->resourceSets[0]->resources[0]->bbox[2];
$minx = $data->resourceSets[0]->resources[0]->bbox[1];
$maxx = $data->resourceSets[0]->resources[0]->bbox[3];



$bb = $minx . " " . $miny . "," . $minx . " " . $maxy . "," . $maxx . " " . $maxy . "," . $minx . " " . $miny;
    }
#echo $bb;
// execute query for start
$sql = "select lat, lon, id from stations where ST_DWithin(ST_transform(ST_GeomFromText('POINT$from_pg', 4326), 3857),stations.geom, $startrange) order by ST_Distance(ST_transform(ST_GeomFromText('POINT$to_pg', 4326), 3857),stations.geom) asc limit 60";
$result = pg_query($sql);

if (!$result) {
    die("Error in SQL query 1: " . pg_last_error());
}
// iterate over result set
// print each row
while ($row = pg_fetch_array($result)) {
    $dist = getDist($row['lat'], $row['lon'], $from, "start");
    $g->addedge("start", $row['id'], $dist);
    
    #echo "start" . " to " .  $row['id'] . " is " .  $dist . "<br/>";
}


// free memory
pg_free_result($result);


//execute query for end.
$sql = "select lat, lon, id from stations where ST_DWithin(ST_transform(ST_GeomFromText('POINT$to_pg', 4326), 3857),stations.geom, $endrange) order by ST_Distance(ST_transform(ST_GeomFromText('POINT$to_pg', 4326), 3857),stations.geom) desc limit 60";
$result = pg_query($sql);

if (!$result) {
    die("Error in SQL query2: " . pg_last_error());
}
// iterate over result set
// print each row
while ($row = pg_fetch_array($result)) {
    $dist = getDist($row['lat'], $row['lon'], $to, "end");
    $g->addedge($row['id'], "end", $dist);
}

pg_free_result($result);

//execute query for links in the middle
$sql = "SELECT network.source, network.target, network.cost FROM network, stations WHERE network.source = stations.id AND ST_DWithin(ST_transform(ST_GeomFromText('Polygon(($bb))', 4326), 3857),stations.geom, $range*6) AND network.cost < $rangemi";
$result = pg_query($sql);
if (!$result) {
    die("Error in SQL query for middle: " . pg_last_error());
}
// iterate over result set
// print each row
while ($row = pg_fetch_array($result)) {
    $g->addedge($row['source'], $row['target'], $row['cost']);
    #echo $row['source'] . " to " .  $row['target'] . " is " .  $row['cost'] . "<br/>";
}
list($distances, $prev) = $g->paths_from("start");
$path = $g->paths_to($prev, "end");


$dom = new DOMDocument("1.0");
$node = $dom->createElement("stations");
$parnode = $dom->appendChild($node);
header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
for ($i = 1; $i < count($path) - 1; $i++) {
    // ADD TO XML DOCUMENT NODE
    $sql = "select lat, lon, id, stationnam, city from stations where id = $path[$i]";
    $result = pg_query($sql);
    if (!$result) {
        die("Error in SQL query for middle: " . pg_last_error());
    }
    $row = pg_fetch_array($result);

    $node = $dom->createElement("station");
    $newnode = $parnode->appendChild($node);
    $newnode->setAttribute("stationnam", $row['stationnam']);
    $newnode->setAttribute("address", $row['address']);
    $newnode->setAttribute("lat", $row['lat']);
    $newnode->setAttribute("lon", $row['lon']);
    #$newnode->setAttribute("type", $row['type']);
}
$node = $dom->createElement("data");
$node->setAttribute("time", $mintime);
$node->setAttribute("dist", $mindist);

echo $dom->saveXML();

#echo "<h1>Final Shortest Path</h1>";
#var_dump($path);





?>