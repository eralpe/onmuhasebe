<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    // Kullanıcıyı sorgula
    $stmt = $conn->prepare("SELECT id, sifre FROM kullanicilar WHERE kullanici_adi = ?");
    if (!$stmt) {
        die("Sorgu hatası: " . $conn->error);
    }
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($sifre, $user['sifre'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Geçersiz kullanıcı adı veya şifre.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Giriş Yap</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Kullanıcı Adı:</label>
            <input type="text" name="kullanici_adi" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Şifre:</label>
            <input type="password" name="sifre" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Giriş</button>
    </form>
</body>
</html>