<?php

require "../ceklogin.php";

// Filter berdasarkan nama kasir
$kasir_filter = isset($_GET['kasir_filter']) ? $_GET['kasir_filter'] : '';

// Filter berdasarkan tanggal
$tanggal_filter = isset($_GET['tanggal_filter']) ? $_GET['tanggal_filter'] : '';

// Buat filter_query untuk menampung kondisi filter
$filter_query = "";

// Periksa jika terdapat filter kasir yang dipilih
if (!empty($kasir_filter)) {
    $filter_query .= " WHERE iduser = '$kasir_filter'";
}

// Periksa jika terdapat filter tanggal yang dipilih
if (!empty($tanggal_filter)) {
    // Jika sudah ada filter sebelumnya, tambahkan kondisi 'AND', jika tidak tambahkan 'WHERE'
    $filter_query .= !empty($filter_query) ? " AND waktu_transaksi LIKE '%$tanggal_filter%'" : " WHERE waktu_transaksi LIKE '%$tanggal_filter%'";
}

// Query untuk mengambil data laporan dengan filter tanggal dan kasir
$sql = "SELECT idlaporan, kode_produk, nama_produk, nama_pelanggan, harga, qty, subtotal, waktu_transaksi FROM laporan" . $filter_query;

$result = mysqli_query($c, $sql);

// Periksa apakah query berhasil dieksekusi
if (!$result) {
    die('Query error: ' . mysqli_error($c));
}

// Hitung total pendapatan dari subtotal transaksi
$total_pendapatan = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $total_pendapatan += $row['subtotal'];
}

// Query untuk mengambil jumlah total baris (produk terjual) dari tabel laporan
$sql = "SELECT COUNT(*) AS total_terjual FROM laporan";
$result = mysqli_query($c, $sql);

// Ambil hasil jumlah total produk yang terjual
$row = mysqli_fetch_assoc($result);
$total_terjual = $row['total_terjual'];

$sql_total_produk = "SELECT COUNT(*) AS total_produk FROM produk";
$result_total_produk = mysqli_query($c, $sql_total_produk);

if (!$result_total_produk) {
    die('Query error: ' . mysqli_error($c));
}

$row_total_produk = mysqli_fetch_assoc($result_total_produk);
$total_produk = $row_total_produk['total_produk'];

$persentase_terjual = 0;

if ($total_produk != 0) {
    $persentase_terjual = ($total_terjual / $total_produk) * 100;
}

function getKasirName($iduser, $c) {
    $query = "SELECT username FROM login WHERE iduser = $iduser"; // Sesuaikan dengan nama tabel dan kolom yang benar
    $result = mysqli_query($c, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['username'];
    } else {
        return "Unknown";
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
    <title>Data Laporan</title>
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
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt fa-fw"></i>
                    <span>Dashboard</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="produk.php">
                    <i class="fas fa-shopping-cart fa-fw"></i>
                    <span>Produk</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="transaksi.php">
                    <i class="fas fa-fw fa-desktop"></i>
                    <span>Transaksi</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-chart-bar fa-fw"></i>
                    <span>Laporan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pengguna.php">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Pengguna</span></a>
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
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
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
                    <div class="row">
                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Terjual</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_terjual; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Pendapatan</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Penjualan
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <?php
                                                echo "<div class='h5 mb-0 mr-3 font-weight-bold text-gray-800'>" . number_format($persentase_terjual, 0) . "%</div>";
                                                ?>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: 50%" aria-valuenow="50" aria-valuemin="0"
                                                            aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                    </div>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Laporan</h6>
                        </div>
                        <div class="card-body">
                            <form action="" method="GET">
                                <div class="form-row align-items-center">
                                    <!-- Filter berdasarkan tanggal -->
                                    <div class="col-auto">
                                        <label class="sr-only" for="start_date">Pilih Tanggal</label>
                                        <input type="date" class="form-control mb-2" id="start_date" name="tanggal_filter" value="<?php echo htmlspecialchars($tanggal_filter); ?>">
                                    </div>
                                    <!-- Filter berdasarkan nama kasir -->
                                    <div class="col-auto">
                                    <label class="sr-only" for="kasir">Pilih Kasir</label>
                                    <select class="form-control mb-2" id="kasir" name="kasir_filter">
                                        <option value="" <?php echo empty($kasir_filter) ? 'selected' : ''; ?>>Tampilkan Semua</option>
                                        <?php
                                        // Ambil data kasir dari tabel login dengan peran "kasir"
                                        $queryKasir = "SELECT iduser, username FROM login WHERE role = 'kasir'";
                                        $resultKasir = mysqli_query($c, $queryKasir);
                                        while ($rowKasir = mysqli_fetch_assoc($resultKasir)) {
                                            $selected = ($kasir_filter == $rowKasir['iduser']) ? 'selected' : ''; // Tandai yang dipilih
                                            echo "<option value='{$rowKasir['iduser']}' {$selected}>{$rowKasir['username']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                    <!-- Tombol untuk melakukan filter -->
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary mb-2">Filter</button>
                                    </div>
                                    <div class="col-auto">
                                        <a href="export_pdf.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-success mb-2">Print PDF</a>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kasir</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>SubTotal</th>
                                    <th>Pembayaran</th>
                                    <th>Kembalian</th>
                                    <th>Tanggal</th>
                                </tr>
                                </thead>
                                <tbody>
                                        <?php
                                        // Ambil data transaksi dari database dan tampilkan dalam tabel
                                        $queryGetTransaksi = "SELECT * FROM laporan" . $filter_query;
                                        $resultGetTransaksi = mysqli_query($c, $queryGetTransaksi);
                                        $counter = 1;
                                        while ($rowTransaksi = mysqli_fetch_assoc($resultGetTransaksi)) {
                                            echo "<tr>";
                                            echo "<td>{$counter}</td>"; // Nomor urut
                                            echo "<td>" . getKasirName($rowTransaksi['iduser'], $c) . "</td>"; // Nama Kasir
                                            echo "<td>{$rowTransaksi['nama_pelanggan']}</td>"; // Nama Pelanggan
                                            echo "<td>{$rowTransaksi['nama_produk']}</td>"; // Nama Produk
                                            echo "<td>{$rowTransaksi['qty']}</td>"; // Qty
                                            echo "<td>Rp " . number_format($rowTransaksi['subtotal'], 0, ',', '.') . "</td>"; // Subtotal
                                            echo "<td>Rp " . number_format($rowTransaksi['pembayaran'], 0, ',', '.') . "</td>"; // Pembayaran (Sesuaikan dengan kolom yang sesuai)
                                            echo "<td>Rp " . number_format($rowTransaksi['kembalian'], 0, ',', '.') . "</td>"; // Kembalian (Sesuaikan dengan kolom yang sesuai)
                                            echo "<td>{$rowTransaksi['waktu_transaksi']}</td>"; // Waktu TransaksI
                                            echo "</tr>";
                                            $counter++;
                                        }
                                        ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="text-center my-auto">
                        <span>Budayakan mencuci tangan</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin Ingin Keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" jika Anda siap untuk mengakhiri sesi ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
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
</body>

</html>
