<?php
session_start();

// Redirect logged-in users
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// --- 1. Database Connection and Data Fetching (Requires a database) ---
$age_data = [
    '0-17' => 0,
    '18-30' => 0,
    '31-50' => 0,
    '51+' => 0,
];
$labels = array_keys($age_data);

// You MUST configure your database connection here
$host = 'localhost';
$db   = 'nama_database_anda';
$user = 'user_database_anda';
$pass = 'password_database_anda';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch all dates of birth
    $stmt = $pdo->query("SELECT tgl_lahir FROM anggota");
    $birthdates = $stmt->fetchAll();

    $today = new DateTime();
    foreach ($birthdates as $row) {
        if (!empty($row['tgl_lahir'])) {
            try {
                $dob = new DateTime($row['tgl_lahir']);
                // Calculate age in years
                $age = $dob->diff($today)->y;

                // Group into age ranges
                if ($age >= 0 && $age <= 17) {
                    $age_data['0-17']++;
                } elseif ($age >= 18 && $age <= 30) {
                    $age_data['18-30']++;
                } elseif ($age >= 31 && $age <= 50) {
                    $age_data['31-50']++;
                } elseif ($age >= 51) {
                    $age_data['51+']++;
                }
            } catch (Exception $e) {
                // Log or handle invalid date formats if necessary
            }
        }
    }
} catch (PDOException $e) {
    // In a real application, you would log this error and show a generic message.
    // For this example, we'll keep the counts as 0 if the connection fails.
    // echo "Database Error: " . $e->getMessage();
}

// Data array for Chart.js
$chart_data_json = json_encode(array_values($age_data));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Silsilah Keluarga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="style.css" rel="stylesheet">
    <style>
        /* Optional custom styling for better visual separation */
        .chart-container-box {
            padding: 20px;
            background-color: #ffffff; /* White background for the chart area */
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Soft shadow */
        }
        .login-card {
            min-height: 400px; /* Ensure vertical alignment is centered */
        }
    </style>
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="row w-100" style="max-width: 1000px;">
            
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="chart-container-box">
                    <h4 class="text-center mb-4">Distribusi Anggota Keluarga Berdasarkan Usia</h4>
                    <canvas id="ageBarChart"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-lg p-4 login-card d-flex flex-column justify-content-center">
                    <h2 class="text-center mb-4">Login</h2>
                    <form action="auth.php" method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                        <div class="text-center mt-3">
                            </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('ageBarChart').getContext('2d');
            
            const ageData = <?php echo $chart_data_json; ?>;
            const labels = <?php echo json_encode($labels); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Anggota',
                        data: ageData,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)', // Red for 0-17
                            'rgba(54, 162, 235, 0.6)', // Blue for 18-30
                            'rgba(255, 206, 86, 0.6)', // Yellow for 31-50
                            'rgba(75, 192, 192, 0.6)'  // Green for 51+
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Anggota'
                            },
                            // Ensure Y-axis displays whole numbers for counts
                            ticks: {
                                precision: 0 
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Rentang Usia (Tahun)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>