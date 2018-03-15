<?php
class dbconnect{
    function connect()
    {  $con = pg_connect("host=localhost dbname=afvrouti_map user=afvrouti password=Q.)-wLpY");    if (!$con)     { die('Could not connect: ' .  pg_last_error());  }}}?>