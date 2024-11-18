<?php
session_start();
require_once 'config.php';
require_once 'encryption_helper.php';

// Initialize encryption
EncryptionHelper::initialize();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Check if form is submitted
    error_log("Form submitted");
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Encrypt main report data
        $nama_pelapor = EncryptionHelper::doubleEncrypt($_POST['nama_pelapor']);
        $usia = $_POST['usia'];
        $pekerjaan = EncryptionHelper::doubleEncrypt($_POST['pekerjaan']);
        $asal = EncryptionHelper::doubleEncrypt($_POST['asal']);
        $jenis_kejahatan = $_POST['jenis_kejahatan'];

        // Insert main report
        $query = $conn->prepare("INSERT INTO laporan (user_id, nama_pelapor, usia, pekerjaan, asal, jenis_kejahatan) VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("isssss", $user_id, $nama_pelapor, $usia, $pekerjaan, $asal, $jenis_kejahatan);
        
        // Debugging: Check if main report insertion is successful
        if (!$query->execute()) {
            throw new Exception("Error inserting main report: " . $conn->error);
        }
        
        // Get the last inserted report ID
        $laporan_id = $conn->insert_id;
        error_log("Laporan ID: " . $laporan_id);

        // Debugging: Check file upload
        error_log("File upload details: " . print_r($_FILES, true));
        
        // Handle file upload - Modified condition to check both file existence and form field
        if (!isset($_FILES['bukti_gambar'])) {
            throw new Exception("No file uploaded!");
        }

        if ($_FILES['bukti_gambar']['error'] !== 0) {
            throw new Exception("File upload error: " . $_FILES['bukti_gambar']['error']);
        }

        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['bukti_gambar']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($filetype, $allowed)) {
            throw new Exception("Hanya file JPG dan PNG yang diperbolehkan!");
        }
        
        // Validate file size (5MB max)
        if ($_FILES['bukti_gambar']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Ukuran file tidak boleh lebih dari 5MB!");
        }
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '.' . $filetype;
        $upload_path = 'uploads/' . $new_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['bukti_gambar']['tmp_name'], $upload_path)) {
            throw new Exception("Gagal mengupload file! Check permissions pada folder uploads.");
        }
        
        // Get and encrypt description
        if (!isset($_POST['deskripsi_kejahatan']) || empty($_POST['deskripsi_kejahatan'])) {
            throw new Exception("Deskripsi kejahatan tidak boleh kosong!");
        }
        
        $deskripsi_kejahatan = EncryptionHelper::doubleEncrypt($_POST['deskripsi_kejahatan']);
        
        // Insert into laporan_bukti
        $query = $conn->prepare("INSERT INTO laporan_bukti (laporan_id, bukti_gambar, deskripsi_kejahatan) VALUES (?, ?, ?)");
        if (!$query) {
            throw new Exception("Error preparing laporan_bukti query: " . $conn->error);
        }
        
        $query->bind_param("iss", $laporan_id, $new_filename, $deskripsi_kejahatan);
        
        // Debugging: Check if laporan_bukti insertion is successful
        if (!$query->execute()) {
            throw new Exception("Error inserting laporan_bukti: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        $success = "Laporan berhasil disubmit!";
        error_log("Report successfully submitted");
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error = $e->getMessage();
        error_log("Error in form submission: " . $error);
    }
}
// Ambil daftar jenis kejahatan
$jenis_kejahatan = $conn->query("SELECT * FROM jenis_kejahatan ORDER BY nama_kejahatan");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pelaporan Kejahatan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #303f9f;
            --accent-color: #ff4081;
            --light-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            padding: 20px;
            color: white;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            text-align: center;
            padding: 20px 0;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--secondary-color);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
        }

        /* Main Content Styling */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f0f2f5;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(48,63,159,0.2);
        }

        select.form-control {
            background-color: white;
        }

        .btn-submit {
            background-color: var(--accent-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #f50057;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
        }
        .btn-logout {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #d32f2f; /* Ganti dengan warna merah */
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-logout:hover {
            background-color: #c62828; /* Ganti dengan warna merah lebih gelap saat hover */
        }
        .btn-stegano{
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #d32f2f; /* Ganti dengan warna merah */
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-stegano:hover {
            background-color: #c62828; /* Ganti dengan warna merah lebih gelap saat hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> E-Lapor</h2>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                <p>Pelapor</p>
                <a href="stegano.php" class="btn-stegano">
                Steganografi 
                </a>
                <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="form-container">
                <h2 class="form-title">Form Laporan Kejahatan</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nama_pelapor">Nama Pelapor</label>
                        <input type="text" id="nama_pelapor" name="nama_pelapor" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="usia">Usia</label>
                        <input type="number" id="usia" name="usia" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="pekerjaan">Pekerjaan</label>
                        <input type="text" id="pekerjaan" name="pekerjaan" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="asal">Asal</label>
                        <input type="text" id="asal" name="asal" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="jenis_kejahatan">Jenis Kejahatan</label>
                        <select id="jenis_kejahatan" name="jenis_kejahatan" class="form-control" required>
                            <option value="">Pilih Jenis Kejahatan</option>
                            <?php while($jenis = $jenis_kejahatan->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($jenis['nama_kejahatan']); ?>">
                                    <?php echo htmlspecialchars($jenis['nama_kejahatan']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                <div class="form-group">
                    <label for="bukti_gambar">Unggah Bukti (Gambar)</label>
                    <input type="file" id="bukti_gambar" name="bukti_gambar" class="form-control" accept="image/png,image/jpeg" required>
                    <small class="text-muted">Format yang didukung: JPG, PNG. Maksimal 5MB</small>
                </div>
                    <div class="form-group">
                    <label for="deskripsi_kejahatan">Deskripsi Kejahatan</label>
                    <textarea id="deskripsi_kejahatan" name="deskripsi_kejahatan" class="form-control" rows="4" required></textarea>
                </div>


                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Laporan
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>