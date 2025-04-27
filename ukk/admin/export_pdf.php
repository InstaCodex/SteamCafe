<?php
require "../ceklogin.php";
require_once '../vendor/autoload.php'; // Make sure you have TCPDF installed via Composer

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
$sql = "SELECT l.idlaporan, l.kode_produk, l.nama_produk, l.nama_pelanggan, l.harga, l.qty, l.subtotal, l.waktu_transaksi, l.iduser, p.harga_jual 
        FROM laporan l 
        LEFT JOIN produk p ON l.kode_produk = p.kode_produk" . $filter_query;

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

// Reset pointer to beginning of result set
mysqli_data_seek($result, 0);

// Function to get kasir name
function getKasirName($iduser, $c) {
    $query = "SELECT username FROM login WHERE iduser = $iduser";
    $result = mysqli_query($c, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['username'];
    } else {
        return "Unknown";
    }
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Steam Cafe');
$pdf->SetTitle('Laporan Penjualan');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Laporan Penjualan', 'Steam Cafe');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$pdf->Cell(0, 15, 'LAPORAN PENJUALAN', 0, 1, 'C');
$pdf->Ln(10);

// Filter information
if (!empty($kasir_filter) || !empty($tanggal_filter)) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 10, 'Filter:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    if (!empty($kasir_filter)) {
        $kasir_name = getKasirName($kasir_filter, $c);
        $pdf->Cell(30, 10, 'Kasir:', 0, 0);
        $pdf->Cell(0, 10, $kasir_name, 0, 1);
    }
    
    if (!empty($tanggal_filter)) {
        $pdf->Cell(30, 10, 'Tanggal:', 0, 0);
        $pdf->Cell(0, 10, $tanggal_filter, 0, 1);
    }
    
    $pdf->Ln(5);
}

// Summary information
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, 'Ringkasan:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 10, 'Total Pendapatan:', 0, 0);
$pdf->Cell(0, 10, 'Rp ' . number_format($total_pendapatan, 0, ',', '.'), 0, 1);
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(10, 10, 'No', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Kasir', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Pelanggan', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Produk', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Qty', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Harga Satuan', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Subtotal', 1, 1, 'C', true);

// Table data
$pdf->SetFont('helvetica', '', 10);
$counter = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell(10, 10, $counter, 1, 0, 'C');
    $pdf->Cell(35, 10, getKasirName($row['iduser'], $c), 1, 0, 'L');
    $pdf->Cell(35, 10, $row['nama_pelanggan'], 1, 0, 'L');
    $pdf->Cell(35, 10, $row['nama_produk'], 1, 0, 'L');
    $pdf->Cell(15, 10, $row['qty'], 1, 0, 'C');
    $pdf->Cell(30, 10, 'Rp ' . number_format($row['harga_jual'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(30, 10, 'Rp ' . number_format($row['subtotal'], 0, ',', '.'), 1, 1, 'R');
    $counter++;
}

// Output the PDF
$pdf->Output('Laporan_Penjualan.pdf', 'D');
?> 