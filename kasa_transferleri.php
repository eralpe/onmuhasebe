<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Transfer Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $kaynak_kasa_id = $_POST['kaynak_kasa_id'];
    $hedef_kasa_id = $_POST['hedef_kasa_id'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];
    $para_birimi = $_POST['para_birimi'];

    if ($kaynak_kasa_id == $hedef_kasa_id) {
        $_SESSION['error'] = "Kaynak ve hedef kasa aynı olamaz.";
        header("Location: kasa_transferleri.php");
        exit;
    }

    // Kaynak kasanın bakiyesini kontrol et
    $stmt = $conn->prepare("SELECT SUM(CASE WHEN hareket = 'giris' THEN tutar ELSE -tutar END) AS bakiye 
                            FROM kasa WHERE kasa_id = ? AND para_birimi = ?");
    $stmt->bind_param("is", $kaynak_kasa_id, $para_birimi);
    $stmt->execute();
    $bakiye = $stmt->get_result()->fetch_assoc()['bakiye'] ?? 0;
    $stmt->close();
    if ($bakiye < $tutar) {
        $_SESSION['error'] = "Kaynak kasada yeterli bakiye yok.";
        header("Location: kasa_transferleri.php");
        exit;
    }

    // Kaynak kasadan çıkış
    $kasa_aciklama = "Kasa Transferi: " . $aciklama;
    $stmt = $conn->prepare("INSERT INTO kasa (kasa_id, hareket, aciklama, tutar, tarih, para_birimi) VALUES (?, 'cikis', ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $kaynak_kasa_id, $kasa_aciklama, $tutar, $tarih, $para_birimi);
    $stmt->execute();
    $kaynak_kasa_hareket_id = $conn->insert_id;
    $stmt->close();

    // Hedef kasaya giriş
    $stmt = $conn->prepare("INSERT INTO kasa (kasa_id, hareket, aciklama, tutar, tarih, para_birimi) VALUES (?, 'giris', ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $hedef_kasa_id, $kasa_aciklama, $tutar, $tarih, $para_birimi);
    $stmt->execute();
    $hedef_kasa_hareket_id = $conn->insert_id;
    $stmt->close();

    // Transfer kaydı
    $stmt = $conn->prepare("INSERT INTO kasa_transferleri (kaynak_kasa_id, hedef_kasa_id, tutar, tarih, aciklama, para_birimi, kaynak_kasa_hareket_id, hedef_kasa_hareket_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsisii", $kaynak_kasa_id, $hedef_kasa_id, $tutar, $tarih, $aciklama, $para_birimi, $kaynak_kasa_hareket_id, $hedef_kasa_hareket_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Transfer başarıyla kaydedildi.";
    } else {
        $_SESSION['error'] = "Transfer kaydedilirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasa_transferleri.php");
    exit;
}

// Transfer Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $kaynak_kasa_id = $_POST['kaynak_kasa_id'];
    $hedef_kasa_id = $_POST['hedef_kasa_id'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];
    $para_birimi = $_POST['para_birimi'];

    if ($kaynak_kasa_id == $hedef_kasa_id) {
        $_SESSION['error'] = "Kaynak ve hedef kasa aynı olamaz.";
        header("Location: kasa_transferleri.php");
        exit;
    }

    // Kaynak kasanın bakiyesini kontrol et
    $stmt = $conn->prepare("SELECT SUM(CASE WHEN hareket = 'giris' THEN tutar ELSE -tutar END) AS bakiye 
                            FROM kasa WHERE kasa_id = ? AND para_birimi = ?");
    $stmt->bind_param("is", $kaynak_kasa_id, $para_birimi);
    $stmt->execute();
    $bakiye = $stmt->get_result()->fetch_assoc()['bakiye'] ?? 0;
    $stmt->close();
    if ($bakiye < $tutar) {
        $_SESSION['error'] = "Kaynak kasada yeterli bakiye yok.";
        header("Location: kasa_transferleri.php");
        exit;
    }

    // Eski kayıtları bul
    $stmt = $conn->prepare("SELECT kaynak_kasa_hareket_id, hedef_kasa_hareket_id FROM kasa_transferleri WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transfer = $result->fetch_assoc();
    $kaynak_kasa_hareket_id = $transfer['kaynak_kasa_hareket_id'];
    $hedef_kasa_hareket_id = $transfer['hedef_kasa_hareket_id'];
    $stmt->close();

    // Kasa hareketlerini güncelle
    $kasa_aciklama = "Kasa Transferi: " . $aciklama;
    $stmt = $conn->prepare("UPDATE kasa SET kasa_id = ?, aciklama = ?, tutar = ?, tarih = ?, para_birimi = ? WHERE id = ?");
    $stmt->bind_param("isdssi", $kaynak_kasa_id, $kasa_aciklama, $tutar, $tarih, $para_birimi, $kaynak_kasa_hareket_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE kasa SET kasa_id = ?, aciklama = ?, tutar = ?, tarih = ?, para_birimi = ? WHERE id = ?");
    $stmt->bind_param("isdssi", $hedef_kasa_id, $kasa_aciklama, $tutar, $tarih, $para_birimi, $hedef_kasa_hareket_id);
    $stmt->execute();
    $stmt->close();

    // Transfer kaydını güncelle
    $stmt = $conn->prepare("UPDATE kasa_transferleri SET kaynak_kasa_id = ?, hedef_kasa_id = ?, tutar = ?, tarih = ?, aciklama = ?, para_birimi = ? WHERE id = ?");
    $stmt->bind_param("iidsisi", $kaynak_kasa_id, $hedef_kasa_id, $tutar, $tarih, $aciklama, $para_birimi, $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Transfer başarıyla güncellendi.";
    } else {
        $_SESSION['error'] = "Transfer güncellenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasa_transferleri.php");
    exit;
}

// Transfer Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("SELECT kaynak_kasa_hareket_id, hedef_kasa_hareket_id FROM kasa_transferleri WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transfer = $result->fetch_assoc();
    $stmt->close();

    // Kasa hareketlerini sil
    $stmt = $conn->prepare("DELETE FROM kasa WHERE id = ?");
    $stmt->bind_param("i", $transfer['kaynak_kasa_hareket_id']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM kasa WHERE id = ?");
    $stmt->bind_param("i", $transfer['hedef_kasa_hareket_id']);
    $stmt->execute();
    $stmt->close();

    // Transfer kaydını sil
    $stmt = $conn->prepare("DELETE FROM kasa_transferleri WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Transfer başarıyla silindi.";
    } else {
        $_SESSION['error'] = "Transfer silinirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasa_transferleri.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM kasa_transferleri WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Kasa ve para birimi listeleri
$kasalar_result = $conn->query("SELECT id, ad, kategori FROM kasalar");
$para_birimleri_result = $conn->query("SELECT kod, ad FROM para_birimleri");
$transferler_result = $conn->query("SELECT kt.id, k1.ad AS kaynak_kasa, k2.ad AS hedef_kasa, kt.tutar, kt.tarih, kt.aciklama, kt.para_birimi, pb.tl_kuru 
                                   FROM kasa_transferleri kt 
                                   JOIN kasalar k1 ON kt.kaynak_kasa_id = k1.id 
                                   JOIN kasalar k2 ON kt.hedef_kasa_id = k2.id 
                                   JOIN para_birimleri pb ON kt.para_birimi = pb.kod 
                                   ORDER BY kt.tarih DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kasalar Arası Transfer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Kasalar Arası Transfer</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <form method="post" class="mb-4">
        <?php if ($edit_row): ?>
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
            <input type="hidden" name="duzenle" value="1">
        <?php endif; ?>
        <div class="mb-3">
            <label>Kaynak Kasa:</label>
            <select name="kaynak_kasa_id" class="form-select" required>
                <?php while($kasa = $kasalar_result->fetch_assoc()): ?>
                    <option value="<?php echo $kasa['id']; ?>" <?php if ($edit_row && $edit_row['kaynak_kasa_id'] == $kasa['id']) echo 'selected'; ?>>
                        <?php echo $kasa['ad'] . ' (' . $kasa['kategori'] . ')'; ?>
                    </option>
                <?php endwhile; $kasalar_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Hedef Kasa:</label>
            <select name="hedef_kasa_id" class="form-select" required>
                <?php while($kasa = $kasalar_result->fetch_assoc()): ?>
                    <option value="<?php echo $kasa['id']; ?>" <?php if ($edit_row && $edit_row['hedef_kasa_id'] == $kasa['id']) echo 'selected'; ?>>
                        <?php echo $kasa['ad'] . ' (' . $kasa['kategori'] . ')'; ?>
                    </option>
                <?php endwhile; $kasalar_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Para Birimi:</label>
            <select name="para_birimi" class="form-select" required>
                <?php while($pb = $para_birimleri_result->fetch_assoc()): ?>
                    <option value="<?php echo $pb['kod']; ?>" <?php if ($edit_row && $edit_row['para_birimi'] == $pb['kod']) echo 'selected'; ?>>
                        <?php echo $pb['ad']; ?>
                    </option>
                <?php endwhile; $para_birimleri_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3"><label>Tutar:</label><input type="number" step="0.01" name="tutar" class="form-control" value="<?php echo $edit_row['tutar'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" value="<?php echo $edit_row['tarih'] ?? date('Y-m-d'); ?>" required></div>
        <div class="mb-3"><label>Açıklama:</label><input type="text" name="aciklama" class="form-control" value="<?php echo $edit_row['aciklama'] ?? ''; ?>"></div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Kaynak Kasa</th><th>Hedef Kasa</th><th>Tutar</th><th>Para Birimi</th><th>TL Karşılığı</th><th>Tarih</th><th>Açıklama</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $transferler_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['kaynak_kasa']; ?></td>
                <td><?php echo $row['hedef_kasa']; ?></td>
                <td><?php echo number_format($row['tutar'], 2); ?> <?php echo $row['para_birimi']; ?></td>
                <td><?php echo $row['para_birimi']; ?></td>
                <td><?php echo number_format($row['tutar'] * $row['tl_kuru'], 2); ?> TL</td>
                <td><?php echo $row['tarih']; ?></td>
                <td><?php echo $row['aciklama'] ?: 'Yok'; ?></td>
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