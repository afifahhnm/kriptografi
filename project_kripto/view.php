<?php
session_start();
include 'config.php';

// Fungsi untuk mengekstrak pesan tersembunyi dari gambar stego
function extractMessageFromStegoImage($imagePath) {
    // Mengambil gambar dan memuatnya dalam format GD
    $image = imagecreatefromjpeg($imagePath);  // Ganti dengan format sesuai gambar (png, gif, dll.)

    if (!$image) {
        return "Gambar tidak valid atau tidak dapat dibaca.";
    }

    $width = imagesx($image); // Lebar gambar
    $height = imagesy($image); // Tinggi gambar
    $message = "";
    $bitCount = 0;

    // Loop untuk memeriksa setiap pixel
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF; // Ambil nilai merah (Red)
            $g = ($rgb >> 8) & 0xFF;  // Ambil nilai hijau (Green)
            $b = $rgb & 0xFF;         // Ambil nilai biru (Blue)

            // Ekstrak bit tersembunyi dari setiap komponen warna
            $message .= chr(($r & 1) << 2 | ($g & 1) << 1 | ($b & 1)); // Menggunakan LSB
            $bitCount++;

            // Stop jika sudah cukup bit untuk pesan
            if ($bitCount % 8 == 0 && $message[$bitCount / 8 - 1] === "\0") {
                break 2; // Pesan telah selesai
            }
        }
    }

    imagedestroy($image); // Lepaskan gambar dari memori

    return $message;
}

// Pastikan admin login
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Cek apakah id laporan ada di URL
if (!isset($_GET['id'])) {
    die("Laporan tidak ditemukan.");
}

$id = $_GET['id'];

// Ambil data laporan berdasarkan ID
$query = $conn->prepare("SELECT * FROM laporan_kejahatan WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    $laporan = $result->fetch_assoc();
} else {
    die("Laporan tidak ditemukan.");
}

// Ekstrak pesan yang tersembunyi dari gambar stego
$stegoImagePath = $laporan['crime_stego_image'];  // Path gambar stego dari database
$hiddenMessage = extractMessageFromStegoImage($stegoImagePath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan Kejahatan</title>
</head>
<body>
    <h1>Detail Laporan Kejahatan</h1>

    <p><strong>Nama Pelapor:</strong> <?php echo htmlspecialchars($laporan['nama_pelapor']); ?></p>
    <p><strong>Usia:</strong> <?php echo htmlspecialchars($laporan['usia']); ?></p>
    <p><strong>Pekerjaan:</strong> <?php echo htmlspecialchars($laporan['pekerjaan']); ?></p>
    <p><strong>Asal:</strong> <?php echo htmlspecialchars($laporan['asal']); ?></p>
    
    <p><strong>Crime Image:</strong> <a href="<?php echo $laporan['crime_image']; ?>" target="_blank">View</a></p>
    <p><strong>Stego Image:</strong> <a href="<?php echo $laporan['crime_stego_image']; ?>" target="_blank">View</a></p>
    
    <h2>Pesan yang Tersembunyi dalam Gambar Stego:</h2>
    <pre><?php echo htmlspecialchars($hiddenMessage); ?></pre> <!-- Menampilkan pesan tersembunyi -->

    <h2>Deskripsi Kejahatan:</h2>
    <p><?php echo htmlspecialchars($laporan['crime_description']); ?></p>

    <!-- Tambahkan aksi lain seperti 'hapus' jika perlu -->
    <a href="delete_report.php?id=<?php echo $laporan['id']; ?>">Hapus Laporan</a>

</body>
</html>
