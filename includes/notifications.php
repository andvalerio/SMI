<?php
require_once 'db.php';

function addNotification($userId, $message) {
    $conn = db_connect();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $message);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
