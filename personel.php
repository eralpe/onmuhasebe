<?php
include 'db.php';
session_start(); if (!isset($_SESSION['user_id'])) { header('Location: login.php'); }

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM personel WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: personel.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_soyad = $_POST['ad_soyad'];
    $pozisyon = $_POST['pozisyon'];
    $maas = $_POST['maas'];

    $stmt = $conn->prepare("INSERT INTO personel (ad_soyad, pozisyon, maas) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $ad_soyad, $pozisyon, $maas);
    $stmt->execute();
    $stmt->close();
}

$result = $conn->query("SELECT * FROM personel");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Personel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Personel Yönetimi</h2>
    <form method="post" class="mb-4">
        <div class="mb-3"><label>Ad Soyad:</label><input type="text" name="ad_soyad" class="form-control" required></div>
        <div class="mb-3"><label>Pozisyon:</label><input type="text" name="pozisyon" class="form-control"></div>
        <div class="mb-3"><label>Maaş:</label><input type="number" step="0.01" name="maas" class="form-control" required></div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Ad Soyad</th><th>Pozisyon</th><th>Maaş</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['ad_soyad']; ?></td>
                <td><?php echo $row['pozisyon']; ?></td>
                <td><?php echo $row['maas']; ?></td>
                <td><a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinizden emin misiniz?')">Sil</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>