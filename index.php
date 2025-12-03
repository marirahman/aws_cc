<?php
// --- BAGIAN 1: KONEKSI DATABASE ---
$host = "localhost";
$user = "admin_tugas";   // Sesuaikan user database kamu
$pass = "password123";   // Sesuaikan password database kamu
$db   = "db_tugas";      // Sesuaikan nama database

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Gagal Konek: " . mysqli_connect_error()); }

// --- BAGIAN 2: LOGIKA PENYIMPANAN (PROSES BELI) ---
$pesan_sukses = false;
if (isset($_POST['btn_konfirmasi'])) {
    $nama   = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $barang = $_POST['nama_barang'];
    $harga  = $_POST['harga'];

    // Simpan ke Tabel Pesanan
    $simpan = mysqli_query($koneksi, "INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga) VALUES ('$nama', '$alamat', '$barang', '$harga')");
    
    if ($simpan) {
        $pesan_sukses = true; // Tandai kalau sukses
    } else {
        echo "<script>alert('Gagal: ".mysqli_error($koneksi)."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Commerce AWS Sederhana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-primary mb-4 shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php"><b>TOKO CLOUD COMPUTING</b></a>
            <span class="text-white">Ari - Teknik Informatika</span>
        </div>
    </nav>

    <div class="container">

        <?php 
        // --- BAGIAN 3: TAMPILAN PESAN SUKSES ---
        if ($pesan_sukses) { 
        ?>
            <div class="alert alert-success text-center shadow p-5">
                <h1>✅ Transaksi Berhasil!</h1>
                <p class="lead">Pesanan atas nama <b><?php echo $nama; ?></b> akan segera diproses.</p>
                <a href="index.php" class="btn btn-primary btn-lg mt-3">Kembali Belanja</a>
            </div>

        <?php 
        // --- BAGIAN 4: TAMPILAN FORM CHECKOUT (Jika tombol beli diklik) ---
        } elseif (isset($_GET['page']) && $_GET['page'] == 'checkout') { 
            $id = $_GET['id'];
            $ambil = mysqli_query($koneksi, "SELECT * FROM produk WHERE id='$id'");
            $data = mysqli_fetch_array($ambil);
        ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Konfirmasi Pembelian</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Produk: <b><?php echo $data['nama_barang']; ?></b><br>
                                Harga: <b>Rp <?php echo number_format($data['harga']); ?></b>
                            </div>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="nama_barang" value="<?php echo $data['nama_barang']; ?>">
                                <input type="hidden" name="harga" value="<?php echo $data['harga']; ?>">
                                
                                <div class="mb-3">
                                    <label>Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" required placeholder="Nama Anda">
                                </div>
                                <div class="mb-3">
                                    <label>Alamat Pengiriman</label>
                                    <textarea name="alamat" class="form-control" rows="3" required placeholder="Alamat Lengkap"></textarea>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="btn_konfirmasi" class="btn btn-primary">BAYAR SEKARANG</button>
                                    <a href="index.php" class="btn btn-secondary">Batal</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php 
        // --- BAGIAN 5: TAMPILAN DAFTAR PRODUK (Halaman Utama) ---
        } else { 
        ?>
            <h3 class="mb-4 text-center">Katalog Produk</h3>
            <div class="row">
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM produk");
                while($data = mysqli_fetch_array($query)){
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo $data['gambar']; ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $data['nama_barang']; ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo $data['deskripsi']; ?></p>
                            <h4 class="text-primary mt-2">Rp <?php echo number_format($data['harga']); ?></h4>
                            <a href="index.php?page=checkout&id=<?php echo $data['id']; ?>" class="btn btn-success w-100 mt-2">Beli Sekarang</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } ?>

        <footer class="text-center mt-5 mb-5 text-muted">
            <small>Dibuat Menggunakan AWS EC2 (Native LAMP Stack)</small>
        </footer>
    </div>
</body>
</html>