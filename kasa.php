<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM kasa WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: kasa.php");
    exit;
}

// Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kasa_id = $_POST['kasa_id'];
    $hareket = $_POST['hareket'];
    $aciklama = $_POST['aciklama'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $para_birimi = $_POST['para_birimi'];

    $stmt = $conn->prepare("INSERT INTO kasa (kasa_id, hareket, aciklama, tutar, tarih, para_birimi) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdss", $kasa_id, $hareket, $aciklama, $tutar, $tarih, $para_birimi);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Hareket başarıyla eklendi.";
    } else {
        $_SESSION['error'] = "Hareket eklenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: kasa.php");
    exit;
}

// Filtreleme için kasa_id ve para_birimi
$kasa_filtre = isset($_GET['kasa_id']) ? $_GET['kasa_id'] : '';
$para_birimi_filtre = isset($_GET['para_birimi']) ? $_GET['para_birimi'] : '';
$where = [];
if ($kasa_filtre) $where[] = "k.kasa_id = ?";
if ($para_birimi_filtre) $where[] = "k.para_birimi = ?";
$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT k.*, ks.ad AS kasa_ad, ks.kategori, pb.kod AS para_birimi_kod, pb.tl_kuru 
          FROM kasa k 
          JOIN kasalar ks ON k.kasa_id = ks.id 
          JOIN para_birimleri pb ON k.para_birimi = pb.kod 
          $where_clause 
          ORDER BY k.tarih DESC";
$stmt = $conn->prepare($query);
if ($kasa_filtre && $para_birimi_filtre) {
    $stmt->bind_param("is", $kasa_filtre, $para_birimi_filtre);
} elseif ($kasa_filtre) {
    $stmt->bind_param("i", $kasa_filtre);
} elseif ($para_birimi_filtre) {
    $stmt->bind_param("s", $para_birimi_filtre);
}
$stmt->execute();
$result = $stmt->get_result();

// Kasa bakiyeleri
$bakiyeler = [];
$bakiye_result = $conn->query("SELECT k.kasa_id, ks.ad AS kasa_ad, k.hareket, k.tutar, k.para_birimi, pb.tl_kuru 
                               FROM kasa k 
                               JOIN kasalar ks ON k.kasa_id = ks.id 
                               JOIN para_birimleri pb ON k.para_birimi = pb.kod");
while ($row = $bakiye_result->fetch_assoc()) {
    $kasa_id = $row['kasa_id'];
    $para_birimi = $row['para_birimi'];
    if (!isset($bakiyeler[$kasa_id])) {
        $bakiyeler[$kasa_id] = ['ad' => $row['kasa_ad'], 'bakiyeler' => []];
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

// Kasa ve para birimi listeleri
$kasalar_result = $conn->query("SELECT id, ad, kategori FROM kasalar");
$para_birimleri_result = $conn->query("SELECT kod, ad FROM para_birimleri");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kasa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Kasa Hareketleri</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>Kasa Filtresi:</label>
                <select name="kasa_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Tüm Kasalar</option>
                    <?php while($kasa = $kasalar_result->fetch_assoc()): ?>
                        <option value="<?php echo $kasa['id']; ?>" <?php if ($kasa_filtre == $kasa['id']) echo 'selected'; ?>>
                            <?php echo $kasa['ad'] . ' (' . $kasa['kategori'] . ')'; ?>
                        </option>
                    <?php endwhile; $kasalar_result->data_seek(0); ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Para Birimi Filtresi:</label>
                <select name="para_birimi" class="form-select" onchange="this.form.submit()">
                    <option value="">Tüm Para Birimleri</option>
                    <?php while($pb = $para_birimleri_result->fetch_assoc()): ?>
                        <option value="<?php echo $pb['kod']; ?>" <?php if ($para_birimi_filtre == $pb['kod']) echo 'selected'; ?>>
                            <?php echo $pb['ad']; ?>
                        </option>
                    <?php endwhile; $para_birimleri_result->data_seek(0); ?>
                </select>
            </div>
        </div>
    </form>
    <h3>Kasa Bakiyeleri</h3>
    <ul class="list-group mb-4">
        <?php foreach ($bakiyeler as $kasa): ?>
            <?php foreach ($kasa['bakiyeler'] as $pb => $bakiye): ?>
                <li class="list-group-item">
                    <?php echo $kasa['ad']; ?> (<?php echo $pb; ?>): 
                    <?php echo number_format($bakiye['tutar'], 2); ?> <?php echo $pb; ?> 
                    (TL Karşılığı: <?php echo number_format($bakiye['tutar'] * $bakiye['tl_kuru'], 2); ?> TL)
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
    <form method="post" class="mb-4">
        <div class="mb-3">
            <label>Kasa:</label>
            <select name="kasa_id" class="form-select" required>
                <?php $kasalar_result->data_seek(0); while($kasa = $kasalar_result->fetch_assoc()): ?>
                    <option value="<?php echo $kasa['id']; ?>"><?php echo $kasa['ad'] . ' (' . $kasa['kategori'] . ')'; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Para Birimi:</label>
            <select name="para_birimi" class="form-select" required>
                <?php $para_birimleri_result->data_seek(0); while($pb = $para_birimleri_result->fetch_assoc()): ?>
                    <option value="<?php echo $pb['kod']; ?>"><?php echo $pb['ad']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Hareket:</label>
            <select name="hareket" class="form-select">
                <option value="giris">Giriş</option>
                <option value="cikis">Çıkış</option>
            </select>
        </div>
        <div class="mb-3"><label>Açıklama:</label><input type="text" name="aciklama" class="form-control" required></div>
        <div class="mb-3"><label>Tutar:</label><input type="number" step="0.01" name="tutar" class="form-control" required></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Kasa</th><th>Kategori</th><th>Hareket</th><th>Açıklama</th><th>Tutar</th><th>Para Birimi</th><th>TL Karşılığı</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['kasa_ad']; ?></td>
                <td><?php echo $row['kategori']; ?></td>
                <td><?php echo $row['hareket']; ?></td>
                <td><?php echo $row['aciklama']; ?></td>
                <td><?php echo number_format($row['tutar'], 2); ?> <?php echo $row['para_birimi_kod']; ?></td>
                <td><?php echo $row['para_birimi_kod']; ?></td>
                <td><?php echo number_format($row['tutar'] * $row['tl_kuru'], 2); ?> TL</td>
                <td><?php echo $row['tarih']; ?></td>
                <td><a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinizden emin misiniz?')">Sil</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>