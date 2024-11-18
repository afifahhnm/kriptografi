<?php
// Koneksi ke database
$servername = "localhost";  // Ganti dengan server database Anda
$username = "root";         // Ganti dengan username database Anda
$password = "";             // Ganti dengan password database Anda
$dbname = "laporan_kejahatan"; // Ganti dengan nama database Anda

$conn = new mysqli($servername, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi data input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua kolom harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } else {
        // Cek apakah username sudah ada di database
        $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $query->bind_param("s", $username);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Simpan user baru ke database
            $query = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hash password untuk keamanan
            $query->bind_param("ss", $username, $hashed_password);

            if ($query->execute()) {
                header("Location: login.php"); // Redirect ke halaman login setelah berhasil
                exit;
            } else {
                $error = "Terjadi kesalahan saat menyimpan data!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* Mengatur latar belakang */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('https://i.pinimg.com/originals/6e/cc/87/6ecc87d5b8e8e7f7be9e20910916d56b.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Kontainer utama */
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Kotak registrasi */
        .register-box {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 350px;
            width: 100%;
        }

        /* Judul */
        .register-box h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Inputan teks */
        .register-box input[type="text"],
        .register-box input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        /*Tombol registrasi*/
        .register-box button {
            width: 100%;
            padding: 10px;
            background-color: blue;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .register-box button:hover {
            background-color: #005cbf;
        }

        /* Error message */
        .error {
            color: red;
            margin-top: 10px;
        }

        /* Link ke login */
        .login-link {
            margin-top: 10px;
            font-size: 12px;
        }

        .login-link a {
            color: #004d40;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>Register Account</h2>
            <form action="registrasi.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">REGISTER</button>
            </form>

            <?php if (isset($error)): ?>
                <div class="error"><?= $error; ?></div>
            <?php endif; ?>

            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>
</body>
</html>
