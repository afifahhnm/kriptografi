<?php
session_start();
require_once 'config.php';
require_once 'encryption_helper.php';


// Inisialisasi helper enkripsi
EncryptionHelper::initialize();

// Cek apakah user adalah admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle perubahan status laporan
if (isset($_POST['update_status'])) {
    $laporan_id = $_POST['laporan_id'];
    $status_baru = $_POST['status_baru'];
    
    $query = $conn->prepare("UPDATE laporan SET status_laporan = ? WHERE id = ?");
    $query->bind_param("si", $status_baru, $laporan_id);
    
    if ($query->execute()) {
        $success = "Status berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui status.";
    }
}

// Ambil data laporan dengan filter
$where = "1=1";
if (isset($_GET['filter_status']) && $_GET['filter_status'] !== '') {
    $where .= " AND status_laporan = '" . $conn->real_escape_string($_GET['filter_status']) . "'";
}

if (isset($_GET['filter_jenis']) && $_GET['filter_jenis'] !== '') {
    $where .= " AND jenis_kejahatan = '" . $conn->real_escape_string($_GET['filter_jenis']) . "'";
}

$query = "SELECT * FROM laporan WHERE $where ORDER BY tanggal_laporan DESC";
$result = $conn->query($query);

// Ambil daftar jenis kejahatan untuk filter
$jenis_kejahatan = $conn->query("SELECT DISTINCT nama_kejahatan FROM jenis_kejahatan ORDER BY nama_kejahatan");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #303f9f;
            --accent-color: #ff4081;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
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

        .admin-info {
            text-align: center;
            padding: 20px 0;
        }

        .admin-avatar {
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

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light-color);
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-diproses {
            background-color: var(--warning-color);
            color: white;
        }

        .status-selesai {
            background-color: var(--success-color);
            color: white;
        }

        .status-ditolak {
            background-color: var(--danger-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }

        .btn-status {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-status:hover {
            background-color: var(--primary-color);
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }

        .btn-logout {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: var(--danger-color);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-logout:hover {
            background-color: #d32f2f;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }

            .filter-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Administrator</h3>
                <p>Admin Panel</p>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h2>Dashboard Laporan Kejahatan</h2>
                
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

                <!-- Filter Section -->
                <div class="filter-container">
                    <select class="filter-select" onchange="applyFilter()" id="status-filter">
                        <option value="">Semua Status</option>
                        <option value="diproses">Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditolak">Ditolak</option>
                    </select>

                    <select class="filter-select" onchange="applyFilter()" id="jenis-filter">
                        <option value="">Semua Jenis Kejahatan</option>
                        <?php while($jenis = $jenis_kejahatan->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($jenis['nama_kejahatan']); ?>">
                                <?php echo htmlspecialchars($jenis['nama_kejahatan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Nama Pelapor</th>
                            <th>Usia</th>
                            <th>Pekerjaan</th>
                            <th>Asal</th>
                            <th>Jenis Kejahatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()):
                         $nama_pelapor = EncryptionHelper::decryptCombined($row['nama_pelapor']);
                         $pekerjaan = EncryptionHelper::decryptCombined($row['pekerjaan']);
                         $asal = EncryptionHelper::decryptCombined($row['asal']);
                        ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_laporan'])); ?></td>

                                <td><?php echo htmlspecialchars($nama_pelapor); ?></td>
                                <td><?php echo $row['usia']; ?></td>
                                <td><?php echo htmlspecialchars($pekerjaan); ?></td>
                                <td><?php echo htmlspecialchars($asal); ?></td>
                                <td><?php echo htmlspecialchars($row['jenis_kejahatan']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status_laporan']; ?>">
                                        <?php echo ucfirst($row['status_laporan']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="showStatusModal(<?php echo $row['id']; ?>)" class="btn btn-status">
                                            Update Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Update Status -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Status Laporan</h3>
            <form method="POST" action="">
                <input type="hidden" name="laporan_id" id="modal-laporan-id">
                <div style="margin: 20px 0;">
                    <select name="status_baru" class="filter-select" style="width: 100%;">
                        <option value="diproses">Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_status" class="btn btn-status" style="flex: 1;">Update</button>
                    <button type="button" onclick="hideStatusModal()" class="btn" style="flex: 1; background-color: #ccc;">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showStatusModal(laporanId) {
            document.getElementById('modal-laporan-id').value = laporanId;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function hideStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function applyFilter() {
            const statusFilter = document.getElementById('status-filter').value;
            const jenisFilter = document.getElementById('jenis-filter').value;
            
            let url = window.location.pathname + '?';
            if (statusFilter) url += 'filter_status=' + statusFilter + '&';
            if (jenisFilter) url += 'filter_jenis=' + encodeURIComponent(jenisFilter);
            
            window.location.href = url;
        }

        // Set filter values from URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('filter_status');
            const jenis = urlParams.get('filter_jenis');
            
            if (status) document.getElementById('status-filter').value = status;
            if (jenis) document.getElementById('jenis-filter').value = jenis;
        }
    </script>
</body>
</html>