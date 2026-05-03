<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: login.php");
  exit;
}

require "db.php";

$uid = trim($_POST["uid"] ?? "");
$resident_name = trim($_POST["resident_name"] ?? "");
$apartment_no = trim($_POST["apartment_no"] ?? "");

if (empty($uid) || empty($resident_name) || empty($apartment_no)) {
  header("Location: rfid_cards.php?err=empty");
  exit;
}

// UID zaten var mı?
$check = $conn->prepare("SELECT id FROM rfid_cards WHERE uid = ?");
$check->bind_param("s", $uid);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
  header("Location: rfid_cards.php?err=exists");
  exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO rfid_cards (uid, resident_name, apartment_no) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $uid, $resident_name, $apartment_no);
$stmt->execute();
$stmt->close();

header("Location: rfid_cards.php?ok=1");
exit;
?>
