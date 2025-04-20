<?php
$user_id = $_GET['id'];  
include_once "connect_ddb.php";  
$sql = "DELETE FROM users WHERE IdUsers = :id";  
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $user_id]);

if ($stmt) {
    header("location:admin.php?message=DeleteSuccess");
} else {
    header("location:admin.php?message=DeleteFail");
}
?>
