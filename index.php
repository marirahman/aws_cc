<?php
// =========================================================
// BAGIAN 1: KONEKSI DATABASE & SETUP GAMBAR INLINE
// =========================================================
session_start();

$host = "localhost";
$user = "admin_tugas";
$pass = "Admin12345";
$db   = "db_tugas";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Gagal Konek Database: " . mysqli_connect_error()); }

// --- GAMBAR INLINE (BASE64 SVG) ---
// Gambar ini tertanam di kode, jadi PASTI MUNCUL tanpa internet.
$img_laptop = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2NDAgNDgwIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImEiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMCUiIHkyPSIxMDAlIj><stop offset='0%' stop-color='%234a90e2'/><stop offset='100%' stop-color='%23003f7f'/></linearGradient></defs><rect x='80' y='60' width='480' height='300' rx='15' fill='url(%23a)'/><rect x='100' y='80' width='440' height='260' fill='%23fff'/><path d='M40 380h560l-40 60H80z' fill='%23333'/><circle cx='320' cy='210' r='40' fill='%23eee' opacity='0.5'/><text x='320' y='220' font-family='Arial' font-size='20' text-anchor='middle' fill='%23aaa'>LAPTOP PRO</text></svg>";

$img_hp = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMDAgNDgwIj48cmVjdCB4PSc1MCcgeT0nMTAwJyB3aWR0aD0nMjAwJyBoZWlnaHQ9JzM1MCcgcng9JzIwJyBmaWxsPScjMzMzJy8+PHJlY3QgeD0nNjAnIHk9JzExMCcgd2lkdGg9JzE4MCcgaGVpZ2h0PSczMzAnIGZpbGw9JyNmZmYnLz48Y2lyY2xlIGN4PScxNTAnIGN5PScxNTAnIHI9JzQwJyBmaWxsPScjNDQ0JyBvcGFjaXR5PScwLjEnLz48dGV4dCB4PScxNTAnIHk9JzI1MCcgZm9udC1mYW1pbHk9J2FyaWFsJyBmb250LXNpemU9JzI0JyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmaWxsPScjODg4Jz5TTUFSVFBIT05FPC90ZXh0Pjwvc3ZnPg==";

$img_acc = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0MDAgMzAwIj48Y2lyY2xlIGN4PScyMDAnIGN5PScxNTAnIHI9JzEwMCcgZmlsbD0nI2ZmNjYwMCcvPjx0ZXh0IHg9JzIwMCcgeT0nMTU1JyBmb250LWZhbWlseT0nYXJpYWwnIGZvbnQtc2l6ZT0nMzAnIHRleHQtYW5jaG9yPSdtaWRkbGUnIGZpbGw9J3doaXRlJz5HQURHRVQ8L3RleHQ+PC9zdmc+";

// Inisialisasi Session
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = array(); }
if (!isset($_SESSION['order_history'])) { $_SESSION['order_history'] = array(); }

// =========================================================
// BAGIAN 2: LOGIKA BACKEND
// =========================================================

// --- A. PROSES CHECKOUT ---
if (isset($_POST['btn_konfirmasi'])) {
    // Sanitasi Data
    $nama   = htmlspecialchars($_POST['nama']);
    $email  = htmlspecialchars($_POST['email']);
    $telepon = htmlspecialchars($_POST['telepon']);
    $alamat_input = htmlspecialchars($_POST['alamat']);
    $kota   = htmlspecialchars($_POST['kota']);
    $kodepos = htmlspecialchars($_POST['kodepos']);
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    $full_alamat = "$alamat_input, $kota, $kodepos (Telp: $telepon)";
    
    $total_trx = 0;
    $history_items = [];

    // Prepared Statement Insert
    $stmt = $koneksi->prepare("INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga, quantity) VALUES (?, ?, ?, ?, ?)");

    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['quantity'];
        $total_trx += $subtotal;
        
        $history_items[] = [
            'nama_barang' => $item['nama_barang'],
            'harga' => $item['harga'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal
        ];

        $stmt->bind_param("sssii", $nama, $full_alamat, $item['nama_barang'], $item['harga'], $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();

    // Simpan History ke Session (untuk display cepat)
    $new_order = [
        'id' => 'INV-' . rand(1000,9999) . '-' . date('dmy'),
        'tanggal' => date('d F Y, H:i'),
        'nama_pembeli' => $nama,
        'email' => $email,
        'alamat' => $full_alamat,
        'metode' => $metode,
        'total' => $total_trx,
        'items' => $history_items
    ];
    array_unshift($_SESSION['order_history'], $new_order);

    // BERSIHKAN CART & REDIRECT KE RIWAYAT
    $_SESSION['cart'] = array();
    $_SESSION['notif_sukses'] = true; // Trigger notifikasi
    
    header('Location: index.php?page=history');
    exit();
}

// --- B. ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id'];
    // Cek Data di DB
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prod = $res->fetch_assoc();
    $stmt->close();

    if ($prod) {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity']++;
                $found = true;
                break;
            }
        }
        // Jika gambar di DB kosong/rusak, pakai gambar inline default
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
    <title>TOKO CLOUD MODERN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; }
        
        /* Navbar Modern Gradient */
        .navbar { background: linear-gradient(90deg, #2b5876 0%, #4e4376 100%); padding: 15px 0; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; color: #fff !important; }
        .nav-link { color: rgba(255,255,255,0.8) !important; font-weight: 500; transition: 0.3s; margin-left: 10px; }
        .nav-link:hover, .nav-link.active { color: #fff !important; transform: translateY(-2px); }
        
        /* Clock & Theme Icon */
        .header-tools { background: rgba(255,255,255,0.15); padding: 5px 15px; border-radius: 20px; color: white; display: flex; align-items: center; gap: 10px; backdrop-filter: blur(5px); }
        
        /* Card & UI Elements */
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); transition: 0.3s; overflow: hidden; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .btn-primary { background: #4e4376; border: none; border-radius: 50px; padding: 8px 20px; }
        .btn-primary:hover { background: #2b5876; }
        .btn-success { background: #00b09b; border: none; background: linear-gradient(to right, #00b09b, #96c93d); }
        
        /* Product Image */
        .product-img { height: 220px; object-fit: cover; width: 100%; transition: 0.5s; }
        .card:hover .product-img { transform: scale(1.05); }
        
        /* Footer */
        footer { background: #2b2b2b; color: #aaa; padding-top: 50px; }
        footer h5 { color: white; font-weight: 600; margin-bottom: 20px; }
        footer ul li { margin-bottom: 10px; }
        footer a { color: #aaa; text-decoration: none; }
        footer a:hover { color: #fff; }
        .badge-cart { position: absolute; top: -5px; right: -10px; background: #ff4757; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; display: flex; justify-content: center; align-items: center; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-cloud-check-fill"></i> TOKO CLOUD</a>
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-2"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=history">Riwayat</a></li>
                    <li class="nav-item position-relative me-3">
                        <a class="nav-link" href="index.php?page=cart">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if(cart_count()>0){ ?><span class="badge-cart"><?php echo cart_count(); ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="nav-item mt-2 mt-lg-0">
                        <div class="header-tools">
                            <i id="theme-icon" class="bi bi-sun-fill text-warning"></i>
                            <span id="live-clock" class="small fw-bold">00:00:00</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="min-height: 80vh;">
        
        <?php if (isset($_GET['page']) && $_GET['page'] == 'history') { ?>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2 class="mb-4 fw-bold text-dark border-start border-5 border-primary ps-3">Riwayat Pesanan</h2>
                    
                    <?php if (isset($_SESSION['notif_sukses'])) { ?>
                        <div class="alert alert-success d-flex align-items-center shadow p-4 rounded-4 mb-5" role="alert">
                            <i class="bi bi-check-circle-fill fs-1 me-3"></i>
                            <div>
                                <h4 class="alert-heading fw-bold">Pemesanan Berhasil!</h4>
                                <p class="mb-0">Terima kasih. Data pesanan Anda telah tersimpan. Silakan cek detail di bawah ini.</p>
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['notif_sukses']); ?>
                    <?php } ?>

                    <?php if (empty($_SESSION['order_history'])) { ?>
                        <div class="text-center py-5">
                            <img src="<?php echo $img_acc; ?>" width="150" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                            <h4 class="text-muted">Belum ada riwayat transaksi</h4>
                            <a href="index.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($_SESSION['order_history'] as $order) { ?>
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary rounded-pill mb-1">ID: <?php echo $order['id']; ?></span>
                                        <div class="small text-muted"><i class="bi bi-calendar3"></i> <?php echo $order['tanggal']; ?></div>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2"><?php echo $order['metode']; ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <h6 class="fw-bold">Penerima:</h6>
                                            <p class="mb-1"><?php echo $order['nama_pembeli']; ?></p>
                                            <small class="text-muted"><?php echo $order['alamat']; ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Item Dibeli:</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($order['items'] as $item) { ?>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span><?php echo $item['nama_barang']; ?> <span class="text-muted">x<?php echo $item['quantity']; ?></span></span>
                                                        <span class="fw-bold">Rp <?php echo number_format($item['subtotal']); ?></span>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                                <h5 class="fw-bold">Total Bayar</h5>
                                                <h5 class="fw-bold text-primary">Rp <?php echo number_format($order['total']); ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

        <?php } elseif (isset($_GET['page']) && $_GET['page'] == 'cart') { ?>
            <h2 class="mb-4 fw-bold border-start border-5 border-primary ps-3">Keranjang Belanja</h2>
            <?php if (empty($_SESSION['cart'])) { ?>
                <div class="text-center py-5">
                    <i class="bi bi-cart-x display-1 text-muted"></i>
                    <h3 class="mt-3">Keranjang Kosong</h3>
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
                                            <div class="col-9 col-md-4">
                                                <h6 class="fw-bold mb-1"><?php echo $i['nama_barang']; ?></h6>
                                                <small class="text-muted">Rp <?php echo number_format($i['harga']); ?></small>
                                            </div>
                                            <div class="col-6 col-md-3 mt-3 mt-md-0">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="qty[<?php echo $k; ?>]" class="form-control text-center" value="<?php echo $i['quantity']; ?>" min="1">
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3 text-end mt-3 mt-md-0">
                                                <span class="fw-bold d-block">Rp <?php echo number_format($sub); ?></span>
                                                <a href="index.php?remove=<?php echo $i['id']; ?>" class="text-danger small text-decoration-none"><i class="bi bi-trash"></i> Hapus</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <button class="btn btn-warning text-white btn-sm"><i class="bi bi-arrow-repeat"></i> Update Qty</button>
                        </form>
                    </div>
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="card">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Ringkasan</h5>
                                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>Rp <?php echo number_format($grand); ?></span></div>
                                <div class="d-flex justify-content-between mb-3"><span>Ongkir</span><span class="text-success">Gratis</span></div>
                                <hr>
                                <div class="d-flex justify-content-between mb-4"><h5 class="fw-bold">Total</h5><h5 class="fw-bold text-primary">Rp <?php echo number_format($grand); ?></h5></div>
                                <a href="index.php?page=checkout" class="btn btn-success w-100 py-2 rounded-pill shadow fw-bold">CHECKOUT SEKARANG</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        <?php } elseif (isset($_GET['page']) && $_GET['page'] == 'checkout') { ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-gradient bg-primary text-white py-3">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2"></i> Konfirmasi Pembayaran</h4>
                        </div>
                        <div class="card-body p-5">
                            <form action="index.php" method="POST">
                                <h6 class="text-uppercase text-muted fw-bold mb-3">Data Penerima</h6>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control bg-light" required></div>
                                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control bg-light" required></div>
                                    <div class="col-md-6"><label class="form-label">No. WhatsApp</label><input type="text" name="telepon" class="form-control bg-light" required></div>
                                    <div class="col-md-6">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <select name="metode_pembayaran" class="form-select bg-light" required>
                                            <option value="Transfer Bank">Transfer Bank (BCA/Mandiri)</option>
                                            <option value="E-Wallet">E-Wallet (GoPay/OVO)</option>
                                            <option value="COD">COD (Bayar di Tempat)</option>
                                        </select>
                                    </div>
                                    <div class="col-12"><label class="form-label">Alamat Lengkap</label><textarea name="alamat" class="form-control bg-light" rows="2" required></textarea></div>
                                    <div class="col-md-6"><label class="form-label">Kota</label><input type="text" name="kota" class="form-control bg-light" required></div>
                                    <div class="col-md-6"><label class="form-label">Kode Pos</label><input type="text" name="kodepos" class="form-control bg-light" required></div>
                                </div>
                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" name="btn_konfirmasi" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm">
                                        <i class="bi bi-lock-fill"></i> KONFIRMASI PEMBAYARAN
                                    </button>
                                    <a href="index.php?page=cart" class="btn btn-outline-secondary rounded-pill">Batal</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php } else { ?>
            <div class="text-center text-white rounded-4 p-5 mb-5 shadow position-relative overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="position-relative z-1">
                    <h1 class="display-4 fw-bold">Selamat Datang di Toko Cloud</h1>
                    <p class="lead opacity-75">Tugas Akhir Mata Kuliah Cloud Computing - Native PHP Architecture</p>
                </div>
            </div>

            <h4 class="mb-4 fw-bold border-start border-5 border-primary ps-3">Katalog Produk Terbaru</h4>
            <div class="row">
                <?php
                // AMBIL DATA DARI DB
                $q = mysqli_query($koneksi, "SELECT * FROM produk");
                if (mysqli_num_rows($q) > 0) {
                    while($d = mysqli_fetch_array($q)) {
                        // Logika Gambar: Jika di DB kosong, pakai gambar inline $img_laptop
                        $gambar_show = (!empty($d['gambar']) && filter_var($d['gambar'], FILTER_VALIDATE_URL)) ? $d['gambar'] : $img_laptop;
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo $gambar_show; ?>" class="product-img">
                                <span class="position-absolute top-0 end-0 bg-danger text-white px-3 py-1 m-3 rounded-pill small fw-bold">Sale</span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold mb-1"><?php echo $d['nama_barang']; ?></h5>
                                <p class="text-muted small flex-grow-1"><?php echo substr($d['deskripsi'], 0, 80); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                    <h5 class="text-primary fw-bold mb-0">Rp <?php echo number_format($d['harga']); ?></h5>
                                    <form action="index.php" method="POST">
                                        <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                        <input type="hidden" name="add_to_cart" value="1">
                                        <button class="btn btn-primary rounded-pill btn-sm px-3 shadow-sm"><i class="bi bi-cart-plus"></i> Beli</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } } else { ?>
                    <div class="col-12 py-5 text-center">
                        <h3 class="text-muted">Data Produk Kosong / DB Belum Konek</h3>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <footer>
        <div class="container pb-4">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>TOKO CLOUD</h5>
                    <p class="small">Platform E-Commerce modern berbasis Cloud Computing untuk memenuhi kebutuhan tugas kuliah dengan arsitektur Native PHP.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>HUBUNGI KAMI</h5>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-geo-alt me-2"></i> Jln. Universitas Suryakancana, No 12, Pasir Gede.</li>
                        <li><i class="bi bi-telephone me-2"></i> Tlp (07232328)</li>
                        <li><i class="bi bi-envelope me-2"></i> Info@Kelompok_CC.com</li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>TAUTAN</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#">Beranda</a></li>
                        <li><a href="#">Produk</a></li>
                        <li><a href="#">Tentang Kami</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center small py-2">
                &copy; 2025 Kelompok Ari | Cloud Computing Project | All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('live-clock').innerText = timeString;

            // Logika Icon Siang/Malam
            const hour = now.getHours();
            const icon = document.getElementById('theme-icon');
            if (hour >= 6 && hour < 18) {
                icon.className = 'bi bi-sun-fill text-warning'; // Siang
            } else {
                icon.className = 'bi bi-moon-stars-fill text-info'; // Malam
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>