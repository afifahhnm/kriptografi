<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "laporan_kejahatan";

$conn = new mysqli($servername, $username, $password, $dbname);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mendapatkan username dan password dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cek jika login sebagai admin
    if ($username === 'admin') {
        if ($password === 'admin123') { // Ganti dengan password admin yang aman
            $_SESSION['admin'] = true;
            header("Location: admin_dashboard.php"); // Redirect ke halaman dashboard admin
            exit;
        } else {
            $error = "Password admin salah!";
        }
    } else {
        // Menggunakan prepared statement untuk mencegah SQL injection
        $query = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $query->bind_param("s", $username); // Binding username ke query
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            // Jika user ditemukan, ambil data user
            $user = $result->fetch_assoc();

            // Memeriksa apakah password cocok menggunakan password_verify
            if (password_verify($password, $user['password'])) {
                // Set session user_id jika login berhasil
                $_SESSION['user_id'] = $user['id'];
                header("Location: dashboard.php"); // Redirect ke halaman dashboard
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Mengatur latar belakang */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('https://i.pinimg.com/736x/bb/ff/9d/bbff9d7c211ec3562617493832e0fc7f.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* Kontainer utama untuk login */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Kotak login */
        .login-box {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 350px;
            width: 100%;
        }

        /* Judul */
        .login-box h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Inputan teks */
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        /*Tombol login*/
        .login-box button {
            width: 100%;
            padding: 10px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .login-box button:hover {
            background-color: #00695c;
        }

        /* Link tambahan */
        .extra-links {
            margin-top: 10px;
            font-size: 12px;
        }

        .extra-links a {
            color: #004d40;
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        /* Error message */
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Login Account</h2>
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username or Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">LOGIN</button>
            </form>
            <div class="extra-links">
                <a href="#">Forget your password?</a> | 
                <a href="registrasi.php">Create an account</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="error"><?= $error; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>