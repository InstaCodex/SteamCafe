<?php

require "../ceklogin.php";

// Proses penyimpanan data transaksi ke dalam database laporan
if (isset($_POST['pembayaran'])) {
    // Ambil data pembayaran dan kembalian dari formulir
    $pembayaran = $_POST['pembayaran'];
    $kembalian = $_POST['kembalian'];

    // Ambil data transaksi dari tabel transaksi
    $queryGetTransaksi = "SELECT * FROM transaksi";
    $resultGetTransaksi = mysqli_query($c, $queryGetTransaksi);

    // Ambil ID pengguna dari sesi
    $iduser = $_SESSION['iduser'];

    // Lakukan perulangan untuk menyimpan setiap transaksi ke dalam tabel laporan_transaksi
    while ($rowTransaksi = mysqli_fetch_assoc($resultGetTransaksi)) {
        $iduser = $rowTransaksi['iduser'];
        $idproduk = $rowTransaksi['idproduk'];
        $idtransaksi = $rowTransaksi['idtransaksi']; // Get the actual transaction ID
        $kode_produk = $rowTransaksi['kode_produk'];
        $nama_produk = $rowTransaksi['nama_produk'];
        $nama_pelanggan = $rowTransaksi['nama_pelanggan'];
        $harga = $rowTransaksi['harga'];
        $qty = $rowTransaksi['qty'];
        $subtotal = $rowTransaksi['subtotal'];

        // Ambil nama kasir dari sesi
        $nama_kasir = $_SESSION['username'];

        // Simpan data transaksi ke dalam tabel laporan_transaksi, termasuk ID pengguna
        $inserted = insertTransactionToLaporan($c, $iduser, $idtransaksi, $kode_produk, $nama_produk, $nama_pelanggan, $harga, $qty, $subtotal, $pembayaran, $kembalian);
        if (!$inserted) {
            // Jika gagal menyimpan, berikan pesan kesalahan
            echo '<script>alert("Gagal menyimpan data transaksi ke laporan.");</script>';
        }
    }

    // Setelah semua transaksi disimpan, kosongkan tabel transaksi
    $queryDeleteTransaksi = "DELETE FROM transaksi";
    $resultDeleteTransaksi = mysqli_query($c, $queryDeleteTransaksi);

    if ($resultDeleteTransaksi) {
        // Jika berhasil mengosongkan tabel transaksi, berikan pesan sukses
        echo '<script>alert("Transaksi berhasil disimpan ke laporan dan data transaksi direset.");</script>';
    } else {
        // Jika gagal mengosongkan tabel transaksi, berikan pesan kesalahan
        echo '<script>alert("Gagal mereset data transaksi.");</script>';
    }
}

if (isset($_POST['InputCart'])) {
    // Ambil data dari formulir
    $kode_produk = $_POST['kode_produk'];
    $nama_produk = $_POST['nama_produk'];
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $harga = $_POST['harga_jual'];
    $qty = $_POST['Cqty'];
    $subtotal = $_POST['Csubs'];
    $pembayaran = $_POST['pembayaran'];
    $kembalian = $_POST['kembalian'];

    // Ambil ID pengguna dari sesi
    $iduser = $_SESSION['iduser']; // Menggunakan ID pengguna

    // Ambil nama kasir dari session
    $nama_kasir = $_SESSION['username'];

    // Ambil ID produk dari database berdasarkan nama produk yang dipilih
    $queryGetIDProduk = "SELECT idproduk FROM produk WHERE nama_produk = '$nama_produk'";
    $resultGetIDProduk = mysqli_query($c, $queryGetIDProduk);
    $row = mysqli_fetch_assoc($resultGetIDProduk);
    $idproduk = $row['idproduk']; // Use this variable for the product ID

    // Check if there's an existing transaction with a different customer name
    $existingTransaction = false;
    $queryCheckExisting = "SELECT nama_pelanggan FROM transaksi LIMIT 1";
    $resultCheckExisting = mysqli_query($c, $queryCheckExisting);
    
    if (mysqli_num_rows($resultCheckExisting) > 0) {
        $rowExisting = mysqli_fetch_assoc($resultCheckExisting);
        $existingCustomerName = $rowExisting['nama_pelanggan'];
        
        if ($existingCustomerName != $nama_pelanggan) {
            $existingTransaction = true;
        }
    }

    if ($existingTransaction) {
        // Jika transaksi sudah ada, tampilkan popup bahwa meja telah digunakan
        echo '<script>alert("Nama pelanggan harus sama untuk semua transaksi!");</script>';
    } else {
        // Validasi jumlah (Qty) tidak boleh kosong atau nol
        if (empty($qty) || $qty <= 0) {
            // Jika jumlah (Qty) kosong atau nol, tampilkan pesan kesalahan
            echo '<script>alert("Jumlah barang harus diisi dan tidak boleh nol!");</script>';
        } else {
            // Cek apakah produk sudah ada dalam transaksi
            $queryCheckProduct = "SELECT * FROM transaksi WHERE kode_produk = '$kode_produk'";
            $resultCheckProduct = mysqli_query($c, $queryCheckProduct);
            
            if (mysqli_num_rows($resultCheckProduct) > 0) {
                // Produk sudah ada, update qty dan subtotal
                $rowProduct = mysqli_fetch_assoc($resultCheckProduct);
                $existingQty = $rowProduct['qty'];
                $existingSubtotal = $rowProduct['subtotal'];
                $existingIdTransaksi = $rowProduct['idtransaksi'];
                
                // Hitung qty dan subtotal baru
                $newQty = $existingQty + $qty;
                $newSubtotal = $newQty * $harga;
                
                // Update transaksi yang ada
                $queryUpdateTransaksi = "UPDATE transaksi SET qty = $newQty, subtotal = $newSubtotal WHERE idtransaksi = $existingIdTransaksi";
                $resultUpdateTransaksi = mysqli_query($c, $queryUpdateTransaksi);
                
                if ($resultUpdateTransaksi) {
                    echo '<script>alert("Jumlah produk berhasil diperbarui!");</script>';
                    header('Location: transaksi.php');
                    exit();
                } else {
                    echo '<script>alert("Gagal memperbarui jumlah produk.");</script>';
                }
            } else {
                // Produk belum ada, tambahkan sebagai transaksi baru
                $inserted = insertTransaction($c, $iduser, $idproduk, $kode_produk, $nama_produk, $nama_pelanggan, $harga, $qty, $subtotal);
                if ($inserted) {
                    // Jika berhasil dimasukkan, lakukan tindakan yang sesuai, misalnya memberikan pesan sukses atau mengarahkan pengguna ke halaman lain
                    echo '<script>alert("Data transaksi berhasil dimasukkan!");</script>';
                    // Redirect ke halaman yang sesuai
                    header('Location: transaksi.php');
                    exit(); // Penting untuk keluar dari skrip setelah melakukan redirect
                } else {
                    // Jika gagal dimasukkan, berikan pesan kesalahan
                    echo '<script>alert("Gagal memasukkan data transaksi.");</script>';
                }
            }
        }
    }
}

// Proses penghapusan transaksi jika tombol "Hapus" diklik
if (isset($_POST['hapus_transaksi'])) {
    $idtransaksi = $_POST['idtransaksi'];

    // Panggil fungsi untuk menghapus transaksi berdasarkan ID transaksi
    $deleted = deleteTransaction($c, $idtransaksi);

    if ($deleted) {
        // Jika berhasil
        echo '<script>alert("Transaksi berhasil dihapus!");</script>';
        header('location: transaksi.php');
    } else {
        // Jika gagal
        echo '<script>alert("Gagal menghapus transaksi!");</script>';
        header('location: transaksi.php');
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Dashboard</title>
    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.css" rel="stylesheet">
    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="sidebar-brand-text mx-3"> Steam Cafe</div>
            </a>
            <!-- Divider -->
            <hr class="sidebar-divider">
            <!-- Heading -->
            <div class="sidebar-heading">
                Menu
            </div>
            <!-- Nav Item - Tables -->
            <li class="nav-item">
                <a class="nav-link" href="transaksi.php">
                    <i class="fas fa-fw fa-desktop"></i>
                    <span>Transaksi</span></a>
            </li>
            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white-atas topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <form class="form-inline">
                        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                            <i class="fa fa-bars"></i>
                        </button>
                    </form>
                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 big"><?php echo $_SESSION['username']; ?></span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <div class="container-fluid">
                    <!-- Content Row -->
                    <div class="container-fluid">
                        <!-- DataTales Example -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Data Transaksi</h6>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="row">
                                        <div class="col-sm-4 col-md-4 col-lg-3 mb-3">
                                            <div class="position-relative">
                                                <label class="small text-muted mb-1">Nama Produk</label>
                                                <div class="input-group">
                                                    <select name="nama_produk" class="form-control form-control-sm" onchange="changeValue(this.value)">
                                                        <option value="">Pilih Nama Produk</option>
                                                        <!-- Opsi default kosong -->
                                                        <?php
                                                        $getProduk = mysqli_query($c, "SELECT * FROM produk");
                                                        while ($pl = mysqli_fetch_array($getProduk)) {
                                                            $nama_produk = $pl['nama_produk'];
                                                        ?>
                                                            <option value="<?= $nama_produk; ?>">
                                                                <?= $nama_produk; ?>
                                                            </option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text icon-qr" style="cursor: pointer;" onclick="openQrScanner()">
                                                            <i class="z text-muted"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-4 col-md-4 col-lg-3 mb-3">
                                            <label class="small text-muted mb-1">Kode Produk</label>
                                            <input type="text" name="kode_produk" id="kode_produk" class="form-control form-control-sm bg-light" readonly>
                                            <input type="hidden" name="harga_modal" id="harga_modal">
                                        </div>
                                        <div class="col-8 col-sm-4 col-md-4 col-lg-2 mb-3">
                                            <label class="small text-muted mb-1">Harga</label>
                                            <input type="number" name="harga_jual" placeholder="0" id="harga_jual" onchange="InputSub()" class="form-control form-control-sm bg-light" readonly>
                                        </div>
                                        <div class="col-4 col-sm-4 col-md-4 col-lg-1 mb-3">
                                            <label class="small text-muted mb-1">Jumlah</label>
                                            <input type="number" name="Cqty" id="Iqty" onchange="InputSub()" placeholder="0" class="form-control form-control-sm" required min="1">
                                        </div>
                                        <div class="col-sm-4 col-md-4 col-lg-3 mb-3">
                                            <label class="small text-muted mb-1">Nama Pelanggan</label>
                                            <input type="text" name="nama_pelanggan" pattern="[A-Za-z\s]+" title="Hanya huruf yang diperbolehkan" id="nama_pelanggan" placeholder="Nama Pelanggan" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-sm-8 col-md-8 col-lg-3 mb-3">
                                            <label class="small text-muted mb-1">Subtotal</label>
                                            <div class="input-group">
                                                <input type="number" name="Csubs" placeholder="0" id="Isubtotal" onchange="InputSub()" class="form-control form-control-sm bg-light mr-2" readonly>
                                                <div class="input-group-append">
                                                    <button type="reset" class="btn btn-danger btn-sm mr-2">Reset</button>
                                                    <button type="submit" name="InputCart" class="btn btn-primary btn-sm">Simpan</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- end row -->
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Kasir</th>
                                                <th>Nama Produk</th>
                                                <th>Nama Pelanggan</th>
                                                <th>Harga</th>
                                                <th>Jumlah</th>
                                                <th>Subtotal</th>
                                                <th>Opsi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Ambil data transaksi dari database dan tampilkan dalam tabel
                                            $queryGetTransaksi = "SELECT * FROM transaksi";
                                            $resultGetTransaksi = mysqli_query($c, $queryGetTransaksi);
                                            $counter = 1;
                                            while ($rowTransaksi = mysqli_fetch_assoc($resultGetTransaksi)) {
                                                echo "<tr>";
                                                echo "<td>{$counter}</td>";
                                                echo "<td>{$_SESSION['username']}</td>"; // Nama Kasir
                                                echo "<td>{$rowTransaksi['nama_produk']}</td>";
                                                echo "<td>{$rowTransaksi['nama_pelanggan']}</td>";
                                                echo "<td>Rp " . number_format($rowTransaksi['harga'], 0, ',', '.') . "</td>"; // Subtotal
                                                echo "<td>{$rowTransaksi['qty']}</td>";
                                                echo "<td>Rp " . number_format($rowTransaksi['subtotal'], 0, ',', '.') . "</td>"; // Subtotal
                                                echo "<td>
                                                <form method='post'>
                                                    <input type='hidden' name='idtransaksi' value='{$rowTransaksi['idtransaksi']}'>
                                                    <button type='submit' name='hapus_transaksi' class='btn btn-danger btn-sm'>Hapus</button>
                                                </form>
                                            </td>";
                                                echo "</tr>";
                                                $counter++;
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="bg-light p-3" style="border-radius:0.25rem;">
                                    <div class="row gy-3 align-items-center row-home">

                                        <div class="col-md-8 col-lg-6 mb-2">
                                            <form method="post" onsubmit="return validatePayment()">
                                                <input type="hidden" id="totalCart" value="">
                                                <div class="row">
                                                    <label for="pembayaran" class="col-4 col-sm-4 col-md-4 col-lg-3 col-form-label col-form-label-sm mb-2">Pembayaran</label>
                                                    <div class="col-8 col-sm-8 col-md-8 col-lg-9 mb-2">
                                                        <input type="text" name="pembayaran" onchange="procesBayar()" class="form-control form-control-sm" id="pembayaran" placeholder="Pembayaran" required>
                                                    </div>
                                                    <label for="kembalian" class="col-4 col-sm-4 col-md-4 col-lg-3 col-form-label col-form-label-sm mb-2">Kembalian</label>
                                                    <div class="col-8 col-sm-8 col-md-8 col-lg-9 mb-2">
                                                        <input type="text" class="form-control form-control-sm bg-light" id="kembalian" placeholder="0" readonly>
                                                        <input type="hidden" name="kembalian" id="kembalian1">
                                                    </div>
                                                    <div class="col-sm-12 text-right">
                                                        <div class="d-block d-sm-block d-md-none d-lg-none py-1"></div>
                                                        <button type="reset" class="btn btn-danger btn-sm px-3 mr-2">
                                                            <i class="fa fa-trash-alt mr-1"></i>Hapus Semua</button>
                                                        <button class="btn btn-primary btn-sm px-3">
                                                            <i class="fa fa-check mr-1"></i>Bayar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-4 col-lg-6 mb-2 text-primary text-right">
                                            <p class="small text-muted mb-0">Total Item</p>
                                            <?php
                                            // Inisialisasi variabel total item
                                            $total_item = 0;

                                            // Ambil data transaksi dari database dan tampilkan dalam tabel
                                            $queryGetTransaksi = "SELECT * FROM transaksi";
                                            $resultGetTransaksi = mysqli_query($c, $queryGetTransaksi);

                                            // Lakukan perulangan untuk mengambil subtotal dari setiap transaksi
                                            while ($rowTransaksi = mysqli_fetch_assoc($resultGetTransaksi)) {
                                                // Tambahkan subtotal transaksi ke total item
                                                $total_item += $rowTransaksi['subtotal'];
                                            }

                                            // Tampilkan total item
                                            echo "<h3 class='mb-0' style='font-weight:600;'>Rp. " . number_format($total_item, 0, ',', '.') . "</h3>";
                                            ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer ">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Steam Cafe 2024</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin Ingin Keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Klik Logout Jika Ingin Keluar</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../login.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="js/demo/chart-area-demo.js"></script>

    <script type="text/javascript">
        var produkData = <?php echo json_encode($produkData); ?>;

        function changeValue(nama_produk) {
            var selectedProduk = produkData.find(function(produk) {
                return produk.nama_produk === nama_produk;
            });

            document.getElementById("kode_produk").value = selectedProduk.kode_produk;
            document.getElementById("harga_jual").value = selectedProduk.harga_jual;
            document.getElementById("harga_modal").value = selectedProduk.harga_modal;
        };

        function InputSub() {
            var harga_jual = parseInt(document.getElementById('harga_jual').value);
            var jumlah_beli = parseInt(document.getElementById('Iqty').value);
            
            // Validasi jumlah tidak boleh negatif
            if (jumlah_beli <= 0) {
                alert("Jumlah barang tidak boleh negatif atau nol!");
                document.getElementById('Iqty').value = 1; // Set default value to 1
                jumlah_beli = 1;
            }
            
            var jumlah_harga = harga_jual * jumlah_beli;
            document.getElementById('Isubtotal').value = jumlah_harga;
        };

        function procesBayar() {
            var total_item = parseInt(<?php echo $total_item; ?>);
            var pembayaran = parseInt(document.getElementById('pembayaran').value);
            var kembalian = pembayaran - total_item;

            // Format kembalian sebagai rupiah
            var kembalian_formatted = kembalian.toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });

            // Ganti format kembalian jika nilainya lebih dari 1 juta
            if (kembalian > 1000000) {
                kembalian_formatted = (kembalian / 1000000).toFixed(2) + ' juta';
            } else {
                kembalian_formatted = 'Rp ' + number_format(kembalian, 0, ',', '.');
            }

            // Tampilkan kembalian dalam input kembalian
            document.getElementById('kembalian').value = kembalian_formatted;
            document.getElementById('kembalian1').value = kembalian;
        }

        function validatePayment() {
            var total_item = parseInt(<?php echo $total_item; ?>);
            var pembayaran = parseInt(document.getElementById('pembayaran').value);

            // Validasi jika pembayaran kurang dari total item
            if (pembayaran < total_item) {
                alert("Uang pembayaran tidak mencukupi!");
                return false; // Menghentikan pengiriman formulir
            }

            return true; // Lanjutkan pengiriman formulir jika pembayaran cukup
        }

        // Panggil fungsi InputSub saat nilai input jumlah barang berubah
        document.getElementById('Iqty').addEventListener('change', InputSub);
        
        // Setelah halaman dimuat, cek apakah sudah ada transaksi dan isi nama pelanggan
        window.onload = function() {
            <?php
            // Cek apakah sudah ada transaksi
            $queryCheckExisting = "SELECT nama_pelanggan FROM transaksi LIMIT 1";
            $resultCheckExisting = mysqli_query($c, $queryCheckExisting);
            
            if (mysqli_num_rows($resultCheckExisting) > 0) {
                $rowExisting = mysqli_fetch_assoc($resultCheckExisting);
                $existingCustomerName = $rowExisting['nama_pelanggan'];
                echo "document.getElementById('nama_pelanggan').value = '" . $existingCustomerName . "';";
                echo "document.getElementById('nama_pelanggan').readOnly = true;";
            }
            ?>
        };
    </script>

</body>

</html> 