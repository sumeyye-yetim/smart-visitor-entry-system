<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: login.php");
  exit;
}

require "db.php";

$id = (int)($_GET["id"] ?? 0);
if ($id > 0) {
  $stmt = $conn->prepare("DELETE FROM rfid_cards WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: rfid_cards.php?deleted=1");
exit;
?>
