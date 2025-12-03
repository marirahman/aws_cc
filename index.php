<?php
// =========================================================
// BAGIAN 1: SETUP & KONEKSI
// =========================================================
session_start();
error_reporting(E_ALL); // Aktifkan error report untuk debugging (bisa dimatikan nanti)

// Konfigurasi Database
$host = "localhost";
$user = "admin_tugas";
$pass = "Admin12345";
$db   = "db_tugas";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Gagal Konek Database: " . mysqli_connect_error()); }

// --- GAMBAR INLINE (AGAR MUNCUL TANPA INTERNET) ---
$img_laptop = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2NDAgNDgwIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9JzAlJyBzdG9wLWNvbG9yPScjMDAxZjNmJy8+PHN0b3Agb2Zmc2V0PScxMDAlJyBzdG9wLWNvbG9yPScjMDAwMDAwJy8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PHJlY3QgeD0nODAnIHk9JzYwJyB3aWR0aD0nNDgwJyBoZWlnaHQ9JzMwMCcgcng9JzE1JyBmaWxsPSd1cmwoI2EpJy8+PHJlY3QgeD0nMTAwJyB5PSc4MCcgd2lkdGg9JzQ0MCcgaGVpZ2h0PScyNjAnIGZpbGw9JyNmZmYnLz48cGF0aCBkPSZNNDAgMzhoeDU2MGwtNDAgNjBIODB6JyBmaWxsPScjMTExJy8+PHRleHQgeD0nMzIwJyB5PScyMjAnIGZvbnQtZmFtaWx5PSdBcmlhbCcgZm9udC1zaXplPSIyNCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzMzMyI+TEFQVE9QIFBSTzwvdGV4dD48L3N2Zz4=";

$img_hp = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMDAgNDgwIj48cmVjdCB4PSc1MCcgeT0nMTAwJyB3aWR0aD0nMjAwJyBoZWlnaHQ9JzM1MCcgcng9JzIwJyBmaWxsPScjMDAxZjNmJy8+PHJlY3QgeD0nNjAnIHk9JzExMCcgd2lkdGg9JzE4MCcgaGVpZ2h0PSczMzAnIGZpbGw9JyNmZmYnLz48Y2lyY2xlIGN4PScxNTAnIGN5PScxNTAnIHI9JzQwJyBmaWxsPScjMTExJyBvcGFjaXR5PScwLjEnLz48dGV4dCB4PScxNTAnIHk9JzI1MCcgZm9udC1mYW1pbHk9J2FyaWFsJyBmb250LXNpemU9JzI0JyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmaWxsPScjMzMzJz5TTElNIFBIT05FPC90ZXh0Pjwvc3ZnPg==";

$img_acc = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0MDAgMzAwIj48Y2lyY2xlIGN4PScyMDAnIGN5PScxNTAnIHI9JzEwMCcgZmlsbD0nIzAwMWYzZicvPjx0ZXh0IHg9JzIwMCcgeT0nMTU1JyBmb250LWZhbWlseT0nYXJpYWwnIGZvbnQtc2l6ZT0nMzAnIHRleHQtYW5jaG9yPSdtaWRkbGUnIGZpbGw9J3doaXRlJz5HQURHRVQ8L3RleHQ+PC9zdmc+";

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = array(); }
if (!isset($_SESSION['order_history'])) { $_SESSION['order_history'] = array(); }

// =========================================================
// BAGIAN 2: LOGIKA PHP
// =========================================================

// --- A. PROSES CHECKOUT (FIXED) ---
if (isset($_POST['btn_konfirmasi'])) {
    // 1. Ambil Data Form
    $nama   = htmlspecialchars($_POST['nama']);
    $email  = htmlspecialchars($_POST['email']);
    $telepon = htmlspecialchars($_POST['telepon']);
    $alamat_input = htmlspecialchars($_POST['alamat']);
    $kota   = htmlspecialchars($_POST['kota']);
    $kodepos = htmlspecialchars($_POST['kodepos']);
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    $full_alamat = "$alamat_input, $kota, $kodepos (Telp: $telepon)";
    
    // 2. Insert ke Database (Looping per item)
    // Pastikan tabel 'pesanan' sudah ada kolom 'quantity'
    $stmt = $koneksi->prepare("INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga, quantity) VALUES (?, ?, ?, ?, ?)");

    $total_trx = 0;
    $history_items = [];

    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['quantity'];
        $total_trx += $subtotal;
        
        // Simpan ke array history session (buat display)
        $history_items[] = [
            'nama_barang' => $item['nama_barang'],
            'harga' => $item['harga'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal
        ];

        // Eksekusi SQL Insert
        $stmt->bind_param("sssii", $nama, $full_alamat, $item['nama_barang'], $item['harga'], $item['quantity']);
        if (!$stmt->execute()) {
            // Jika error SQL, tampilkan (untuk debugging)
            die("Error DB: " . $stmt->error);
        }
    }
    $stmt->close();

    // 3. Simpan ke Session History (Opsional, agar user bisa lihat di menu Riwayat)
    $new_order = [
        'id' => 'TRX-' . rand(100,999) . '-' . time(),
        'tanggal' => date('d F Y, H:i'),
        'nama_pembeli' => $nama,
        'email' => $email,
        'alamat' => $full_alamat,
        'metode' => $metode,
        'total' => $total_trx,
        'items' => $history_items
    ];
    array_unshift($_SESSION['order_history'], $new_order);

    // 4. BERSIHKAN CART
    $_SESSION['cart'] = array();

    // 5. REDIRECT KE HALAMAN AWAL DENGAN NOTIFIKASI
    // Kita pakai script JS langsung agar redirectnya mulus dengan alert
    echo "<script>
        alert('PEMBAYARAN BERHASIL!\\n\\nTerima kasih $nama, pesanan Anda sedang kami proses.');
        window.location.href = 'index.php'; 
    </script>";
    exit();
}

// --- B. ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id'];
    $q = mysqli_query($koneksi, "SELECT * FROM produk WHERE id='$id'");
    $prod = mysqli_fetch_array($q);

    if ($prod) {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity']++;
                $found = true;
                break;
            }
        }
        // Gunakan gambar inline jika gambar DB kosong
        $gambar_final = (!empty($prod['gambar']) && filter_var($prod['gambar'], FILTER_VALIDATE_URL)) ? $prod['gambar'] : $img_laptop;

        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $prod['id'],
                'nama_barang' => $prod['nama_barang'],
                'harga' => $prod['harga'],
                'gambar' => $gambar_final,
                'quantity' => 1
            ];
        }
    }
    header('Location: index.php');
    exit;
}

// --- C. HAPUS & UPDATE CART ---
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    foreach ($_SESSION['cart'] as $k => $v) {
        if ($v['id'] == $id) { unset($_SESSION['cart'][$k]); break; }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: index.php?page=cart');
    exit;
}
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $k => $v) {
        if ($v > 0) $_SESSION['cart'][$k]['quantity'] = $v;
    }
    header('Location: index.php?page=cart');
    exit;
}

function cart_count() {
    $c = 0; if(isset($_SESSION['cart'])) foreach($_SESSION['cart'] as $i) $c += $i['quantity'];
    return $c;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TOKO CLOUD ELITE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* TEMA BIRU TUA & HITAM (DARK BLUE & BLACK) */
        :root {
            --primary: #001f3f; /* Navy Blue Gelap */
            --secondary: #111111; /* Hitam Pekat */
            --accent: #0074D9; /* Biru terang untuk tombol */
            --bg-light: #f4f4f4;
            --text-dark: #222;
        }

        body { font-family: 'Montserrat', sans-serif; background-color: #e0e0e0; color: var(--text-dark); }
        
        /* Navbar Gradient Hitam ke Biru Tua */
        .navbar { background: linear-gradient(90deg, #000000 0%, #001f3f 100%); padding: 1rem 0; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .navbar-brand { font-weight: 800; letter-spacing: 1.5px; color: #fff !important; text-transform: uppercase; }
        .nav-link { color: rgba(255,255,255,0.7) !important; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; border-bottom: 2px solid var(--accent); }
        
        /* Jam & Icon */
        .header-info { color: white; background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 30px; display: flex; align-items: center; gap: 8px; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.2); }
        
        /* Card Styling */
        .card { border: none; border-radius: 10px; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        /* Tombol */
        .btn-primary { background-color: var(--primary); border: none; padding: 10px 20px; border-radius: 5px; font-weight: 600; }
        .btn-primary:hover { background-color: var(--secondary); }
        .btn-success { background-color: #2ecc71; border: none; }
        .btn-checkout { background: var(--secondary); color: white; width: 100%; padding: 15px; font-weight: bold; border-radius: 8px; letter-spacing: 1px; transition: 0.3s; }
        .btn-checkout:hover { background: var(--primary); color: white; }

        /* Footer */
        footer { background: #0a0a0a; color: #888; padding-top: 60px; border-top: 5px solid var(--primary); }
        footer h5 { color: white; font-weight: 700; margin-bottom: 25px; letter-spacing: 1px; }
        footer a { color: #888; text-decoration: none; transition: 0.3s; }
        footer a:hover { color: var(--accent); }
        footer .info-text i { color: var(--accent); margin-right: 10px; }

        /* Hero Section */
        .hero { 
            background: linear-gradient(135deg, #001f3f 0%, #111 100%); 
            color: white; padding: 80px 0; text-align: center; margin-bottom: 40px; 
            border-bottom: 5px solid var(--accent);
        }
        
        .badge-cart { position: absolute; top: -5px; right: -10px; background: var(--accent); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; justify-content: center; align-items: center; }
        .section-title { font-weight: 800; color: var(--secondary); margin-bottom: 30px; position: relative; display: inline-block; }
        .section-title::after { content: ''; display: block; width: 50px; height: 4px; background: var(--primary); margin-top: 10px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-cpu-fill"></i> TOKO CLOUD</a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=history">RIWAYAT</a></li>
                    <li class="nav-item position-relative me-4">
                        <a class="nav-link" href="index.php?page=cart">
                            KERANJANG <i class="bi bi-cart-fill"></i>
                            <?php if(cart_count()>0){ ?><span class="badge-cart"><?php echo cart_count(); ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="header-info">
                            <i id="theme-icon" class="bi bi-sun-fill text-warning"></i>
                            <span id="live-clock">00:00</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="min-height: 80vh;">
        
        <?php if (isset($_GET['page']) && $_GET['page'] == 'history') { ?>
            <div class="container py-5">
                <h3 class="section-title">RIWAYAT PESANAN</h3>
                
                <?php if (empty($_SESSION['order_history'])) { ?>
                    <div class="text-center py-5 bg-white rounded shadow-sm">
                        <i class="bi bi-receipt display-1 text-muted"></i>
                        <h4 class="mt-3 text-dark">Belum ada transaksi</h4>
                        <a href="index.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <?php foreach ($_SESSION['order_history'] as $order) { ?>
                            <div class="col-12 mb-3">
                                <div class="card border-start border-5 border-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="fw-bold text-primary mb-0">ID: <?php echo $order['id']; ?></h5>
                                            <span class="badge bg-dark"><?php echo $order['tanggal']; ?></span>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p class="mb-1"><strong>Nama:</strong> <?php echo $order['nama_pembeli']; ?></p>
                                                <p class="mb-1"><strong>Alamat:</strong> <?php echo $order['alamat']; ?></p>
                                                <small class="text-muted">Metode: <?php echo $order['metode']; ?></small>
                                            </div>
                                            <div class="col-md-4 text-end align-self-center">
                                                <h3 class="fw-bold text-dark">Rp <?php echo number_format($order['total']); ?></h3>
                                                <span class="badge bg-success">LUNAS</span>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="bg-light p-2 rounded">
                                            <small class="fw-bold text-muted">DETAIL ITEM:</small>
                                            <ul class="list-unstyled mb-0 mt-1">
                                                <?php foreach ($order['items'] as $item) { ?>
                                                    <li class="d-flex justify-content-between small">
                                                        <span><?php echo $item['nama_barang']; ?> (x<?php echo $item['quantity']; ?>)</span>
                                                        <span>Rp <?php echo number_format($item['subtotal']); ?></span>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

        <?php } elseif (isset($_GET['page']) && $_GET['page'] == 'cart') { ?>
            <div class="container py-5">
                <h3 class="section-title">KERANJANG BELANJA</h3>
                <?php if (empty($_SESSION['cart'])) { ?>
                    <div class="text-center py-5 bg-white rounded shadow-sm">
                        <i class="bi bi-cart-x display-1 text-muted"></i>
                        <h4 class="mt-3">Keranjang Kosong</h4>
                        <a href="index.php" class="btn btn-primary mt-3">Belanja Sekarang</a>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <form action="index.php" method="POST">
                                <input type="hidden" name="update_cart" value="1">
                                <?php $grand = 0; foreach ($_SESSION['cart'] as $k => $i) { $sub = $i['harga']*$i['quantity']; $grand+=$sub; ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-3 col-md-2">
                                                    <img src="<?php echo $i['gambar']; ?>" class="img-fluid rounded">
                                                </div>
                                                <div class="col-9 col-md-5">
                                                    <h6 class="fw-bold text-dark mb-1"><?php echo $i['nama_barang']; ?></h6>
                                                    <small class="text-primary fw-bold">Rp <?php echo number_format($i['harga']); ?></small>
                                                </div>
                                                <div class="col-6 col-md-3 mt-2">
                                                    <input type="number" name="qty[<?php echo $k; ?>]" class="form-control text-center border-dark" value="<?php echo $i['quantity']; ?>" min="1">
                                                </div>
                                                <div class="col-6 col-md-2 text-end mt-2">
                                                    <a href="index.php?remove=<?php echo $i['id']; ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <button class="btn btn-dark btn-sm"><i class="bi bi-arrow-clockwise"></i> UPDATE JUMLAH</button>
                            </form>
                        </div>
                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <div class="card bg-white text-dark">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-4">RINGKASAN</h5>
                                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>Rp <?php echo number_format($grand); ?></span></div>
                                    <div class="d-flex justify-content-between mb-3"><span>Ongkir</span><span class="text-success fw-bold">GRATIS</span></div>
                                    <hr class="border-dark">
                                    <div class="d-flex justify-content-between mb-4"><h4 class="fw-bold">Rp <?php echo number_format($grand); ?></h4></div>
                                    <a href="index.php?page=checkout" class="btn btn-checkout text-center">CHECKOUT SEKARANG</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

        <?php } elseif (isset($_GET['page']) && $_GET['page'] == 'checkout') { ?>
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-lg border-0">
                            <div class="card-header bg-dark text-white py-3">
                                <h4 class="mb-0 fw-bold text-center">KONFIRMASI PEMBAYARAN</h4>
                            </div>
                            <div class="card-body p-5">
                                <form action="index.php" method="POST">
                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">DATA PENERIMA</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="fw-bold small">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                                        <div class="col-md-6"><label class="fw-bold small">Email</label><input type="email" name="email" class="form-control" required></div>
                                        <div class="col-md-6"><label class="fw-bold small">No. Telepon</label><input type="text" name="telepon" class="form-control" required></div>
                                        <div class="col-md-6">
                                            <label class="fw-bold small">Metode Pembayaran</label>
                                            <select name="metode_pembayaran" class="form-select" required>
                                                <option value="Transfer Bank">Transfer Bank (BCA/Mandiri)</option>
                                                <option value="E-Wallet">E-Wallet (Dana/OVO)</option>
                                                <option value="COD">COD (Bayar di Tempat)</option>
                                            </select>
                                        </div>
                                        <div class="col-12"><label class="fw-bold small">Alamat Lengkap</label><textarea name="alamat" class="form-control" rows="3" required></textarea></div>
                                        <div class="col-md-6"><label class="fw-bold small">Kota</label><input type="text" name="kota" class="form-control" required></div>
                                        <div class="col-md-6"><label class="fw-bold small">Kode Pos</label><input type="text" name="kodepos" class="form-control" required></div>
                                    </div>
                                    <div class="d-grid gap-2 mt-5">
                                        <button type="submit" name="btn_konfirmasi" class="btn btn-checkout btn-lg shadow">
                                            <i class="bi bi-lock-fill"></i> KONFIRMASI PEMBAYARAN
                                        </button>
                                        <a href="index.php?page=cart" class="btn btn-outline-secondary">Batal</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php } else { ?>
            <div class="hero">
                <div class="container">
                    <h1 class="display-4 fw-bold">SELAMAT DATANG DI TOKO CLOUD</h1>
                    <p class="lead opacity-75">Teknologi Terdepan, Kualitas Terbaik, Harga Terjangkau</p>
                    <a href="#katalog" class="btn btn-primary btn-lg mt-3 px-5 rounded-pill shadow">BELANJA SEKARANG</a>
                </div>
            </div>

            <div class="container" id="katalog">
                <h3 class="section-title">PRODUK UNGGULAN</h3>
                <div class="row">
                    <?php
                    $q = mysqli_query($koneksi, "SELECT * FROM produk");
                    if (mysqli_num_rows($q) > 0) {
                        while($d = mysqli_fetch_array($q)) {
                            // Cek Gambar
                            $gambar_show = (!empty($d['gambar']) && filter_var($d['gambar'], FILTER_VALIDATE_URL)) ? $d['gambar'] : $img_laptop;
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div style="height: 220px; overflow: hidden;">
                                    <img src="<?php echo $gambar_show; ?>" class="product-img" alt="Produk">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="fw-bold text-dark"><?php echo $d['nama_barang']; ?></h5>
                                    <p class="text-muted small flex-grow-1"><?php echo substr($d['deskripsi'], 0, 90); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <h5 class="text-primary fw-bold mb-0">Rp <?php echo number_format($d['harga']); ?></h5>
                                        <form action="index.php" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <button class="btn btn-dark btn-sm rounded-pill px-3"><i class="bi bi-cart-plus"></i> BELI</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } } else { ?>
                        <div class="col-12 text-center py-5">
                            <h3 class="text-muted">Produk Kosong / Database Belum Terhubung</h3>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <footer>
        <div class="container pb-4">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>TENTANG KAMI</h5>
                    <p class="small text-white-50">Toko Cloud adalah platform e-commerce modern yang dibangun dengan arsitektur Native PHP di atas AWS EC2. Kami menyediakan produk teknologi terbaik untuk Anda.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>HUBUNGI KAMI</h5>
                    <ul class="list-unstyled info-text small">
                        <li><i class="bi bi-geo-alt-fill"></i> Jln. Universitas Suryakancana, No 12, Pasir Gede.</li>
                        <li><i class="bi bi-telephone-fill"></i> Tlp (07232328)</li>
                        <li><i class="bi bi-envelope-fill"></i> Info@Kelompok_CC.com</li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>LAYANAN</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#">Cara Pemesanan</a></li>
                        <li><a href="#">Status Pengiriman</a></li>
                        <li><a href="#">Pengembalian Barang</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center pt-4 border-top border-secondary mt-3">
                <small class="text-white-50">&copy; 2025 Kelompok Ari | Cloud Computing Project | All Rights Reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('live-clock').innerText = timeString;

            // Logika Ikon Siang/Malam
            const hour = now.getHours();
            const icon = document.getElementById('theme-icon');
            if (hour >= 6 && hour < 18) {
                icon.className = 'bi bi-sun-fill text-warning';
            } else {
                icon.className = 'bi bi-moon-stars-fill text-info';
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>