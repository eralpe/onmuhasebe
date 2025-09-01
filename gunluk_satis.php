<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Silme işlemi (stok geri yükleme ile)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $satis = $conn->query("SELECT urun, adet FROM gunluk_satis WHERE id = $id")->fetch_assoc();
    $urun = $satis['urun'];
    $adet = $satis['adet'];

    // Stok geri yükle
    $stmt = $conn->prepare("UPDATE stok SET adet = adet + ? WHERE urun_adi = ?");
    $stmt->bind_param("is", $adet, $urun);
    $stmt->execute();
    $stmt->close();

    // Satışı sil
    $stmt = $conn->prepare("DELETE FROM gunluk_satis WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gunluk_satis.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $urun = $_POST['urun'];
    $adet = $_POST['adet'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];

    $stmt = $conn->prepare("INSERT INTO gunluk_satis (urun, adet, tutar, tarih) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sids", $urun, $adet, $tutar, $tarih);
    $stmt->execute();
    $stmt->close();

    // Stok güncelle
    $stok_stmt = $conn->prepare("UPDATE stok SET adet = adet - ? WHERE urun_adi = ?");
    $stok_stmt->bind_param("is", $adet, $urun);
    $stok_stmt->execute();
    $stok_stmt->close();
}

$result = $conn->query("SELECT * FROM gunluk_satis ORDER BY tarih DESC");
$urunler_result = $conn->query("SELECT urun_adi, satis_fiyat FROM stok");

// Günlük toplam
$today = date('Y-m-d');
$toplam_result = $conn->query("SELECT SUM(tutar) AS toplam FROM gunluk_satis WHERE tarih = '$today'");
$toplam = $toplam_result->fetch_assoc()['toplam'] ?? 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Günlük Satışlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Ürün seçildiğinde tutarı otomatik doldur
        function updateTutar() {
            var urun = document.querySelector('select[name="urun"]').value;
            var urunler = <?php echo json_encode($urunler_result->fetch_all(MYSQLI_ASSOC)); ?>;
            var selected = urunler.find(u => u.urun_adi === urun);
            if (selected) {
                document.querySelector('input[name="tutar"]').value = selected.satis_fiyat;
            } else {
                document.querySelector('input[name="tutar"]').value = '';
            }
        }
    </script>
</head>
<body class="container mt-5" onload="updateTutar()">
    <h2>Günlük Satışlar (Bugün Toplam: <?php echo $toplam; ?> TL)</h2>
    <form method="post" class="mb-4">
        <div class="mb-3">
            <label>Ürün:</label>
            <select name="urun" class="form-select" onchange="updateTutar()" required>
                <?php $urunler_result->data_seek(0); while($urun = $urunler_result->fetch_assoc()): ?>
                    <option value="<?php echo $urun['urun_adi']; ?>"><?php echo $urun['urun_adi']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3"><label>Adet:</label><input type="number" name="adet" class="form-control" required></div>
        <div class="mb-3"><label>Tutar (Otomatik Satış Fiyatı):</label><input type="number" step="0.01" name="tutar" class="form-control" required></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ürün</th><th>Adet</th><th>Tutar</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['urun']; ?></td>
                <td><?php echo $row['adet']; ?></td>
                <td><?php echo $row['tutar']; ?></td>
                <td><?php echo $row['tarih']; ?></td>
                <td><a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinizden emin misiniz?')">Sil</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>