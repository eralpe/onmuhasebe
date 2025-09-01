<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ön Muhasebe Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h1 class="text-center">Basit Ön Muhasebe Sistemi</h1>
    <div class="list-group">
        <a href="gelir_gider.php" class="list-group-item list-group-item-action">Gelir/Gider Yönetimi</a>
        <a href="kasa.php" class="list-group-item list-group-item-action">Kasa Yönetimi</a>
        <a href="kasalar.php" class="list-group-item list-group-item-action">Kasalar</a>
        <a href="kasa_transferleri.php" class="list-group-item list-group-item-action">Kasalar Arası Transfer</a>
        <a href="personel.php" class="list-group-item list-group-item-action">Personel Yönetimi</a>
        <a href="personel_odemeler.php" class="list-group-item list-group-item-action">Personel Ödemeleri</a>
        <a href="gunluk_satis.php" class="list-group-item list-group-item-action">Günlük Satışlar</a>
        <a href="stok.php" class="list-group-item list-group-item-action">Stok Yönetimi</a>
        <a href="toptancilar.php" class="list-group-item list-group-item-action">Toptancılar Yönetimi</a>
        <a href="toptanci_odemeler.php" class="list-group-item list-group-item-action">Toptancı Ödemeleri</a>
        <a href="faturalar.php" class="list-group-item list-group-item-action">Faturalar</a>
        <a href="kategoriler.php" class="list-group-item list-group-item-action">Kategori Yönetimi</a>
        <a href="rapor.php" class="list-group-item list-group-item-action">Raporlar</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger">Çıkış Yap</a>
    </div>
</body>
</html>