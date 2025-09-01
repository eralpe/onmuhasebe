<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
include 'db.php';

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM gelir_gider WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gelir_gider.php");
    exit;
}

// Ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tur = $_POST['tur'];
    $aciklama = $_POST['aciklama'];
    $tutar = $_POST['tutar'];
    $tarih = $_POST['tarih'];
    $kategori_id = $_POST['kategori_id'] ?: null;

    $stmt = $conn->prepare("INSERT INTO gelir_gider (tur, aciklama, tutar, tarih, kategori_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $tur, $aciklama, $tutar, $tarih, $kategori_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kayıt başarıyla eklendi.";
    } else {
        $_SESSION['error'] = "Kayıt eklenirken hata oluştu.";
    }
    $stmt->close();
    header("Location: gelir_gider.php");
    exit;
}

// Filtreleme için sorgu
$kategori_filtre = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
$where = $kategori_filtre ? "WHERE kategori_id = ?" : "";
$query = "SELECT gg.*, k.ad AS kategori_ad FROM gelir_gider gg LEFT JOIN kategoriler k ON gg.kategori_id = k.id $where ORDER BY gg.tarih DESC";
$stmt = $conn->prepare($query);
if ($kategori_filtre) {
    $stmt->bind_param("i", $kategori_filtre);
}
$stmt->execute();
$result = $stmt->get_result();
$kategoriler_result = $conn->query("SELECT id, ad, tur FROM kategoriler");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelir/Gider</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Tür seçildiğinde kategorileri filtrele
        function updateKategoriler() {
            var tur = document.querySelector('select[name="tur"]').value;
            var kategoriSelect = document.querySelector('select[name="kategori_id"]');
            var kategoriler = <?php
                $kats = [];
                while ($kat = $kategoriler_result->fetch_assoc()) {
                    $kats[] = $kat;
                }
                $kategoriler_result->data_seek(0);
                echo json_encode($kats);
            ?>;
            kategoriSelect.innerHTML = '<option value="">Kategori Seçiniz</option>';
            kategoriler.forEach(k => {
                if (k.tur === tur) {
                    var option = document.createElement('option');
                    option.value = k.id;
                    option.textContent = k.ad;
                    kategoriSelect.appendChild(option);
                }
            });
        }
    </script>
</head>
<body class="container mt-5">
    <h2>Gelir/Gider Kayıtları</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>Kategori Filtresi:</label>
                <select name="kategori_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Tüm Kategoriler</option>
                    <?php while($kategori = $kategoriler_result->fetch_assoc()): ?>
                        <option value="<?php echo $kategori['id']; ?>" <?php if ($kategori_filtre == $kategori['id']) echo 'selected'; ?>>
                            <?php echo $kategori['ad'] . ' (' . $kategori['tur'] . ')'; ?>
                        </option>
                    <?php endwhile; $kategoriler_result->data_seek(0); ?>
                </select>
            </div>
        </div>
    </form>
    <form method="post" class="mb-4">
        <div class="mb-3">
            <label>Tür:</label>
            <select name="tur" class="form-select" onchange="updateKategoriler()" required>
                <option value="gelir">Gelir</option>
                <option value="gider">Gider</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Kategori:</label>
            <select name="kategori_id" class="form-select">
                <option value="">Kategori Seçiniz</option>
                <?php while($kategori = $kategoriler_result->fetch_assoc()): ?>
                    <option value="<?php echo $kategori['id']; ?>"><?php echo $kategori['ad']; ?></option>
                <?php endwhile; $kategoriler_result->data_seek(0); ?>
            </select>
        </div>
        <div class="mb-3"><label>Açıklama:</label><input type="text" name="aciklama" class="form-control" required></div>
        <div class="mb-3"><label>Tutar:</label><input type="number" step="0.01" name="tutar" class="form-control" required></div>
        <div class="mb-3"><label>Tarih:</label><input type="date" name="tarih" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        <button type="submit" class="btn btn-primary">Ekle</button>
    </form>
    <table class="table table-striped">
        <thead><tr><th>ID</th><th>Tür</th><th>Kategori</th><th>Açıklama</th><th>Tutar</th><th>Tarih</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['tur']; ?></td>
                <td><?php echo $row['kategori_ad'] ?? 'Yok'; ?></td>
                <td><?php echo $row['aciklama']; ?></td>
                <td><?php echo $row['tutar']; ?> TL</td>
                <td><?php echo $row['tarih']; ?></td>
                <td><a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğinizden emin misiniz?')">Sil</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Geri</a>
</body>
</html>