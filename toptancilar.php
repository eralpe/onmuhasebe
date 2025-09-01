<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $ad = $_POST['ad'];
    $adres = $_POST['adres'];
    $telefon = $_POST['telefon'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO toptancilar (ad, adres, telefon, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $ad, $adres, $telefon, $email);
    $stmt->execute();
    $stmt->close();
}

// Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $ad = $_POST['ad'];
    $adres = $_POST['adres'];
    $telefon = $_POST['telefon'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE toptancilar SET ad=?, adres=?, telefon=?, email=? WHERE id=?");
    $stmt->bind_param("ssssi", $ad, $adres, $telefon, $email, $id);
    $stmt->execute();
    $stmt->close();
}

// Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM toptancilar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Not: Bağlı stok kayıtları için toptanci_id NULL yapın veya kısıtlayın
    $conn->query("UPDATE stok SET toptanci_id = NULL WHERE toptanci_id = $id");
    header("Location: toptancilar.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM toptancilar WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM toptancilar");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toptancılar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Toptancılar Yönetimi</h2>
    <form method="post" class="mb-4">
        <?php if ($edit_row): ?>
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
            <input type="hidden" name="duzenle" value="1">
        <?php endif; ?>
        <div class="mb-3"><label>Ad:</label><input type="text" name="ad" class="form-control" value="<?php echo $edit_row['ad'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Adres:</label><input type="text" name="adres" class="form-control" value="<?php echo $edit_row['adres'] ?? ''; ?>"></div>
        <div class="mb-3"><label>Telefon:</label><input type="text" name="telefon" class="form-control" value="<?php echo $edit_row['telefon'] ?? ''; ?>"></div>
        <div class="mb-3"><label>Email:</label><input type="email" name="email" class="form-control" value="<?php echo $edit_row['email'] ?? ''; ?>"></div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ad</th><th>Adres</th><th>Telefon</th><th>Email</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['ad']; ?></td>
                <td><?php echo $row['adres']; ?></td>
                <td><?php echo $row['telefon']; ?></td>
                <td><?php echo $row['email']; ?></td>
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