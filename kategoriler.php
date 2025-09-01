<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $ad = $_POST['ad'];
    $tur = $_POST['tur'];

    $stmt = $conn->prepare("INSERT INTO kategoriler (ad, tur) VALUES (?, ?)");
    $stmt->bind_param("ss", $ad, $tur);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori başarıyla eklendi.";
    } else {
        $_SESSION['error'] = "Kategori eklenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kategoriler.php");
    exit;
}

// Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $ad = $_POST['ad'];
    $tur = $_POST['tur'];

    $stmt = $conn->prepare("UPDATE kategoriler SET ad = ?, tur = ? WHERE id = ?");
    $stmt->bind_param("ssi", $ad, $tur, $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori başarıyla güncellendi.";
    } else {
        $_SESSION['error'] = "Kategori güncellenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kategoriler.php");
    exit;
}

// Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Gelir/Gider kayıtlarındaki kategori_id'leri NULL yap
    $stmt = $conn->prepare("UPDATE gelir_gider SET kategori_id = NULL WHERE kategori_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM kategoriler WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori başarıyla silindi.";
    } else {
        $_SESSION['error'] = "Kategori silinirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kategoriler.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM kategoriler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM kategoriler ORDER BY tur, ad");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kategoriler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Kategori Yönetimi</h2>
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
            <label>Tür:</label>
            <select name="tur" class="form-select" required>
                <option value="gelir" <?php if ($edit_row && $edit_row['tur'] == 'gelir') echo 'selected'; ?>>Gelir</option>
                <option value="gider" <?php if ($edit_row && $edit_row['tur'] == 'gider') echo 'selected'; ?>>Gider</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ad</th><th>Tür</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['ad']; ?></td>
                <td><?php echo $row['tur']; ?></td>
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