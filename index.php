<?php
// =========================================================
// BAGIAN 1: KONFIGURASI
// =========================================================
session_start();
error_reporting(E_ALL);

$host = "localhost";
$user = "admin_tugas";
$pass = "Admin12345";
$db   = "db_tugas";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Gagal Konek Database: " . mysqli_connect_error()); }

// --- GAMBAR CADANGAN (Anti Gagal) ---
// Jika gambar di database rusak, script akan otomatis pakai ini
$img_default = "https://placehold.co/400x300/001f3f/ffffff?text=PRODUK+TOKO";

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = array(); }
if (!isset($_SESSION['order_history'])) { $_SESSION['order_history'] = array(); }

// =========================================================
// BAGIAN 2: LOGIKA BACKEND
// =========================================================

// --- A. PROSES CHECKOUT ---
if (isset($_POST['btn_konfirmasi'])) {
    $nama   = htmlspecialchars($_POST['nama']);
    $email  = htmlspecialchars($_POST['email']);
    $telepon = htmlspecialchars($_POST['telepon']);
    $alamat_input = htmlspecialchars($_POST['alamat']);
    $kota   = htmlspecialchars($_POST['kota']);
    $kodepos = htmlspecialchars($_POST['kodepos']);
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    $full_alamat = "$alamat_input, $kota, $kodepos (Telp: $telepon)";
    
    // Pastikan tabel pesanan punya kolom quantity
    $stmt = $koneksi->prepare("INSERT INTO pesanan (nama_pembeli, alamat, nama_barang, total_harga, quantity) VALUES (?, ?, ?, ?, ?)");
    
    $total_trx = 0;
    $history_items = [];

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

    // Simpan history ke session
    $new_order = [
        'id' => 'INV-' . rand(1000,9999),
        'tanggal' => date('d F Y, H:i'),
        'nama_pembeli' => $nama,
        'metode' => $metode,
        'alamat' => $alamat_input,
        'total' => $total_trx,
        'items' => $history_items
    ];
    array_unshift($_SESSION['order_history'], $new_order);

    // Bersihkan Cart
    $_SESSION['cart'] = array();

    // Set Session Flash Message untuk SweetAlert
    $_SESSION['flash_status'] = 'success';
    $_SESSION['flash_title'] = 'PEMBAYARAN BERHASIL!';
    $_SESSION['flash_text'] = "Terima kasih $nama. Pesanan Anda sedang diproses.";
    
    // Redirect kembali ke halaman utama
    header('Location: index.php');
    exit();
}

// --- B. ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['id'];
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
        
        // Cek apakah gambar valid URL, jika tidak pakai default
        $gambar_fix = (!empty($prod['gambar']) && filter_var($prod['gambar'], FILTER_VALIDATE_URL)) ? $prod['gambar'] : $img_default;
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $prod['id'],
                'nama_barang' => $prod['nama_barang'],
                'harga' => $prod['harga'],
                'gambar' => $gambar_fix,
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">

    <style>
        /* TEMA: MIDNIGHT BLUE & BLACK */
        :root {
            --midnight: #001233;  /* Biru Sangat Gelap */
            --royal: #03256c;     /* Biru Tua */
            --accent: #2541b2;    /* Biru Cerah */
            --black: #000000;
            --text-light: #e0e0e0;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f0f2f5; 
            color: #222;
        }

        /* NAVBAR KEREN */
        .navbar { 
            background: linear-gradient(90deg, var(--black) 0%, var(--midnight) 100%); 
            padding: 15px 0; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .navbar-brand { font-weight: 800; color: #fff !important; letter-spacing: 2px; text-transform: uppercase; }
        .nav-link { color: rgba(255,255,255,0.7) !important; font-weight: 500; transition: 0.3s; }
        .nav-link:hover { color: #fff !important; transform: scale(1.05); }
        
        /* HEADER TOOLS */
        .header-tools { 
            background: rgba(255,255,255,0.1); 
            border: 1px solid rgba(255,255,255,0.2);
            padding: 5px 15px; 
            border-radius: 50px; 
            color: white; 
            font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
        }

        /* CARD STYLE */
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; overflow: hidden; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        
        /* BUTTONS */
        .btn-primary { background-color: var(--midnight); border: none; padding: 10px 25px; border-radius: 30px; }
        .btn-primary:hover { background-color: var(--accent); }
        
        .btn-checkout {
            background: linear-gradient(45deg, var(--midnight), var(--accent));
            color: white; width: 100%; padding: 15px; border-radius: 10px; font-weight: bold; border: none; letter-spacing: 1px;
        }
        .btn-checkout:hover { color: white; opacity: 0.9; }

        /* FOOTER */
        footer { background: var(--black); color: #888; padding-top: 60px; border-top: 4px solid var(--accent); }
        footer h5 { color: white; font-weight: 700; margin-bottom: 20px; }
        footer a { color: #888; text-decoration: none; transition: 0.3s; }
        footer a:hover { color: var(--accent); }

        .section-title { font-weight: 800; color: var(--midnight); margin-bottom: 30px; text-transform: uppercase; position: relative; display: inline-block; }
        .section-title::after { content: ''; display: block; width: 60%; height: 4px; background: var(--accent); margin-top: 5px; }
        
        .badge-cart { position: absolute; top: -5px; right: -8px; background: #ff3b30; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; justify-content: center; align-items: center; }
        
        /* GAMBAR PRODUK */
        .product-img { width: 100%; height: 220px; object-fit: cover; object-position: center; }
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
                    <li class="nav-item"><a class="nav-link" href="index.php">BERANDA</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=history">RIWAYAT</a></li>
                    <li class="nav-item position-relative me-4">
                        <a class="nav-link" href="index.php?page=cart">
                            KERANJANG <i class="bi bi-cart-fill"></i>
                            <?php if(cart_count()>0){ ?><span class="badge-cart"><?php echo cart_count(); ?></span><?php } ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <div class="header-tools">
                            <i id="icon-waktu" class="bi bi-sun-fill text-warning"></i>
                            <span id="jam-digital" class="fw-bold">00:00</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="min-height: 80vh;">

        <?php if (isset($_GET['page']) && $_GET['page'] == 'history') { ?>
            <div class="container py-5">
                <h3 class="section-title">Riwayat Transaksi</h3>
                
                <?php if (empty($_SESSION['order_history'])) { ?>
                    <div class="text-center py-5 bg-white rounded shadow-sm">
                        <i class="bi bi-clock-history display-1 text-muted"></i>
                        <h4 class="mt-3">Belum ada riwayat pesanan</h4>
                        <a href="index.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <?php foreach ($_SESSION['order_history'] as $o) { ?>
                            <div class="col-12 mb-4">
                                <div class="card border-start border-5 border-primary">
                                    <div class="card-header bg-white d-flex justify-content-between">
                                        <span class="fw-bold text-primary">ID: <?php echo $o['id']; ?></span>
                                        <span class="badge bg-dark"><?php echo $o['tanggal']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="fw-bold"><?php echo $o['nama_pembeli']; ?></h5>
                                                <p class="mb-1 small text-muted"><i class="bi bi-geo-alt"></i> <?php echo $o['alamat']; ?></p>
                                                <span class="badge bg-secondary"><?php echo $o['metode']; ?></span>
                                            </div>
                                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                                <small class="text-muted">Total Bayar</small>
                                                <h3 class="fw-bold text-dark">Rp <?php echo number_format($o['total']); ?></h3>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="fw-bold small mb-2">Rincian Barang:</h6>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($o['items'] as $i) { ?>
                                                    <li class="d-flex justify-content-between small mb-1">
                                                        <span><?php echo $i['nama_barang']; ?> <span class="text-muted">x<?php echo $i['quantity']; ?></span></span>
                                                        <span class="fw-bold">Rp <?php echo number_format($i['subtotal']); ?></span>
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
                <h3 class="section-title">Keranjang Belanja</h3>
                
                <?php if (empty($_SESSION['cart'])) { ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x display-1 text-muted"></i>
                        <h4 class="mt-3">Keranjang Anda Kosong</h4>
                        <a href="index.php" class="btn btn-primary mt-3">Belanja Sekarang</a>
                    </div>
                <?php } else { ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <form action="index.php" method="POST">
                                <input type="hidden" name="update_cart" value="1">
                                <?php $grand=0; foreach ($_SESSION['cart'] as $k=>$v) { $sub=$v['harga']*$v['quantity']; $grand+=$sub; ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-3 col-md-2">
                                                    <img src="<?php echo $v['gambar']; ?>" class="img-fluid rounded" onerror="this.src='<?php echo $img_default; ?>'">
                                                </div>
                                                <div class="col-9 col-md-5">
                                                    <h6 class="fw-bold mb-1"><?php echo $v['nama_barang']; ?></h6>
                                                    <div class="text-primary fw-bold small">Rp <?php echo number_format($v['harga']); ?></div>
                                                </div>
                                                <div class="col-6 col-md-3 mt-3 mt-md-0">
                                                    <input type="number" name="qty[<?php echo $k; ?>]" class="form-control text-center" value="<?php echo $v['quantity']; ?>" min="1">
                                                </div>
                                                <div class="col-6 col-md-2 text-end mt-3 mt-md-0">
                                                    <a href="index.php?remove=<?php echo $v['id']; ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></a>
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
                                    <h5 class="fw-bold mb-4">Ringkasan</h5>
                                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span>Rp <?php echo number_format($grand); ?></span></div>
                                    <div class="d-flex justify-content-between mb-3"><span>Ongkir</span><span class="text-success fw-bold">GRATIS</span></div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-4"><h4 class="fw-bold text-primary">Rp <?php echo number_format($grand); ?></h4></div>
                                    <a href="index.php?page=checkout" class="btn btn-checkout text-center shadow">CHECKOUT SEKARANG</a>
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
                        <div class="card shadow-lg">
                            <div class="card-header bg-dark text-white py-3 text-center">
                                <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2"></i> KONFIRMASI PEMBAYARAN</h4>
                            </div>
                            <div class="card-body p-5">
                                <form action="index.php" method="POST">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">DATA PENERIMA</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="small fw-bold">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                                        <div class="col-md-6"><label class="small fw-bold">Email</label><input type="email" name="email" class="form-control" required></div>
                                        <div class="col-md-6"><label class="small fw-bold">No. Telepon</label><input type="text" name="telepon" class="form-control" required></div>
                                        <div class="col-md-6">
                                            <label class="small fw-bold">Metode Pembayaran</label>
                                            <select name="metode_pembayaran" class="form-select" required>
                                                <option value="Transfer Bank">Transfer Bank</option>
                                                <option value="E-Wallet">E-Wallet</option>
                                                <option value="COD">COD (Bayar di Tempat)</option>
                                            </select>
                                        </div>
                                        <div class="col-12"><label class="small fw-bold">Alamat Lengkap</label><textarea name="alamat" class="form-control" rows="2" required></textarea></div>
                                        <div class="col-md-6"><label class="small fw-bold">Kota</label><input type="text" name="kota" class="form-control" required></div>
                                        <div class="col-md-6"><label class="small fw-bold">Kode Pos</label><input type="text" name="kodepos" class="form-control" required></div>
                                    </div>
                                    <div class="d-grid gap-2 mt-5">
                                        <button type="submit" name="btn_konfirmasi" class="btn btn-checkout btn-lg shadow">
                                            <i class="bi bi-check-circle-fill"></i> KONFIRMASI PEMBAYARAN
                                        </button>
                                        <a href="index.php?page=cart" class="btn btn-outline-secondary rounded-pill">Batal</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php } else { ?>
            <div class="text-center text-white py-5 mb-5 shadow" style="background: linear-gradient(135deg, var(--midnight) 0%, var(--black) 100%);">
                <div class="container">
                    <h1 class="display-4 fw-bold">SELAMAT DATANG</h1>
                    <p class="lead opacity-75">Platform E-Commerce Modern dengan Teknologi Cloud Computing AWS</p>
                    <a href="#katalog" class="btn btn-primary btn-lg mt-3 px-5 shadow">LIHAT PRODUK</a>
                </div>
            </div>

            <div class="container" id="katalog">
                <h3 class="section-title">Katalog Produk</h3>
                <div class="row">
                    <?php
                    $q = mysqli_query($koneksi, "SELECT * FROM produk");
                    if (mysqli_num_rows($q) > 0) {
                        while($d = mysqli_fetch_array($q)) {
                            // Validasi Gambar
                            $gbr = (!empty($d['gambar']) && filter_var($d['gambar'], FILTER_VALIDATE_URL)) ? $d['gambar'] : $img_default;
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div style="height: 220px; overflow: hidden;">
                                    <img src="<?php echo $gbr; ?>" class="product-img" onerror="this.src='<?php echo $img_default; ?>'">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="fw-bold mb-1"><?php echo $d['nama_barang']; ?></h5>
                                    <p class="text-muted small flex-grow-1"><?php echo substr($d['deskripsi'], 0, 80); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                        <h5 class="text-primary fw-bold mb-0">Rp <?php echo number_format($d['harga']); ?></h5>
                                        <form action="index.php" method="POST">
                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <button class="btn btn-dark rounded-pill btn-sm px-3 shadow"><i class="bi bi-cart-plus"></i> BELI</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } } else { ?>
                        <div class="col-12 py-5 text-center text-muted">
                            <h3>Belum ada data produk di database.</h3>
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
                    <h5>TOKO CLOUD</h5>
                    <p class="small text-muted">Aplikasi E-Commerce berbasis Native PHP yang berjalan di infrastruktur AWS EC2. Tugas Mata Kuliah Cloud Computing.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>HUBUNGI KAMI</h5>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> Jln. Universitas Suryakancana, No 12, Pasir Gede.</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> Tlp (07232328)</li>
                        <li><i class="bi bi-envelope me-2"></i> Info@Kelompok_CC.com</li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>JAM OPERASIONAL</h5>
                    <ul class="list-unstyled small text-muted">
                        <li>Senin - Jumat: 08.00 - 20.00</li>
                        <li>Sabtu - Minggu: 09.00 - 18.00</li>
                    </ul>
                </div>
            </div>
            <div class="text-center pt-4 border-top border-secondary mt-3 small text-muted">
                &copy; 2025 Kelompok Ari | Cloud Computing Project | All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // SCRIPT JAM & TEMA
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('jam-digital').innerText = time;
            
            const jam = now.getHours();
            const icon = document.getElementById('icon-waktu');
            if(jam >= 6 && jam < 18) {
                icon.className = 'bi bi-sun-fill text-warning';
            } else {
                icon.className = 'bi bi-moon-stars-fill text-info';
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // SCRIPT POPUP NOTIFIKASI
        <?php if(isset($_SESSION['flash_status'])) { ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['flash_status']; ?>',
                title: '<?php echo $_SESSION['flash_title']; ?>',
                text: '<?php echo $_SESSION['flash_text']; ?>',
                background: '#001233', // Midnight Blue Background
                color: '#ffffff', // White Text
                confirmButtonColor: '#2541b2', // Accent Blue Button
                confirmButtonText: 'OK, Mantap!'
            });
        <?php 
            unset($_SESSION['flash_status']);
            unset($_SESSION['flash_title']);
            unset($_SESSION['flash_text']);
        } ?>
    </script>
</body>
</html>