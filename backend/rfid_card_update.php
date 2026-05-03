<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: login.php");
  exit;
}

require "db.php";

$id = (int)($_POST["id"] ?? 0);
$uid = trim($_POST["uid"] ?? "");
$resident_name = trim($_POST["resident_name"] ?? "");
$apartment_no = trim($_POST["apartment_no"] ?? "");

if ($id <= 0 || empty($uid) || empty($resident_name) || empty($apartment_no)) {
  header("Location: rfid_cards.php?err=empty");
  exit;
}

$stmt = $conn->prepare("UPDATE rfid_cards SET uid = ?, resident_name = ?, apartment_no = ? WHERE id = ?");
$stmt->bind_param("sssi", $uid, $resident_name, $apartment_no, $id);
$stmt->execute();
$stmt->close();

header("Location: rfid_cards.php?updated=1");
exit;
?>
