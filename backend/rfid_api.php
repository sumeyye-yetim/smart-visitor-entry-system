<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

date_default_timezone_set('Europe/Istanbul');

require_once 'db.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST kabul edilir']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON']);
    exit;
}

$durum = $data['durum'] ?? '';
$uid = $data['uid'] ?? '';
$kaynak = $data['kaynak'] ?? 'rfid';

if (empty($durum) || empty($uid)) {
    echo json_encode(['success' => false, 'message' => 'Eksik veri']);
    exit;
}

// Kart bilgilerini veritabanından çek
$stmt = $conn->prepare("SELECT resident_name, apartment_no FROM rfid_cards WHERE uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();
$card = $result->fetch_assoc();
$stmt->close();

$entry_datetime = date('Y-m-d H:i:s');
$visitor_type = $durum === 'yetkili' ? 'Daire Sahibi' : 'Yetkisiz';
$visitor_name = $durum === 'yetkili' ? ($card ? $card['resident_name'] : 'Bilinmeyen Kart: ' . $uid) : 'Bilinmeyen Kart: ' . $uid;
$apartment_no = $card ? $card['apartment_no'] : '';
$host_name = '';
$plate = '';
$description = $durum === 'yetkili' ? 'Kartla giriş yapıldı' : 'YETKİSİZ GİRİŞ DENEMESİ';

$stmt = $conn->prepare("INSERT INTO visitors (entry_datetime, visitor_type, visitor_name, apartment_no, host_name, plate, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $entry_datetime, $visitor_type, $visitor_name, $apartment_no, $host_name, $plate, $description);

if ($stmt->execute()) {
    // Yetkisiz girişte e-posta gönder
    if ($durum === 'yetkisiz') {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sumeyyeyetim10@gmail.com';
            $mail->Password = 'nxgixsafzwekybsa';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('GMAIL_ADRESIN@gmail.com', 'Apartman Güvenlik Sistemi');
            $mail->addAddress('sumeyyeyetim10@gmail.com');

            $mail->Subject = '⚠️ YETKİSİZ GİRİŞ DENEMESİ - ' . date('d.m.Y H:i:s');
            $mail->Body = "
                <h2>⚠️ Yetkisiz Giriş Uyarısı</h2>
                <p><strong>Tarih/Saat:</strong> " . date('d.m.Y H:i:s') . "</p>
                <p><strong>Kart UID:</strong> " . $uid . "</p>
                <p><strong>Kaynak:</strong> " . $kaynak . "</p>
                <p>Bu kart sistemde kayıtlı değil!</p>
            ";
            $mail->isHTML(true);
            $mail->send();
        } catch (Exception $e) {
            // Mail gönderilemedi ama kayıt yine de yapıldı
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Kayıt eklendi',
        'durum' => $durum,
        'uid' => $uid,
        'name' => $visitor_name
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Kayıt hatası']);
}

$stmt->close();
$conn->close();
?>
