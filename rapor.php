<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Varsayılan tarih aralığı: son 30 gün
$baslangic = isset($_GET['baslangic']) ? $_GET['baslangic'] : date('Y-m-d', strtotime('-30 days'));
$bitis = isset($_GET['bitis']) ? $_GET['bitis'] : date('Y-m-d');

// Gelir/Gider Raporu (Kategori bazlı)
$gelir_gider_result = $conn->prepare("SELECT k.ad AS kategori_ad, gg.tur, SUM(gg.tutar) AS toplam 
                                      FROM gelir_gider gg 
                                      LEFT JOIN kategoriler k ON gg.kategori_id = k.id 
                                      WHERE gg.tarih BETWEEN ? AND ? 
                                      GROUP BY gg.kategori_id, gg.tur");
$gelir_gider_result->bind_param("ss", $baslangic, $bitis);
$gelir_gider_result->execute();
$gelir_gider_result = $gelir_gider_result->get_result();
$gelir = 0;
$gider = 0;
$kategori_raporu = [];
while ($row = $gelir_gider_result->fetch_assoc()) {
    $kategori_raporu[] = $row;
    if ($row['tur'] == 'gelir') $gelir += $row['toplam'];
    else $gider += $row['toplam'];
}

// Kasa Raporu
$bakiyeler = [];
$kasa_result = $conn->query("SELECT k.kasa_id, ks.ad AS kasa_ad, ks.kategori, k.hareket, k.tutar, k.para_birimi, pb.tl_kuru 
                             FROM kasa k 
                             JOIN kasalar ks ON k.kasa_id = ks.id 
                             JOIN para_birimleri pb ON k.para_birimi = pb.kod 
                             WHERE k.tarih BETWEEN '$baslangic' AND '$bitis'");
while ($row = $kasa_result->fetch_assoc()) {
    $kasa_id = $row['kasa_id'];
    $para_birimi = $row['para_birimi'];
    if (!isset($bakiyeler[$kasa_id])) {
        $bakiyeler[$kasa_id] = ['ad' => $row['kasa_ad'], 'kategori' => $row['kategori'], 'bakiyeler' => []];
    }
    if (!isset($bakiyeler[$kasa_id]['bakiyeler'][$para_birimi])) {
        $bakiyeler[$kasa_id]['bakiyeler'][$para_birimi] = ['tutar' => 0, 'tl_kuru' => $row['tl_kuru']];
    }
    if ($row['hareket'] == 'giris') {
        $bakiyeler[$kasa_id]['bakiyeler'][$para_birimi]['tutar'] += $row['tutar'];
    } else {
        $bakiyeler[$kasa_id]['bakiyeler'][$para_birimi]['tutar'] -= $row['tutar'];
    }
}

// Kasa Transferleri Raporu
$transferler_result = $conn->query("SELECT k1.ad AS kaynak_kasa, k2.ad AS hedef_kasa, kt.tutar, kt.para_birimi, pb.tl_kuru 
                                    FROM kasa_transferleri kt 
                                    JOIN kasalar k1 ON kt.kaynak_kasa_id = k1.id 
                                    JOIN kasalar k2 ON kt.hedef_kasa_id = k2.id 
                                    JOIN para_birimleri pb ON kt.para_birimi = pb.kod 
                                    WHERE kt.tarih BETWEEN '$baslangic' AND '$bitis'");
$transferler_toplam = [];
$transferler = [];
while ($row = $transferler_result->fetch_assoc()) {
    $transferler[] = $row;
    $pb = $row['para_birimi'];
    if (!isset($transferler_toplam[$pb])) $transferler_toplam[$pb] = ['tutar' => 0, 'tl_kuru' => $row['tl_kuru']];
    $transferler_toplam[$pb]['tutar'] += $row['tutar'];
}

// Günlük Satış Raporu
$gunluk_satis_result = $conn->prepare("SELECT SUM(tutar) AS toplam FROM gunluk_satis WHERE tarih BETWEEN ? AND ?");
$gunluk_satis_result->bind_param("ss", $baslangic, $bitis);
$gunluk_satis_result->execute();
$gunluk_satis_toplam = $gunluk_satis_result->get_result()->fetch_assoc()['toplam'] ?? 0;

// Stok Raporu
$stok_result = $conn->query("SELECT s.urun_adi, s.adet, s.alim_fiyat, s.satis_fiyat, t.ad AS toptanci_ad, (s.adet * s.satis_fiyat) AS toplam_deger 
                             FROM stok s 
                             LEFT JOIN toptancilar t ON s.toptanci_id = t.id");
$stok_toplam_deger = 0;
while ($row = $stok_result->fetch_assoc()) {
    $stok_toplam_deger += $row['toplam_deger'];
}
$stok_result->data_seek(0);

// Toptancı Ödemeleri Raporu
$toptanci_odemeler_result = $conn->prepare("SELECT t.ad AS toptanci_ad, SUM(to.tutar) AS toplam 
                                           FROM toptanci_odemeler to 
                                           JOIN toptancilar t ON to.toptanci_id = t.id 
                                           WHERE to.tarih BETWEEN ? AND ? 
                                           GROUP BY t.id");
$toptanci_odemeler_result->bind_param("ss", $baslangic, $bitis);
$toptanci_odemeler_result->execute();
$toptanci_odemeler_result = $toptanci_odemeler_result->get_result();
$toptanci_odemeler_toplam = 0;
while ($row = $toptanci_odemeler_result->fetch_assoc()) {
    $toptanci_odemeler_toplam += $row['toplam'];
}
$toptanci_odemeler_result->data_seek(0);

// Faturalar Raporu
$faturalar_result = $conn->prepare("SELECT t.ad AS toptanci_ad, SUM(f.tutar) AS toplam 
                                    FROM faturalar f 
                                    JOIN toptancilar t ON f.toptanci_id = t.id 
                                    WHERE f.tarih BETWEEN ? AND ? 
                                    GROUP BY t.id");
$faturalar_result->bind_param("ss", $baslangic, $bitis);
$faturalar_result->execute();
$faturalar_result = $faturalar_result->get_result();
$faturalar_toplam = 0;
while ($row = $faturalar_result->fetch_assoc()) {
    $faturalar_toplam += $row['toplam'];
}
$faturalar_result->data_seek(0);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Raporlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Raporlar</h2>
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Başlangıç Tarihi:</label>
                <input type="date" name="baslangic" class="form-control" value="<?php echo $baslangic; ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Bitiş Tarihi:</label>
                <input type="date" name="bitis" class="form-control" value="<?php echo $bitis; ?>" required>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </div>
        </div>
    </form>
    <h3>Özet</h3>
    <ul class="list-group mb-4">
        <li class="list-group-item">Toplam Gelir: <?php echo number_format($gelir, 2); ?> TL</li>
        <li class="list-group-item">Toplam Gider: <?php echo number_format($gider, 2); ?> TL</li>
        <li class="list-group-item">Günlük Satış Toplamı: <?php echo number_format($gunluk_satis_toplam, 2); ?> TL</li>
        <li class="list-group-item">Toptancı Ödemeleri Toplamı: <?php echo number_format($toptanci_odemeler_toplam, 2); ?> TL</li>
        <li class="list-group-item">Faturalar Toplamı: <?php echo number_format($faturalar_toplam, 2); ?> TL</li>
        <li class="list-group-item">Toplam Stok Değeri: <?php echo number_format($stok_toplam_deger, 2); ?> TL</li>
    </ul>
    <h3>Kasa Bakiyeleri</h3>
    <table class="table table-striped">
        <thead><tr><th>Kasa</th><th>Kategori</th><th>Para Birimi</th><th>Bakiye</th><th>TL Karşılığı</th></tr></thead>
        <tbody>
            <?php foreach ($bakiyeler as $kasa): ?>
                <?php foreach ($kasa['bakiyeler'] as $pb => $bakiye): ?>
                <tr>
                    <td><?php echo $kasa['ad']; ?></td>
                    <td><?php echo $kasa['kategori']; ?></td>
                    <td><?php echo $pb; ?></td>
                    <td><?php echo number_format($bakiye['tutar'], 2); ?> <?php echo $pb; ?></td>
                    <td><?php echo number_format($bakiye['tutar'] * $bakiye['tl_kuru'], 2); ?> TL</td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Kategori Bazlı Gelir/Gider</h3>
    <table class="table table-striped">
        <thead><tr><th>Kategori</th><th>Tür</th><th>Toplam</th></tr></thead>
        <tbody>
            <?php foreach($kategori_raporu as $row): ?>
            <tr>
                <td><?php echo $row['kategori_ad'] ?? 'Kategorisiz'; ?></td>
                <td><?php echo $row['tur']; ?></td>
                <td><?php echo number_format($row['toplam'], 2); ?> TL</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Kasa Transferleri</h3>
    <table class="table table-striped">
        <thead><tr><th>Kaynak Kasa</th><th>Hedef Kasa</th><th>Tutar</th><th>Para Birimi</th><th>TL Karşılığı</th></tr></thead>
        <tbody>
            <?php foreach($transferler as $row): ?>
            <tr>
                <td><?php echo $row['kaynak_kasa']; ?></td>
                <td><?php echo $row['hedef_kasa']; ?></td>
                <td><?php echo number_format($row['tutar'], 2); ?> <?php echo $row['para_birimi']; ?></td>
                <td><?php echo $row['para_birimi']; ?></td>
                <td><?php echo number_format($row['tutar'] * $row['tl_kuru'], 2); ?> TL</td>
            </tr>
            <?php endforeach; ?>
            <?php foreach($transferler_toplam as $pb => $toplam): ?>
            <tr>
                <th colspan="2">Toplam (<?php echo $pb; ?>)</th>
                <td><?php echo number_format($toplam['tutar'], 2); ?> <?php echo $pb; ?></td>
                <td><?php echo $pb; ?></td>
                <td><?php echo number_format($toplam['tutar'] * $toplam['tl_kuru'], 2); ?> TL</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Stok Durumu</h3>
    <table class="table table-striped">
        <thead><tr><th>Ürün Adı</th><th>Adet</th><th>Alım Fiyat</th><th>Satış Fiyat</th><th>Toptancı</th><th>Toplam Değer (Satış Bazlı)</th></tr></thead>
        <tbody>
            <?php while($row = $stok_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['urun_adi']; ?></td>
                <td><?php echo $row['adet']; ?></td>
                <td><?php echo number_format($row['alim_fiyat'], 2); ?> TL</td>
                <td><?php echo number_format($row['satis_fiyat'], 2); ?> TL</td>
                <td><?php echo $row['toptanci_ad'] ?? 'Yok'; ?></td>
                <td><?php echo number_format($row['toplam_deger'], 2); ?> TL</td>
            </tr>
            <?php endwhile; ?>
            <tr><th colspan="5">Toplam Stok Değeri</th><td><?php echo number_format($stok_toplam_deger, 2); ?> TL</td></tr>
        </tbody>
    </table>
    <h3>Toptancı Ödemeleri</h3>
    <table class="table table-striped">
        <thead><tr><th>Toptancı</th><th>Toplam Ödeme</th></tr></thead>
        <tbody>
            <?php while($row = $toptanci_odemeler_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['toptanci_ad']; ?></td>
                <td><?php echo number_format($row['toplam'], 2); ?> TL</td>
            </tr>
            <?php endwhile; ?>
            <tr><th>Toplam</th><td><?php echo number_format($toptanci_odemeler_toplam, 2); ?> TL</td></tr>
        </tbody>
    </table>
    <h3>Faturalar</h3>
    <table class="table table-striped">
        <thead><tr><th>Toptancı</th><th>Toplam Fatura Tutarı</th></tr></thead>
        <tbody>
            <?php while($row = $faturalar_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['toptanci_ad']; ?></td>
                <td><?php echo number_format($row['toplam'], 2); ?> TL</td>
            </tr>
            <?php endwhile; ?>
            <tr><th>Toplam</th><td><?php echo number_format($faturalar_toplam, 2); ?> TL</td></tr>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>