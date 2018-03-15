<?php
include("dbconnect.php");
include("getDist.php");
$con = new dbconnect();
$con->connect();


$sql = "SELECT ST_AsText(geom),lat,lon,id FROM evstations";
$stations1 = pg_query($sql);
while ($row = pg_fetch_array($stations1)) {
   $fromid = $row[id];
   if ($fromid < 44252+1)
   {
       echo ".";
       continue;
   }
   else {
   echo "~";
   $from = '(' . $row[lat] . ',' . $row[lon] . ')';

   $geom = $row[0];
   $sql =  "select lat, lon, id from evstations where ST_DWithin(ST_GeomFromText('$geom', 4326), evstations.geom, 643738)";
   $stations2 = pg_query($sql);
   while ($row2 = pg_fetch_array($stations2)) {
        $dist = getDist($row2['lat'], $row2['lon'], $from, "start");
        $sql = "INSERT INTO evnetwork (source,target,cost) VALUES ($row[id], $row2[id], $dist)";
        $result = pg_query($sql);
        echo $sql;
    }
echo $fromid;
}}



?>
