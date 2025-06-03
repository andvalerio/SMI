<?php
function db_connect()
{
    $xml = simplexml_load_file("config.xml") or die("No XML file found");

    $host = (string) $xml->database->host;
    $port = (int) $xml->database->port;
    $username = (string) $xml->database->username;
    $password = (string) $xml->database->password;
    $dbname = (string) $xml->database->dbname;

    $conn = new mysqli($host, $username, $password, $dbname, $port);
    if ($conn->connect_error) {
        die("Error: " . $conn->connect_error);
    }
    return $conn;
}

function insert_image_in_database() {}
