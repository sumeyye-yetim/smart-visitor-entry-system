<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: visitor_list.php");
  exit;
}

require "db.php";

$res = $conn->query("SELECT * FROM rfid_cards ORDER BY created_at DESC");
?>

<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>RFID Kart Yönetimi</title>
  <style>
    body{font-family:Arial;padding:20px}
    .top{display:flex;justify-content:space-between;align-items:center}
    .btn{background:#0b5cff;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;border:none;cursor:pointer;font-size:14px}
    table{width:100%;border-collapse:collapse;margin-top:14px}
    th,td{border:1px solid #ddd;padding:10px}
    th{background:#f5f5f5}
    .top h2{font-size:40px;font-weight:700}
    .link-btn{border:none;background:transparent;padding:0;margin-right:10px;color:#0b5cff;cursor:pointer;font-weight:600;}
    .right-buttons{display:flex;flex-direction:column;gap:10px;margin-top:20px;}
    .logout-btn{background:#dc2626;}
    .back-btn{background:#6b7280;}
    .add-btn{background:#16a34a;}
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);justify-content:center;align-items:center;z-index:9999;}
    .modal-content{width:500px;max-width:90%;background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.25);}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
    .close{border:none;background:transparent;font-size:26px;cursor:pointer;}
    .modal label{display:block;margin-top:10px;font-weight:600}
    .modal input{width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;margin-top:6px;box-sizing:border-box;}
    .row{display:flex;gap:10px;margin-top:14px}
    .btn.ghost{background:#f2f2f2;color:#111;}
  </style>
</head>
<body>

<div class="top">
  <h2>RFID KART YÖNETİMİ</h2>
  <div class="right-buttons">
    <a href="logout.php" class="btn logout-btn">Çıkış</a>
    <a href="visitor_list.php" class="btn back-btn">← Ziyaretçi Listesi</a>
    <button class="btn add-btn" onclick="openModal()">+ Yeni Kart Ekle</button>
  </div>
</div>

<?php if(isset($_GET["ok"])): ?>
  <p style="color:green;">Kart eklendi ✅</p>
<?php endif; ?>

<?php if(isset($_GET["deleted"])): ?>
  <p style="color:#b00020;">Kart silindi 🗑️</p>
<?php endif; ?>

<?php if(isset($_GET["updated"])): ?>
  <p style="color:green;">Kart güncellendi ✅</p>
<?php endif; ?>

<?php if(($_GET["err"] ?? "") === "exists"): ?>
  <p style="color:#b00020;">Bu UID zaten kayıtlı!</p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Kart UID</th>
      <th>Sakin Adı</th>
      <th>Daire No</th>
      <th>Eklenme Tarihi</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php while ($r = $res->fetch_assoc()): ?>
    <tr>
      <td><?= $r["id"] ?></td>
      <td><?= htmlspecialchars($r["uid"]) ?></td>
      <td><?= htmlspecialchars($r["resident_name"]) ?></td>
      <td><?= htmlspecialchars($r["apartment_no"]) ?></td>
      <td><?= (new DateTime($r["created_at"]))->format("d.m.Y H:i") ?></td>
      <td>
        <button type="button" class="link-btn"
          onclick='openEditModal(<?= json_encode([
            "id" => (int)$r["id"],
            "uid" => $r["uid"],
            "resident_name" => $r["resident_name"],
            "apartment_no" => $r["apartment_no"]
          ], JSON_UNESCAPED_UNICODE) ?>)'>
          Düzenle
        </button>
        <a href="rfid_card_delete.php?id=<?= (int)$r["id"] ?>"
           onclick="return confirm('Bu kartı silmek istediğine emin misin?');"
           style="color:#b00020;text-decoration:none;font-weight:600;">
          Sil
        </a>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<!-- EKLE MODAL -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Yeni Kart Ekle</h3>
      <button class="close" onclick="closeModal()">×</button>
    </div>
    <form method="post" action="rfid_card_create.php">
      <label>Kart UID</label>
      <input type="text" name="uid" placeholder="Örn: 1D 1C F7 04" required>

      <label>Sakin Adı</label>
      <input type="text" name="resident_name" placeholder="Örn: Ahmet Yılmaz" required>

      <label>Daire No</label>
      <input type="number" name="apartment_no" placeholder="Örn: 12" required min="1">

      <div class="row">
        <button class="btn ghost" type="button" onclick="closeModal()">İptal</button>
        <button class="btn" type="submit">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- DÜZENLE MODAL -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Kartı Düzenle</h3>
      <button class="close" onclick="closeEditModal()">×</button>
    </div>
    <form method="post" action="rfid_card_update.php">
      <input type="hidden" name="id" id="edit_id">

      <label>Kart UID</label>
      <input type="text" name="uid" id="edit_uid" required>

      <label>Sakin Adı</label>
      <input type="text" name="resident_name" id="edit_resident_name" required>

      <label>Daire No</label>
      <input type="number" name="apartment_no" id="edit_apartment_no" required min="1">

      <div class="row">
        <button class="btn ghost" type="button" onclick="closeEditModal()">İptal</button>
        <button class="btn" type="submit">Güncelle</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById("addModal").style.display = "flex"; }
function closeModal(){ document.getElementById("addModal").style.display = "none"; }

function openEditModal(data){
  document.getElementById("edit_id").value = data.id;
  document.getElementById("edit_uid").value = data.uid;
  document.getElementById("edit_resident_name").value = data.resident_name;
  document.getElementById("edit_apartment_no").value = data.apartment_no;
  document.getElementById("editModal").style.display = "flex";
}
function closeEditModal(){ document.getElementById("editModal").style.display = "none"; }

document.addEventListener("click", function(e){
  if(e.target === document.getElementById("addModal")) closeModal();
  if(e.target === document.getElementById("editModal")) closeEditModal();
});
</script>

</body>
</html>
