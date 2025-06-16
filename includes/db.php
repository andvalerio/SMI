<?php
$CONTENT_FOLDER = "../../assets/content/";
$max_file_size = 50 * 1024 * 1024; // 50 MB
$allowed_extensions_photo = ["jpg", "jpeg", "png", "gif"];
$allowed_extensions_video = ["mp4", "mov"];

function db_connect()
{
    $xml = simplexml_load_file(__DIR__ . '/../config.xml') or die("No XML file found");

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

function hasUser($conn, $username, $email) {
    // Verificar se o utilizador já existe
    $stmt = $conn->prepare("SELECT id FROM user WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return false;
    } else {
        $stmt->close();
        return true;
    }
}