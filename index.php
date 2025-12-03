<?php
// =========================================================
// BAGIAN 1: KONEKSI DATABASE & INITIAL SETUP
// =========================================================
session_start();

// Konfigurasi Database
$host = "localhost";
$user = "admin_tugas";
$pass = "Admin12345";
$db   = "db_tugas";

// Membuat Koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { 
    die("Gagal Konek Database: " . mysqli_connect_error()); 
}

// Inisialisasi Session jika belum ada
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = array(); }
if (!isset($_SESSION['order_history'])) { $_SESSION['order_history'] = array(); }

// =========================================================
// BAGIAN 2: LOGIKA BACKEND (PHP)
// =========================================================

// A. LOGIKA CHECKOUT (SIMPAN PESANAN)
if (isset($_POST['btn_konfirmasi'])) {
    // 1. Tangkap & Sanitasi Input (Keamanan)
    $nama   = htmlspecialchars($_POST['nama']);
    $email  = htmlspecialchars($_POST['email']);
    $telepon = htmlspecialchars($_POST['telepon']);
    $alamat_input = htmlspecialchars($_POST['alamat']);
    $kota   = htmlspecialchars($_POST['kota']);
    $kodepos = htmlspecialchars($_POST['kodepos']);
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    
    // Gabungkan alamat agar rapi di database
    $full_alamat = "$alamat_input, $kota, $kodepos (Telp: $telepon)";
    
    $total_harga_transaksi = 0;
    $items_detail_history = []; // Untuk disimpan di session history

    // 2. Siapkan Query Insert (Prepared Statement)
    $stmt = $koneksi->prepare("INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga, quantity) VALUES (?, ?, ?, ?, ?)");

    // 3. Looping Keranjang untuk disimpan ke DB per item
    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['quantity'];
        $total_harga_transaksi += $subtotal;
        
        // Simpan data untuk tampilan history di web
        $items_detail_history[] = [
            'nama_barang' => $item['nama_barang'],
            'harga' => $item['harga'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal
        ];

        // Eksekusi Simpan ke DB
        // "sssii" artinya: string, string, string, integer, integer
        $stmt->bind_param("sssii", $nama, $full_alamat, $item['nama_barang'], $item['harga'], $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();

    // 4. Simpan Riwayat ke Session (Agar user bisa lihat history tanpa login)
    $new_order = [
        'id' => 'TRX-' . time(),
        'tanggal' => date('Y-m-d H:i:s'),
        'nama_pembeli' => $nama,
        'email' => $email,
        'alamat' => $alamat_input,
        'kota' => $kota,
        'metode_pembayaran' => $metode,
        'total_harga' => $total_harga_transaksi,
        'items' => $items_detail_history
    ];
    // Masukkan ke urutan pertama array history
    array_unshift($_SESSION['order_history'], $new_order);

    // 5. Bersihkan Keranjang & Redirect
    $_SESSION['cart'] = array();
    $_SESSION['order_success'] = true;
    
    header('Location: index.php?page=history');
    exit();
}

// B. LOGIKA TAMBAH KE KERANJANG
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id'];
    
    // Ambil data produk ASLI dari Database berdasarkan ID
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product) {
        $found = false;
        // Cek apakah produk sudah ada di keranjang?
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity']++; // Kalau ada, tambah jumlahnya
                $found = true;
                break;
            }
        }
        // Kalau belum ada, masukkan sebagai item baru
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'nama_barang' => $product['nama_barang'],
                'harga' => $product['harga'],
                'gambar' => $product['gambar'],
                'quantity' => 1
            ];
        }
    }
    header('Location: index.php'); // Refresh halaman
    exit;
}

// C. LOGIKA HAPUS ITEM DARI KERANJANG
if (isset($_GET['remove_from_cart'])) {
    $id = $_GET['remove_from_cart'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $id) {
            unset($_SESSION['cart'][$key]);
            break; 
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Rapikan index array
    header('Location: index.php?page=cart');
    exit;
}

// D. LOGIKA UPDATE JUMLAH (QTY) DI KERANJANG
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $value) {
        if ($value > 0) {
            $_SESSION['cart'][$key]['quantity'] = $value;
        }
    }
    header('Location: index.php?page=cart');
    exit;
}

// Fungsi Bantu: Hitung Total Item di Cart
function count_cart_items() {
    $count = 0;
    if(isset($_SESSION['cart'])) {
        foreach($_SESSION['cart'] as $item){
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
    <title>Toko Cloud Computing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        /* CSS Custom Sederhana */
        body { font-family: sans-serif; background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #0d6efd, #0dcaf0); }
        .navbar-brand { font-weight: bold; color: white !important; }
        .nav-link { color: rgba(255,255,255,0.9) !important; }
        .nav-link:hover { color: white !important; }
        .hero { background: #0d6efd; color: white; padding: 50px 0; margin-bottom: 30px; border-radius: 0 0 30px 30px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .product-img { height: 200px; object-fit: cover; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .badge-cart { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-cloud"></i> TOKO CLOUD</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=history">Riwayat</a></li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="index.php?page=cart">
                            <i class="bi bi-cart-fill"></i> Keranjang
                            <?php if(count_cart_items() > 0) { ?>
                                <span class="badge-cart"><?php echo count_cart_items(); ?></span>
                            <?php } ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <?php 
        // =========================================================
        // BAGIAN 3: ROUTING HALAMAN (VIEWS)
        // =========================================================
        
        // --- HALAMAN RIWAYAT (HISTORY) ---
        if (isset($_GET['page']) && $_GET['page'] == 'history') { 
        ?>
            <div class="mt-4">
                <h2 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pesanan</h2>
                
                <?php if (isset($_SESSION['order_success'])) { ?>
                    <div class="alert alert-success">
                        <h4><i class="bi bi-check-circle"></i> Transaksi Berhasil!</h4>
                        <p>Data pesanan sudah masuk ke Database AWS RDS.</p>
                    </div>
                    <?php unset($_SESSION['order_success']); ?>
                <?php } ?>

                <?php if (empty($_SESSION['order_history'])) { ?>
                    <div class="text-center py-5 text-muted">
                        <h3>Belum ada riwayat transaksi.</h3>
                        <a href="index.php" class="btn btn-primary mt-2">Mulai Belanja</a>
                    </div>
                <?php } else { ?>
                    <?php foreach ($_SESSION['order_history'] as $order) { ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light d-flex justify-content-between">
                                <span><strong>ID:</strong> <?php echo $order['id']; ?></span>
                                <span class="badge bg-success"><?php echo $order['metode_pembayaran']; ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Tgl:</strong> <?php echo $order['tanggal']; ?></p>
                                        <p class="mb-1"><strong>Nama:</strong> <?php echo $order['nama_pembeli']; ?></p>
                                        <p class="mb-1"><strong>Alamat:</strong> <?php echo $order['alamat']; ?></p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h5 class="text-primary">Total: Rp <?php echo number_format($order['total_harga']); ?></h5>
                                    </div>
                                </div>
                                <hr>
                                <h6>Item Dibeli:</h6>
                                <ul class="list-group">
                                    <?php foreach ($order['items'] as $item) { ?>
                                        <li class="list-group-item d-flex justify-content-between p-2">
                                            <span><?php echo $item['nama_barang']; ?> (x<?php echo $item['quantity']; ?>)</span>
                                            <span>Rp <?php echo number_format($item['subtotal']); ?></span>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

        <?php 
        // --- HALAMAN KERANJANG (CART) ---
        } elseif (isset($_GET['page']) && $_GET['page'] == 'cart') { 
        ?>
            <div class="mt-4">
                <h2 class="mb-4">Keranjang Belanja</h2>
                
                <?php if (empty($_SESSION['cart'])) { ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x display-1 text-muted"></i>
                        <h3 class="mt-3">Keranjang Kosong</h3>
                        <a href="index.php" class="btn btn-primary">Belanja Sekarang</a>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <form action="index.php" method="POST">
                                <input type="hidden" name="update_cart" value="1">
                                <?php 
                                $grand_total = 0; 
                                foreach ($_SESSION['cart'] as $key => $item) { 
                                    $subtotal = $item['harga'] * $item['quantity']; 
                                    $grand_total += $subtotal; 
                                ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <img src="<?php echo $item['gambar']; ?>" class="img-fluid rounded" alt="img">
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="mb-1"><?php echo $item['nama_barang']; ?></h5>
                                                <small class="text-muted">Rp <?php echo number_format($item['harga']); ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" name="quantity[<?php echo $key; ?>]" class="form-control text-center" value="<?php echo $item['quantity']; ?>" min="1">
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <h6>Rp <?php echo number_format($subtotal); ?></h6>
                                                <a href="index.php?remove_from_cart=<?php echo $item['id']; ?>" class="text-danger small text-decoration-none"><i class="bi bi-trash"></i> Hapus</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                                <button type="submit" class="btn btn-warning text-white"><i class="bi bi-arrow-clockwise"></i> Update Jumlah</button>
                            </form>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-white"><h4>Ringkasan</h4></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>Rp <?php echo number_format($grand_total); ?></span></div>
                                    <div class="d-flex justify-content-between mb-2"><span>Ongkir</span><span>Rp 15.000</span></div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-4"><h5>Total</h5><h5 class="text-primary">Rp <?php echo number_format($grand_total + 15000); ?></h5></div>
                                    <a href="index.php?page=checkout" class="btn btn-success w-100 btn-lg">CHECKOUT</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

        <?php 
        // --- HALAMAN CHECKOUT (FORM) ---
        } elseif (isset($_GET['page']) && $_GET['page'] == 'checkout') { 
        ?>
            <div class="row justify-content-center mt-5">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Konfirmasi Pembayaran</h4>
                        </div>
                        <div class="card-body p-4">
                            <form action="index.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                                    <div class="col-md-6 mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>No. HP</label><input type="text" name="telepon" class="form-control" required></div>
                                    <div class="col-md-6 mb-3">
                                        <label>Metode Bayar</label>
                                        <select name="metode_pembayaran" class="form-select" required>
                                            <option value="Transfer Bank">Transfer Bank</option>
                                            <option value="E-Wallet">E-Wallet</option>
                                            <option value="COD">COD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3"><label>Alamat Jalan</label><textarea name="alamat" class="form-control" rows="2" required></textarea></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>Kota</label><input type="text" name="kota" class="form-control" required></div>
                                    <div class="col-md-6 mb-3"><label>Kode Pos</label><input type="text" name="kodepos" class="form-control" required></div>
                                </div>
                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" name="btn_konfirmasi" class="btn btn-success btn-lg">BAYAR SEKARANG</button>
                                    <a href="index.php?page=cart" class="btn btn-outline-secondary">Batal</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php 
        // --- HALAMAN UTAMA (HOME / KATALOG) ---
        } else { 
        ?>
            <div class="hero text-center">
                <div class="container">
                    <h1 class="display-4 fw-bold">Selamat Datang di Toko Cloud</h1>
                    <p class="lead">Demo Aplikasi E-Commerce dengan Arsitektur AWS Native</p>
                </div>
            </div>

            <h3 class="mb-4">Katalog Produk</h3>
            <div class="row">
                <?php
                // AMBIL DATA DARI DATABASE MYSQL
                $query = mysqli_query($koneksi, "SELECT * FROM produk");
                
                if (mysqli_num_rows($query) > 0) {
                    while($data = mysqli_fetch_array($query)) {
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo $data['gambar']; ?>" class="product-img" alt="Produk"
                                 onerror="this.src='https://via.placeholder.com/300?text=No+Image'">
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold"><?php echo $data['nama_barang']; ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?php echo $data['deskripsi']; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <h4 class="text-primary mb-0">Rp <?php echo number_format($data['harga']); ?></h4>
                                    
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <button type="submit" class="btn btn-primary rounded-pill px-3">
                                            <i class="bi bi-cart-plus"></i> Beli
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    } 
                } else {
                    echo "<div class='col-12 text-center py-5'><h4>Database Kosong / Belum Terhubung</h4></div>";
                }
                ?>
            </div>
        <?php } ?>
        
    </div>

    <footer class="text-center py-4 mt-5 text-muted bg-light">
        <small>&copy; 2025 Kelompok Ari | Teknik Informatika | Cloud Computing Project</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>