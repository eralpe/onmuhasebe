<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Ödeme Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $toptanci_id = $_POST['toptanci_id'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];

    // Gelir/Gider kaydı (gider olarak)
    $gider_aciklama = "Toptancı Ödemesi: " . $aciklama;
    $stmt = $conn->prepare("INSERT INTO gelir_gider (tur, aciklama, tutar, tarih) VALUES ('gider', ?, ?, ?)");
    $stmt->bind_param("sds", $gider_aciklama, $tutar, $tarih);
    $stmt->execute();
    $gelir_gider_id = $conn->insert_id;
    $stmt->close();

    // Kasa kaydı (çıkış olarak)
    $kasa_aciklama = "Toptancı Ödemesi: " . $aciklama;
    $stmt = $conn->prepare("INSERT INTO kasa (hareket, aciklama, tutar, tarih) VALUES ('cikis', ?, ?, ?)");
    $stmt->bind_param("sds", $kasa_aciklama, $tutar, $tarih);
    $stmt->execute();
    $kasa_id = $conn->insert_id;
    $stmt->close();

    // Toptancı ödemesi kaydı
    $stmt = $conn->prepare("INSERT INTO toptanci_odemeler (toptanci_id, tutar, tarih, aciklama, gelir_gider_id, kasa_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssii", $toptanci_id, $tutar, $tarih, $aciklama, $gelir_gider_id, $kasa_id);
    $stmt->execute();
    $stmt->close();

    header("Location: toptanci_odemeler.php");
    exit;
}

// Ödeme Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $toptanci_id = $_POST['toptanci_id'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];

    // Eski kayıtları bul
    $stmt = $conn->prepare("SELECT gelir_gider_id, kasa_id FROM toptanci_odemeler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $odeme = $result->fetch_assoc();
    $gelir_gider_id = $odeme['gelir_gider_id'];
    $kasa_id = $odeme['kasa_id'];
    $stmt->close();

    // Gelir/Gider güncelle
    $gider_aciklama = "Toptancı Ödemesi: " . $aciklama;
    $stmt = $conn->prepare("UPDATE gelir_gider SET aciklama = ?, tutar = ?, tarih = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $gider_aciklama, $tutar, $tarih, $gelir_gider_id);
    $stmt->execute();
    $stmt->close();

    // Kasa güncelle
    $kasa_aciklama = "Toptancı Ödemesi: " . $aciklama;
    $stmt = $conn->prepare("UPDATE kasa SET aciklama = ?, tutar = ?, tarih = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $kasa_aciklama, $tutar, $tarih, $kasa_id);
    $stmt->execute();
    $stmt->close();

    // Toptancı ödemesi güncelle
    $stmt = $conn->prepare("UPDATE toptanci_odemeler SET toptanci_id = ?, tutar = ?, tarih = ?, aciklama = ? WHERE id = ?");
    $stmt->bind_param("idssi", $toptanci_id, $tutar, $tarih, $aciklama, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: toptanci_odemeler.php");
    exit;
}

// Ödeme Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("SELECT gelir_gider_id, kasa_id FROM toptanci_odemeler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $odeme = $result->fetch_assoc();
    $stmt->close();

    // Gelir/Gider ve Kasa kayıtlarını sil
    $stmt = $conn->prepare("DELETE FROM gelir_gider WHERE id = ?");
    $stmt->bind_param("i", $odeme['gelir_gider_id']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM kasa WHERE id = ?");
    $stmt->bind_param("i", $odeme['kasa_id']);
    $stmt->execute();
    $stmt->close();

    // Toptancı ödemesini sil
    $stmt = $conn->prepare("DELETE FROM toptanci_odemeler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: toptanci_odemeler.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM toptanci_odemeler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Toptancı listesi
$toptancilar_result = $conn->query("SELECT id, ad FROM toptancilar");
$odemeler_result = $conn->query("SELECT to.id, to.toptanci_id, t.ad AS toptanci_ad, to.tutar, to.tarih, to.aciklama 
                                 FROM toptanci_odemeler to 
                                 JOIN toptancilar t ON to.toptanci_id = t.id 
                                 ORDER BY to.tarih DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toptancı Ödemeleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Toptancı Ödemeleri</h2>
    <form method="post" class="mb-4">
        <?php if ($edit_row): ?>
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
            <input type="hidden" name="duzenle" value="1">
        <?php endif; ?>
        <div class="mb-3">
            <label>Toptancı:</label>
            <select name="toptanci_id" class="form-select" required>
                <?php while($toptanci = $toptancilar_result->fetch_assoc()): ?>
                    <option value="<?php echo $toptanci['id']; ?>" <?php if ($edit_row && $edit_row['toptanci_id'] == $toptanci['id']) echo 'selected'; ?>>
                        <?php echo $toptanci['ad']; ?>
                    </option>
                <?php endwhile; $toptancilar_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3"><label>Tutar:</label><input type="number" step="0.01" name="tutar" class="form-control" value="<?php echo $edit_row['tutar'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" value="<?php echo $edit_row['tarih'] ?? date('Y-m-d'); ?>" required></div>
        <div class="mb-3"><label>Açıklama:</label><input type="text" name="aciklama" class="form-control" value="<?php echo $edit_row['aciklama'] ?? ''; ?>"></div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Toptancı</th><th>Tutar</th><th>Tarih</th><th>Açıklama</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $odemeler_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['toptanci_ad']; ?></td>
                <td><?php echo $row['tutar']; ?> TL</td>
                <td><?php echo $row['tarih']; ?></td>
                <td><?php echo $row['aciklama']; ?></td>
                <td>
                    <a href="?edit_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                    <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinizden emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>