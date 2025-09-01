<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $ad = $_POST['ad'];
    $aciklama = $_POST['aciklama'];
    $kategori = $_POST['kategori'];

    $stmt = $conn->prepare("INSERT INTO kasalar (ad, aciklama, kategori) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $ad, $aciklama, $kategori);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kasa başarıyla eklendi.";
    } else {
        $_SESSION['error'] = "Kasa eklenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasalar.php");
    exit;
}

// Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $ad = $_POST['ad'];
    $aciklama = $_POST['aciklama'];
    $kategori = $_POST['kategori'];

    $stmt = $conn->prepare("UPDATE kasalar SET ad = ?, aciklama = ?, kategori = ? WHERE id = ?");
    $stmt->bind_param("sssi", $ad, $aciklama, $kategori, $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kasa başarıyla güncellendi.";
    } else {
        $_SESSION['error'] = "Kasa güncellenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasalar.php");
    exit;
}

// Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) AS hareket_sayisi FROM kasa WHERE kasa_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result['hareket_sayisi'] > 0) {
        $_SESSION['error'] = "Bu kasaya ait hareketler bulunduğu için silinemez.";
    } else {
        $stmt = $conn->prepare("DELETE FROM kasalar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kasa başarıyla silindi.";
        } else {
            $_SESSION['error'] = "Kasa silinirken hata oluştu.";
        }
    }
    $stmt->close();
    header("Location: kasalar.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM kasalar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM kasalar ORDER BY kategori, ad");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kasalar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Kasa Yönetimi</h2>
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
            <label>Ad:</label>
            <input type="text" name="ad" class="form-control" value="<?php echo $edit_row['ad'] ?? ''; ?>" required>
        </div>
        <div class="mb-3">
            <label>Kategori:</label>
            <select name="kategori" class="form-select" required>
                <option value="Nakit" <?php if ($edit_row && $edit_row['kategori'] == 'Nakit') echo 'selected'; ?>>Nakit</option>
                <option value="Banka" <?php if ($edit_row && $edit_row['kategori'] == 'Banka') echo 'selected'; ?>>Banka</option>
                <option value="Dijital" <?php if ($edit_row && $edit_row['kategori'] == 'Dijital') echo 'selected'; ?>>Dijital</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Açıklama:</label>
            <input type="text" name="aciklama" class="form-control" value="<?php echo $edit_row['aciklama'] ?? ''; ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ad</th><th>Kategori</th><th>Açıklama</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['ad']; ?></td>
                <td><?php echo $row['kategori']; ?></td>
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