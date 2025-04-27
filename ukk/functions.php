<?php

session_start();

//bikin koneksi
$c = mysqli_connect('localhost', 'root', '', 'kasir');

// Login
if (isset($_POST['login'])) {
    // inisiasi variabel
    $username = $_POST['username'];
    $password = $_POST['password'];

    $cekuser = mysqli_query($c, "SELECT * from login where username='$username'");
    $hitung = mysqli_num_rows($cekuser);

    if ($hitung > 0) {
        // jika data ditemukan
        $ambildatarole = mysqli_fetch_array($cekuser);
        $user_id = $ambildatarole['iduser']; // Mengambil ID pengguna dari hasil query
        $hashed_password_from_db = $ambildatarole['password']; // Mengambil hash password dari database

        // Verifikasi password
        if (password_verify($password, $hashed_password_from_db)) {
            // Password cocok, lanjutkan proses login
            $role = $ambildatarole['role'];

            // Simpan nama pengguna ke dalam sesi
            $_SESSION['username'] = $username;

            // Simpan ID pengguna ke dalam sesi
            $_SESSION['iduser'] = $user_id;

            if ($role == 'admin') {
                // jika dia admin
                $_SESSION['login'] = true;
                $_SESSION['role'] = 'admin';
                header('location: admin/index.php');
            } else {
                // jika bukan admin
                $_SESSION['login'] = true;
                $_SESSION['role'] = 'kasir';
                header('location: kasir/transaksi.php');
            }
        } else {
            // Password tidak cocok, tampilkan pesan kesalahan
            echo '
                <script>
                    alert("Username atau password salah");
                    window.location.href="login.php";
                </script>
            ';
        }
    } else {
        // jika tidak ditemukan
        echo '
            <script>
                alert("Username atau password salah");
                window.location.href="login.php";
            </script>
        ';
    }
}

//tambah produk
if (isset($_POST['TambahProduk'])) {
    $kode_produk = $_POST['kode_produk'];
    $nama_produk = $_POST['nama_produk'];

    // Lakukan pengecekan apakah kode produk atau nama produk sudah ada dalam database
    $cek_produk = mysqli_query($c, "SELECT * FROM produk WHERE kode_produk = '$kode_produk' OR nama_produk = '$nama_produk'");
    if (mysqli_num_rows($cek_produk) > 0) {
        // Jika sudah ada, tampilkan pesan kesalahan
        echo "<script>alert('Kode Produk atau Nama Produk sudah ada. Silakan coba lagi.');</script>";
    } else {
        // Jika belum ada, lanjutkan proses penyimpanan data ke database
        $harga_modal = $_POST['harga_modal'];
        $harga_jual = $_POST['harga_jual'];

        // Lakukan penyimpanan data ke dalam database
        $insert_produk = mysqli_query($c, "INSERT INTO produk (kode_produk, nama_produk, harga_modal, harga_jual) VALUES ('$kode_produk', '$nama_produk', '$harga_modal', '$harga_jual')");
        if ($insert_produk) {
            echo "<script>alert('Produk berhasil ditambahkan.');</script>";
        } else {
            echo "<script>alert('Gagal menambahkan produk.');</script>";
        }
    }
}

/// Fungsi untuk mengedit produk
if(isset($_POST['editProduk'])){
    $kode_produk = $_POST['kode_produk'];
    $nama_produk_baru = $_POST['nama_produk']; // Nama produk baru yang akan diubah
    $harga_modal = $_POST['harga_modal'];
    $harga_jual = $_POST['harga_jual'];

    // Lakukan pengecekan apakah nama produk yang akan diubah sudah ada di database kecuali untuk produk yang sedang diedit
    $cek_nama_produk = mysqli_query($c, "SELECT * FROM produk WHERE nama_produk = '$nama_produk_baru' AND kode_produk != '$kode_produk'");

    if(mysqli_num_rows($cek_nama_produk) > 0) {
        // Jika nama produk sudah ada di database selain produk yang sedang diedit
        echo '<script>alert("Nama produk sudah ada. Silakan pilih nama produk lain."); window.location.href = "produk.php";</script>';
        exit; // Hentikan eksekusi lebih lanjut
    }

    // Lakukan proses update jika nama produk belum ada di database atau tidak berubah
    $queryupdate = mysqli_query($c, "UPDATE produk SET nama_produk='$nama_produk_baru', harga_modal='$harga_modal', harga_jual='$harga_jual' WHERE kode_produk='$kode_produk'");

    if($queryupdate){
        // Jika berhasil
        echo '<script>alert("Produk berhasil diperbarui!"); window.location.href = "produk.php";</script>';
    } else {
        // Jika gagal
        echo '<script>alert("Gagal memperbarui produk!"); window.location.href = "produk.php";</script>';
    }
}

// Fungsi untuk menghapus produk
if(isset($_POST['hapusProduk'])){
    $kode_produk = $_POST['id_produk'];

    $querydelete = mysqli_query($c, "DELETE FROM produk WHERE kode_produk='$kode_produk'");

    if($querydelete){
        // Jika berhasil
        echo '<script>alert("Produk berhasil dihapus!");</script>';
        header('location: produk.php');
    } else {
        // Jika gagal
        echo '<script>alert("Gagal menghapus produk!");</script>';
        header('location: produk.php');
    }
}

$query = "SELECT * FROM produk";
$result = mysqli_query($c, $query);

// Periksa apakah query berhasil dieksekusi
if (!$result) {
    die('Query error: ' . mysqli_error($c));
}

// Buat array untuk menyimpan data produk
$produkData = array();

// Loop melalui hasil query dan tambahkan setiap produk ke array $produkData
while ($row = mysqli_fetch_assoc($result)) {
    $produk = array(
        'kode_produk' => $row['kode_produk'],
        'nama_produk' => $row['nama_produk'],
        'harga_jual' => $row['harga_jual'],
        'harga_modal' => $row['harga_modal']
        // tambahkan kolom lain yang Anda perlukan
    );
    // Tambahkan produk ke dalam array $produkData
    $produkData[] = $produk;
}

// Konversi array $produkData ke dalam format JSON
$produkDataJSON = json_encode($produkData);

// Fungsi untuk memasukkan data transaksi ke dalam database
function insertTransaction($c, $iduser, $idproduk, $kode_produk, $nama_produk, $nama_pelanggan, $harga, $qty, $subtotal) {
    $query = "INSERT INTO transaksi (iduser, idproduk, kode_produk, nama_produk, nama_pelanggan, harga, qty, subtotal) 
              VALUES ('$iduser', '$idproduk', '$kode_produk', '$nama_produk', '$nama_pelanggan', '$harga', '$qty', '$subtotal')";

    if (mysqli_query($c, $query)) {
        return true;
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($c);
        return false;
    }
}

// Fungsi untuk menghapus transaksi berdasarkan ID transaksi
function deleteTransaction($c, $idtransaksi) {
    $query = "DELETE FROM transaksi WHERE idtransaksi = '$idtransaksi'";
    $result = mysqli_query($c, $query);
    return $result;
}

// Fungsi untuk menyimpan data transaksi ke dalam tabel laporan
function insertTransactionToLaporan($c, $iduser, $idtransaksi, $kode_produk, $nama_produk, $nama_pelanggan, $harga, $qty, $subtotal, $pembayaran, $kembalian) {
    $query = "INSERT INTO laporan (iduser, idtransaksi, kode_produk, nama_produk, nama_pelanggan, harga, qty, subtotal, pembayaran, kembalian) VALUES ('$iduser','$idtransaksi','$kode_produk', '$nama_produk', '$nama_pelanggan', '$harga', '$qty', '$subtotal', '$pembayaran', '$kembalian')";
    $result = mysqli_query($c, $query);
    return $result;
}

// Data pengguna baru
if (isset($_POST['tambahpenggunabaru'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password sebelum disimpan
    $role = $_POST['role'];

    // Periksa apakah username sudah ada dalam database
    $query_cek_username = mysqli_query($c, "SELECT * FROM login WHERE username = '$username'");
    if (mysqli_num_rows($query_cek_username) > 0) {
        // Jika sudah ada, tampilkan pesan pop-up
        echo '
            <script>
                alert("Username sudah ada. Silakan gunakan username lain.");
                window.location.href = "pengguna.php";
            </script>
        ';
    } else {
        // Jika belum ada, lanjutkan proses penambahan pengguna baru
        $query_insert = mysqli_query($c, "INSERT INTO login (username, password, role) VALUES ('$username','$password', '$role')");
        if ($query_insert) {
            // Jika berhasil
            header('location:pengguna.php');
        } else {
            // Jika gagal
            echo '
                <script>
                    alert("Gagal menambahkan pengguna baru.");
                    window.location.href = "pengguna.php";
                </script>
            ';
        }
    }
}

// Edit data pengguna
if(isset($_POST['updatepengguna'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $idnya = $_POST['id'];

    // Periksa apakah username yang diubah sudah ada kecuali untuk pengguna yang sedang diubah
    $query_cek_username = mysqli_query($c, "SELECT * FROM login WHERE username = '$username' AND iduser != '$idnya'");
    if (mysqli_num_rows($query_cek_username) > 0) {
        // Jika sudah ada, tampilkan pesan pop-up
        echo '
            <script>
                alert("Username sudah ada. Silakan gunakan username lain.");
                window.location.href = "pengguna.php";
            </script>
        ';
    } else {
        // Jika belum ada, lanjutkan proses pembaruan data pengguna
        $queryupdate = mysqli_query($c, "UPDATE login SET username='$username', password='$password' WHERE iduser='$idnya'");
        if($queryupdate){
            // Jika berhasil
            header('location:pengguna.php');
        } else {
            // Jika gagal
            echo '
                <script>
                    alert("Gagal mengupdate data pengguna.");
                    window.location.href = "pengguna.php";
                </script>
            ';
        }
    }
}

//hapus pengguna 
if(isset($_POST['hapuspenggunanew'])){
    $iduser = $_POST['id'];

    $querydelete = mysqli_query($c,"delete from login where iduser='$iduser'");

    if($querydelete){
        header('location:pengguna.php');
    } else {
        echo '
        <script>alert("Gagal");
        window.location.href="pengguna.php"
        </script>
        ';
    }
};