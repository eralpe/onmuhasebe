<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Fatura Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['duzenle'])) {
    $toptanci_id = $_POST['toptanci_id'];
    $fatura_no = $_POST['fatura_no'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];
    $urunler = $_POST['urunler'];
    $adetler = $_POST['adetler'];
    $birim_fiyatlar = $_POST['birim_fiyatlar'];

    // Fatura kaydı
    $stmt = $conn->prepare("INSERT INTO faturalar (toptanci_id, fatura_no, tutar, tarih, aciklama) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $toptanci_id, $fatura_no, $tutar, $tarih, $aciklama);
    $stmt->execute();
    $fatura_id = $conn->insert_id;
    $stmt->close();

    // Fatura kalemleri ve stok güncelleme
    for ($i = 0; $i < count($urunler); $i++) {
        $urun_adi = $urunler[$i];
        $adet = $adetler[$i];
        $birim_fiyat = $birim_fiyatlar[$i];

        // Fatura kalemi ekle
        $stmt = $conn->prepare("INSERT INTO fatura_kalemleri (fatura_id, urun_adi, adet, birim_fiyat) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isid", $fatura_id, $urun_adi, $adet, $birim_fiyat);
        $stmt->execute();
        $stmt->close();

        // Stok güncelle
        $check = $conn->query("SELECT * FROM stok WHERE urun_adi = '$urun_adi'");
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE stok SET adet = adet + ?, alim_fiyat = ? WHERE urun_adi = ?");
            $stmt->bind_param("ids", $adet, $birim_fiyat, $urun_adi);
        } else {
            $stmt = $conn->prepare("INSERT INTO stok (urun_adi, adet, alim_fiyat, satis_fiyat, toptanci_id) VALUES (?, ?, ?, 0.00, ?)");
            $stmt->bind_param("sidi", $urun_adi, $adet, $birim_fiyat, $toptanci_id);
        }
        $stmt->execute();
        $stmt->close();
    }

    // Gelir/Gider kaydı (gider olarak)
    $gider_aciklama = "Fatura: " . $fatura_no . " - " . $aciklama;
    $stmt = $conn->prepare("INSERT INTO gelir_gider (tur, aciklama, tutar, tarih) VALUES ('gider', ?, ?, ?)");
    $stmt->bind_param("sds", $gider_aciklama, $tutar, $tarih);
    $stmt->execute();
    $stmt->close();

    header("Location: faturalar.php");
    exit;
}

// Fatura Düzenleme
if (isset($_POST['duzenle'])) {
    $id = $_POST['id'];
    $toptanci_id = $_POST['toptanci_id'];
    $fatura_no = $_POST['fatura_no'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $aciklama = $_POST['aciklama'];
    $urunler = $_POST['urunler'];
    $adetler = $_POST['adetler'];
    $birim_fiyatlar = $_POST['birim_fiyatlar'];

    // Eski kalemleri ve stokları geri al
    $kalemler = $conn->query("SELECT urun_adi, adet FROM fatura_kalemleri WHERE fatura_id = $id");
    while ($kalem = $kalemler->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE stok SET adet = adet - ? WHERE urun_adi = ?");
        $stmt->bind_param("is", $kalem['adet'], $kalem['urun_adi']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->query("DELETE FROM fatura_kalemleri WHERE fatura_id = $id");

    // Fatura güncelle
    $stmt = $conn->prepare("UPDATE faturalar SET toptanci_id = ?, fatura_no = ?, tutar = ?, tarih = ?, aciklama = ? WHERE id = ?");
    $stmt->bind_param("isdssi", $toptanci_id, $fatura_no, $tutar, $tarih, $aciklama, $id);
    $stmt->execute();
    $stmt->close();

    // Yeni kalemler ve stok güncelleme
    for ($i = 0; $i < count($urunler); $i++) {
        $urun_adi = $urunler[$i];
        $adet = $adetler[$i];
        $birim_fiyat = $birim_fiyatlar[$i];

        // Fatura kalemi ekle
        $stmt = $conn->prepare("INSERT INTO fatura_kalemleri (fatura_id, urun_adi, adet, birim_fiyat) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isid", $id, $urun_adi, $adet, $birim_fiyat);
        $stmt->execute();
        $stmt->close();

        // Stok güncelle
        $check = $conn->query("SELECT * FROM stok WHERE urun_adi = '$urun_adi'");
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE stok SET adet = adet + ?, alim_fiyat = ? WHERE urun_adi = ?");
            $stmt->bind_param("ids", $adet, $birim_fiyat, $urun_adi);
        } else {
            $stmt = $conn->prepare("INSERT INTO stok (urun_adi, adet, alim_fiyat, satis_fiyat, toptanci_id) VALUES (?, ?, ?, 0.00, ?)");
            $stmt->bind_param("sidi", $urun_adi, $adet, $birim_fiyat, $toptanci_id);
        }
        $stmt->execute();
        $stmt->close();
    }

    header("Location: faturalar.php");
    exit;
}

// Fatura Silme
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Stokları geri al
    $kalemler = $conn->query("SELECT urun_adi, adet FROM fatura_kalemleri WHERE fatura_id = $id");
    while ($kalem = $kalemler->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE stok SET adet = adet - ? WHERE urun_adi = ?");
        $stmt->bind_param("is", $kalem['adet'], $kalem['urun_adi']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->query("DELETE FROM fatura_kalemleri WHERE fatura_id = $id");
    $stmt = $conn->prepare("DELETE FROM faturalar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: faturalar.php");
    exit;
}

// Düzenleme için veri çek
$edit_row = null;
$edit_kalemler = [];
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM faturalar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $kalemler_result = $conn->query("SELECT urun_adi, adet, birim_fiyat FROM fatura_kalemleri WHERE fatura_id = $id");
    while ($kalem = $kalemler_result->fetch_assoc()) {
        $edit_kalemler[] = $kalem;
    }
}

// Toptancı listesi
$toptancilar_result = $conn->query("SELECT id, ad FROM toptancilar");
$faturalar_result = $conn->query("SELECT f.id, f.toptanci_id, t.ad AS toptanci_ad, f.fatura_no, f.tutar, f.tarih, f.aciklama 
                                 FROM faturalar f 
                                 JOIN toptancilar t ON f.toptanci_id = t.id 
                                 ORDER BY f.tarih DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Faturalar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function addKalem() {
            const container = document.getElementById('kalemler');
            const div = document.createElement('div');
            div.className = 'row mb-2 kalem';
            div.innerHTML = `
                <div class="col-md-4"><input type="text" name="urunler[]" class="form-control" placeholder="Ürün Adı" required></div>
                <div class="col-md-3"><input type="number" name="adetler[]" class="form-control" placeholder="Adet" required></div>
                <div class="col-md-3"><input type="number" step="0.01" name="birim_fiyatlar[]" class="form-control" placeholder="Birim Fiyat" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.parentNode.remove()">Kaldır</button></div>
            `;
            container.appendChild(div);
        }

        function calculateTotal() {
            const birimFiyatlar = document.getElementsByName('birim_fiyatlar[]');
            const adetler = document.getElementsByName('adetler[]');
            let total = 0;
            for (let i = 0; i < birimFiyatlar.length; i++) {
                const birimFiyat = parseFloat(birimFiyatlar[i].value) || 0;
                const adet = parseInt(adetler[i].value) || 0;
                total += birimFiyat * adet;
            }
            document.getElementById('tutar').value = total.toFixed(2);
        }
    </script>
</head>
<body class="container mt-5">
    <h2>Faturalar</h2>
    <form method="post" class="mb-4">
        <?php if ($edit_row): ?>
            <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
            <input type="hidden" name="duzenle" value="1">
        <?php endif; ?>
        <div class="mb-3">
            <label>Toptancı:</label>
            <select name="toptanci_id" class="form-select" required>
                <?php while($toptanci = $toptancilar_result->fetch_assoc()): ?>
                    <option value="<?php echo $toptanci['id']; ?>" <?php if ($edit_row && $edit_row['toptanci_id'] == $toptanci['id']) echo 'selected'; ?>>
                        <?php echo $toptanci['ad']; ?>
                    </option>
                <?php endwhile; $toptancilar_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3"><label>Fatura No:</label><input type="text" name="fatura_no" class="form-control" value="<?php echo $edit_row['fatura_no'] ?? ''; ?>" required></div>
        <div class="mb-3"><label>Tutar:</label><input type="number" step="0.01" id="tutar" name="tutar" class="form-control" value="<?php echo $edit_row['tutar'] ?? ''; ?>" readonly></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" value="<?php echo $edit_row['tarih'] ?? date('Y-m-d'); ?>" required></div>
        <div class="mb-3"><label>Açıklama:</label><input type="text" name="aciklama" class="form-control" value="<?php echo $edit_row['aciklama'] ?? ''; ?>"></div>
        <div class="mb-3">
            <label>Fatura Kalemleri:</label>
            <div id="kalemler">
                <?php if ($edit_row && $edit_kalemler): ?>
                    <?php foreach ($edit_kalemler as $kalem): ?>
                        <div class="row mb-2 kalem">
                            <div class="col-md-4"><input type="text" name="urunler[]" class="form-control" value="<?php echo $kalem['urun_adi']; ?>" required></div>
                            <div class="col-md-3"><input type="number" name="adetler[]" class="form-control" value="<?php echo $kalem['adet']; ?>" required oninput="calculateTotal()"></div>
                            <div class="col-md-3"><input type="number" step="0.01" name="birim_fiyatlar[]" class="form-control" value="<?php echo $kalem['birim_fiyat']; ?>" required oninput="calculateTotal()"></div>
                            <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.parentNode.remove(); calculateTotal()">Kaldır</button></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row mb-2 kalem">
                        <div class="col-md-4"><input type="text" name="urunler[]" class="form-control" placeholder="Ürün Adı" required></div>
                        <div class="col-md-3"><input type="number" name="adetler[]" class="form-control" placeholder="Adet" required oninput="calculateTotal()"></div>
                        <div class="col-md-3"><input type="number" step="0.01" name="birim_fiyatlar[]" class="form-control" placeholder="Birim Fiyat" required oninput="calculateTotal()"></div>
                        <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.parentNode.remove(); calculateTotal()">Kaldır</button></div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-secondary mt-2" onclick="addKalem()">Kalem Ekle</button>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_row ? 'Güncelle' : 'Ekle'; ?></button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Toptancı</th><th>Fatura No</th><th>Tutar</th><th>Tarih</th><th>Açıklama</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $faturalar_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['toptanci_ad']; ?></td>
                <td><?php echo $row['fatura_no']; ?></td>
                <td><?php echo $row['tutar']; ?> TL</td>
                <td><?php echo $row['tarih']; ?></td>
                <td><?php echo $row['aciklama']; ?></td>
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