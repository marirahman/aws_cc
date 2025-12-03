<?php
// --- BAGIAN 1: KONEKSI DATABASE ---
 $host = "localhost";
 $user = "admin_tugas";   // Sesuaikan user database kamu
 $pass = "Admin12345";   // Sesuaikan password database kamu
 $db   = "db_tugas";      // Sesuaikan nama database

 $koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Gagal Konek: " . mysqli_connect_error()); }

// --- BAGIAN 2: INISIALISASI KERANJANG ---
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// --- BAGIAN 3: LOGIKA PENYIMPANAN (PROSES BELI) ---
 $pesan_sukses = false;
if (isset($_POST['btn_konfirmasi'])) {
    $nama   = $_POST['nama'];
    $alamat = $_POST['alamat'];
    
    // Simpan ke Tabel Pesanan
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['harga'] * $item['quantity'];
    }
    
    // Simpan setiap item dalam keranjang ke pesanan
    foreach ($_SESSION['cart'] as $item) {
        $simpan = mysqli_query($koneksi, "INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga, quantity) VALUES ('$nama', '$alamat', '{$item['nama_barang']}', '{$item['harga']}', '{$item['quantity']}')");
    }
    
    if ($simpan) {
        $pesan_sukses = true;
        // Kosongkan keranjang setelah checkout berhasil
        $_SESSION['cart'] = array();
    } else {
        echo "<script>alert('Gagal: ".mysqli_error($koneksi)."');</script>";
    }
}

// --- BAGIAN 4: LOGIKA TAMBAH KE KERANJANG ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id'];
    $ambil = mysqli_query($koneksi, "SELECT * FROM produk WHERE id='$id'");
    $data = mysqli_fetch_array($ambil);
    
    // Cek apakah produk sudah ada di keranjang
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $id) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    
    // Jika produk belum ada di keranjang, tambahkan baru
    if (!$found) {
        $_SESSION['cart'][] = array(
            'id' => $id,
            'nama_barang' => $data['nama_barang'],
            'harga' => $data['harga'],
            'gambar' => $data['gambar'],
            'quantity' => 1
        );
    }
    
    // Redirect ke halaman yang sama untuk menghindari resubmission form
    header('Location: index.php');
    exit;
}

// --- BAGIAN 5: LOGIKA HAPUS DARI KERANJANG ---
if (isset($_GET['remove_from_cart'])) {
    $id = $_GET['remove_from_cart'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $id) {
            unset($_SESSION['cart'][$key]);
            // Re-index array
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }
    header('Location: index.php?page=cart');
    exit;
}

// --- BAGIAN 6: LOGIKA UPDATE KUANTITAS KERANJANG ---
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $value) {
        if ($value <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key]['quantity'] = $value;
        }
    }
    // Re-index array
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: index.php?page=cart');
    exit;
}

// --- BAGIAN 7: FUNGSI MENGHITUNG TOTAL ITEM DI KERANJANG ---
function count_cart_items() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Commerce AWS Sederhana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .product-img {
            height: 220px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .product-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .cart-item {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        
        .checkout-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
        }
        
        .success-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            color: white;
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        
        .footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: var(--dark-color);
            color: white;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
        }
        
        .category-badge {
            background-color: var(--light-color);
            color: var(--primary-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .rating {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-shop"></i> TOKO CLOUD COMPUTING</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="bi bi-house"></i> Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=cart">
                            <i class="bi bi-cart3"></i> Keranjang
                            <span class="cart-badge"><?php echo count_cart_items(); ?></span>
                        </a>
                    </li>
                </ul>
            </div>
            <span class="text-white ms-3 d-none d-md-block">Kelompok Ari, Ega, Juand, Ramadan</span>
        </div>
    </nav>

    <div class="container">
        <?php 
        // --- BAGIAN 8: TAMPILAN PESAN SUKSES ---
        if ($pesan_sukses) { 
        ?>
            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h1>Transaksi Berhasil!</h1>
                        <p class="lead">Pesanan atas nama <b><?php echo $nama; ?></b> akan segera diproses.</p>
                        <p>Terima kasih telah berbelanja di toko kami!</p>
                        <a href="index.php" class="btn btn-light btn-lg mt-3">
                            <i class="bi bi-arrow-left"></i> Kembali Belanja
                        </a>
                    </div>
                </div>
            </div>

        <?php 
        // --- BAGIAN 9: TAMPILAN KERANJANG BELANJA ---
        } elseif (isset($_GET['page']) && $_GET['page'] == 'cart') { 
        ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h2 class="mb-4"><i class="bi bi-cart3"></i> Keranjang Belanja</h2>
                </div>
            </div>
            
            <?php if (empty($_SESSION['cart'])) { ?>
                <div class="row justify-content-center mt-5">
                    <div class="col-md-6 text-center">
                        <div class="card shadow">
                            <div class="card-body p-5">
                                <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
                                <h3 class="mt-3">Keranjang Belanja Kosong</h3>
                                <p class="text-muted">Belum ada produk di keranjang belanja Anda</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left"></i> Lanjut Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row mt-4">
                    <div class="col-lg-8">
                        <form action="index.php" method="POST">
                            <input type="hidden" name="update_cart" value="1">
                            <?php 
                            $total = 0;
                            foreach ($_SESSION['cart'] as $key => $item) {
                                $subtotal = $item['harga'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo $item['gambar']; ?>" alt="<?php echo $item['nama_barang']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo $item['nama_barang']; ?></h5>
                                        <p class="mb-0 text-muted">Rp <?php echo number_format($item['harga']); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(<?php echo $key; ?>, -1)">-</button>
                                            <input type="number" name="quantity[<?php echo $key; ?>]" class="form-control quantity-input" value="<?php echo $item['quantity']; ?>" min="1">
                                            <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity(<?php echo $key; ?>, 1)">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <h5>Rp <?php echo number_format($subtotal); ?></h5>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <a href="index.php?remove_from_cart=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Lanjut Belanja
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise"></i> Update Keranjang
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="card checkout-card">
                            <div class="checkout-header">
                                <h4 class="mb-0">Ringkasan Belanja</h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>Rp <?php echo number_format($total); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Ongkos Kirim:</span>
                                    <span>Rp 15.000</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Total:</h5>
                                    <h5>Rp <?php echo number_format($total + 15000); ?></h5>
                                </div>
                                <a href="index.php?page=checkout" class="btn btn-success w-100">
                                    <i class="bi bi-credit-card"></i> Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        <?php 
        // --- BAGIAN 10: TAMPILAN FORM CHECKOUT ---
        } elseif (isset($_GET['page']) && $_GET['page'] == 'checkout') { 
        ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-8">
                    <div class="checkout-card">
                        <div class="checkout-header">
                            <h4 class="mb-0"><i class="bi bi-credit-card"></i> Konfirmasi Pembelian</h4>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="mb-3">Ringkasan Pesanan</h5>
                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = 0;
                                        foreach ($_SESSION['cart'] as $item) {
                                            $subtotal = $item['harga'] * $item['quantity'];
                                            $total += $subtotal;
                                        ?>
                                        <tr>
                                            <td><?php echo $item['nama_barang']; ?></td>
                                            <td>Rp <?php echo number_format($item['harga']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rp <?php echo number_format($subtotal); ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total:</th>
                                            <th>Rp <?php echo number_format($total); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <h5 class="mb-3">Informasi Pengiriman</h5>
                            <form action="index.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nama" class="form-label">Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" id="nama" required placeholder="Nama Anda">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" id="email" required placeholder="email@example.com">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">Nomor Telepon</label>
                                    <input type="tel" name="telepon" class="form-control" id="telepon" required placeholder="08123456789">
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat Pengiriman</label>
                                    <textarea name="alamat" class="form-control" id="alamat" rows="3" required placeholder="Alamat Lengkap"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="kota" class="form-label">Kota</label>
                                        <input type="text" name="kota" class="form-control" id="kota" required placeholder="Kota">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kodepos" class="form-label">Kode Pos</label>
                                        <input type="text" name="kodepos" class="form-control" id="kodepos" required placeholder="12345">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                                    <select name="metode_pembayaran" class="form-select" id="metode_pembayaran" required>
                                        <option value="">Pilih Metode Pembayaran</option>
                                        <option value="transfer_bank">Transfer Bank</option>
                                        <option value="ewallet">E-Wallet</option>
                                        <option value="cod">Cash on Delivery (COD)</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="btn_konfirmasi" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
                                    </button>
                                    <a href="index.php?page=cart" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php 
        // --- BAGIAN 11: TAMPILAN DAFTAR PRODUK (Halaman Utama) ---
        } else { 
        ?>
            <div class="hero-section">
                <div class="container text-center">
                    <h1 class="display-4 fw-bold mb-3">Selamat Datang di TOKO CLOUD COMPUTING</h1>
                    <p class="lead">Temukan produk berkualitas dengan harga terbaik</p>
                    <div class="mt-4">
                        <div class="input-group mx-auto" style="max-width: 500px;">
                            <input type="text" class="form-control" placeholder="Cari produk...">
                            <button class="btn btn-light" type="button">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mb-4"><i class="bi bi-grid-3x3-gap"></i> Katalog Produk</h3>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-12">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">Semua Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Elektronik</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Fashion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Makanan</a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="row">
                <?php
                $query = mysqli_query($koneksi, "SELECT * FROM produk");
                while($data = mysqli_fetch_array($query)){
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card shadow-sm h-100">
                        <div class="position-relative overflow-hidden">
                            <img src="<?php echo $data['gambar']; ?>" class="card-img-top product-img" alt="<?php echo $data['nama_barang']; ?>">
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="category-badge">Terlaris</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title product-title"><?php echo $data['nama_barang']; ?></h5>
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </div>
                            </div>
                            <p class="card-text text-muted flex-grow-1"><?php echo $data['deskripsi']; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <h4 class="product-price mb-0">Rp <?php echo number_format($data['harga']); ?></h4>
                                <span class="badge bg-success">Tersedia</span>
                            </div>
                            <div class="mt-3">
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <button type="submit" class="btn btn-add-cart w-100">
                                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-white mb-3">TOKO CLOUD COMPUTING</h5>
                    <p class="text-white-50">Menyediakan berbagai produk berkualitas dengan harga terjangkau.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-white mb-3">Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">Tentang Kami</a></li>
                        <li><a href="#" class="text-white-50">Cara Belanja</a></li>
                        <li><a href="#" class="text-white-50">Kebijakan Privasi</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white mb-3">Hubungi Kami</h5>
                    <p class="text-white-50">
                        <i class="bi bi-geo-alt"></i> Jl. Contoh No. 123, Jakarta<br>
                        <i class="bi bi-telephone"></i> (021) 1234567<br>
                        <i class="bi bi-envelope"></i> info@tokocloud.com
                    </p>
                </div>
            </div>
            <hr class="bg-white-50">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; 2023 TOKO CLOUD COMPUTING. Dibuat Menggunakan AWS EC2 (Native LAMP Stack) oleh Kelompok Ari, Ega, Juand, Ramadan</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeQuantity(key, change) {
            const input = document.querySelector(`input[name="quantity[${key}]"]`);
            const newValue = parseInt(input.value) + change;
            if (newValue > 0) {
                input.value = newValue;
            }
        }
    </script>
</body>
</html>