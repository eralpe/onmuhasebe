<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM stok WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: stok.php");
    exit;
}

// Ekleme/Güncelleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $urun_adi = $_POST['urun_adi'];
    $adet = $_POST['adet'];
    $alim_fiyat = $_POST['alim_fiyat'];
    $satis_fiyat = $_POST['satis_fiyat'];
    $toptanci_id = $_POST['toptanci_id'] ?: null;

    $check = $conn->query("SELECT * FROM stok WHERE urun_adi = '$urun_adi'");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE stok SET adet = adet + ?, alim_fiyat = ?, satis_fiyat = ?, toptanci_id = ? WHERE urun_adi = ?");
        $stmt->bind_param("iddis", $adet, $alim_fiyat, $satis_fiyat, $toptanci_id, $urun_adi);
    } else {
        $stmt = $conn->prepare("INSERT INTO stok (urun_adi, adet, alim_fiyat, satis_fiyat, toptanci_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siddi", $urun_adi, $adet, $alim_fiyat, $satis_fiyat, $toptanci_id);
    }
    $stmt->execute();
    $stmt->close();
}

// Düzenleme için veri çek (tam düzenleme için edit_id ekle)
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM stok WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // Eğer düzenleme ise, POST'ta id ile update yap
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Yukarıdaki update'i id bazlı yap (ürün adı değişebilir)
        $stmt = $conn->prepare("UPDATE stok SET urun_adi = ?, adet = ?, alim_fiyat = ?, satis_fiyat = ?, toptanci_id = ? WHERE id = ?");
        $stmt->bind_param("siddii", $urun_adi, $adet, $alim_fiyat, $satis_fiyat, $toptanci_id, $id);
        $stmt->execute();
        $stmt->close();
    }
}

$result = $conn->query("SELECT s.*, t.ad AS toptanci_ad FROM stok s LEFT JOIN toptancilar t ON s.toptanci_id = t.id");
$toptancilar_result = $conn->query("SELECT id, ad FROM toptancilar");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Stok</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Stok Yönetimi</h2>
    <form method="post" class="mb-4">
        <?php if ($edit_row): ?>
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
        <?php endif; ?>
        <div class="mb-3"><label>Ürün Adı:</label><input type="text" name="urun_adi" class="form-control" value="<?php echo $edit_row['urun_adi'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Adet (Giriş):</label><input type="number" name="adet" class="form-control" value="<?php echo $edit_row['adet'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Alım Fiyatı:</label><input type="number" step="0.01" name="alim_fiyat" class="form-control" value="<?php echo $edit_row['alim_fiyat'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Satış Fiyatı:</label><input type="number" step="0.01" name="satis_fiyat" class="form-control" value="<?php echo $edit_row['satis_fiyat'] ?? ''; ?>" required></div>
        <div class="mb-3">
            <label>Toptancı:</label>
            <select name="toptanci_id" class="form-select">
                <option value="">Seçiniz</option>
                <?php while($toptanci = $toptancilar_result->fetch_assoc()): ?>
                    <option value="<?php echo $toptanci['id']; ?>" <?php if ($edit_row && $edit_row['toptanci_id'] == $toptanci['id']) echo 'selected'; ?>>
                        <?php echo $toptanci['ad']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle/Güncelle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ürün Adı</th><th>Adet</th><th>Alım Fiyat</th><th>Satış Fiyat</th><th>Toptancı</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['urun_adi']; ?></td>
                <td><?php echo $row['adet']; ?></td>
                <td><?php echo $row['alim_fiyat']; ?> TL</td>
                <td><?php echo $row['satis_fiyat']; ?> TL</td>
                <td><?php echo $row['toptanci_ad'] ?? 'Yok'; ?></td>
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