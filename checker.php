<?php

$config = require __DIR__ . "/config.php";

class logger {

    private ?PDO $pdo = null;
    
    function __construct() {
        global $config;

        try {
            $this->pdo = new PDO(
                $config["db"]["dsn"],
                $config["db"]["user"],
                $config["db"]["pass"],
                $config["db"]["opts"]
            );
        } catch (PDOException $e) {
            die("Koneksi DB Gagal: " . $e->getMessage());
        }

        // --- AUTO-RUN HOOK ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->autoRunParameter(); // tetap dipanggil sesuai logika awal
        }

        if($config["BLOCK_USER"] === true){

            $description_fail = strlen($config["DESCRIPTION_MAX_FAILED_ATTEMPTS"]) == 0 ? "⚠️ IP kamu diblokir sementara 30 detik karena terlalu banyak percobaan gagal." : $config["DESCRIPTION_MAX_FAILED_ATTEMPTS"];

            if ($this->is_ip_blocked($this->get_client_ip())) {
                die($description_fail);
                exit();
            }
        }

    }

    public function getPdo(): ?PDO {
        return $this->pdo;
    }


    /*
    * Fungsi autoRunParameter = untuk otomatis mengisi parameter,
    */
    private function autoRunParameter() {
        $input_user = $_POST['user'] ?? '';
        // $input_pass = $_POST['pass'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $this->get_client_ip();

        return [
            "input_username" => $input_user,
            "ua" => $ua,
            "ip" => $ip
        ];
    }


    /*
    Fungsi fail_login berguna untuk dipanggil jika otentikasi gagal
    */
    public function fail_login() {
        $p = $this->autoRunParameter();
        $this->record_login_attempt($p["ip"], $p["input_username"], $p["ua"], false);
    }


    /*
    * Fungsi success_login berguna untuk dipanggil jika otentikasi berhasil
    */
    public function success_login() {
        $p = $this->autoRunParameter();
        $this->record_login_attempt($p["ip"], $p["input_username"], $p["ua"], true);
    }


    /*
    * Fungsi get_client_ip berguna untuk mengembalikan ip user
    */
    function get_client_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $x = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($x[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }


    /*
    * Fungsi record_login_attempt berfungsi melakukan penyimpanan rekaman login user(gagal maupun berhasil)
    */
    function record_login_attempt($ip, $username, $user_agent, $success) {
        $query = "INSERT INTO login_attempts (ip, username, user_agent, success) VALUES (:ip, :username, :ua, :success)";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ":ip" => $ip,
            ":username" => $username,
            ":ua" => $user_agent,
            ":success" => $success ? 1 : 0
        ]);
    }


    /*
    * Fungsi count_failed_attempts berfungsi untuk menghitung jumlah submit yang gagal
    */
    function count_failed_attempts($ip, $minutes = 100) {
        $sql = "SELECT COUNT(*) FROM login_attempts
                WHERE ip = :ip AND success = 0
                AND created_at >= (NOW() - INTERVAL :m MINUTE)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':m', (int)$minutes, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }


    /*
    * Fungsi get_recent_attempts berfungsi untuk nantinya memvisualisasikan semua riwayat percobaan login yang ada
    */
    function get_recent_attempts($limit = 100) {
        $stmt = $this->pdo->prepare("SELECT * FROM login_attempts ORDER BY created_at DESC LIMIT :l");
        $stmt->bindValue(":l", (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    * Fungsi get_summary_stats berfungsi untuk nantinya memvisualisasikan riwayat gagal maupun berhasil login dari user
    */
    function get_summary_stats(PDO $pdo, $minutes = null, $top = 10) {

        // HITUNG TOTAL PERCOBAAN
        if ($minutes !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total,
                       SUM(success = 0) AS failed,
                       SUM(success = 1) AS success
                FROM login_attempts
                WHERE created_at >= (NOW() - INTERVAL :m MINUTE)
            ");
            $stmt->bindValue(":m", (int)$minutes, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total,
                       SUM(success = 0) AS failed,
                       SUM(success = 1) AS success
                FROM login_attempts
            ");
        }
        $stmt->execute();
        $tot = $stmt->fetch(PDO::FETCH_ASSOC);

        // TOP IP
        if ($minutes !== null) {
            $stmt = $pdo->prepare("
                SELECT ip, COUNT(*) AS c
                FROM login_attempts
                WHERE created_at >= (NOW() - INTERVAL :m MINUTE)
                GROUP BY ip
                ORDER BY c DESC
                LIMIT :top
            ");
            $stmt->bindValue(':m', (int)$minutes, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare("
                SELECT ip, COUNT(*) AS c
                FROM login_attempts
                GROUP BY ip
                ORDER BY c DESC
                LIMIT :top
            ");
        }
        $stmt->bindValue(':top', (int)$top, PDO::PARAM_INT);
        $stmt->execute();
        $top_ip = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // TOP USERNAME
        if ($minutes !== null) {
            $stmt = $pdo->prepare("
                SELECT username, COUNT(*) AS c
                FROM login_attempts
                WHERE created_at >= (NOW() - INTERVAL :m MINUTE)
                GROUP BY username
                ORDER BY c DESC
                LIMIT :top
            ");
            $stmt->bindValue(':m', (int)$minutes, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare("
                SELECT username, COUNT(*) AS c
                FROM login_attempts
                GROUP BY username
                ORDER BY c DESC
                LIMIT :top
            ");
        }
        $stmt->bindValue(':top', (int)$top, PDO::PARAM_INT);
        $stmt->execute();
        $top_user = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'totals' => $tot,
            'top_ip' => $top_ip,
            'top_user' => $top_user
        ];
    }


    /*
    * Fungsi get_recent_attempts berfungsi untuk nantinya memvisualisasikan semua riwayat percobaan login yang ada
    */
    public function view_logs() {
        $logs = $this->get_recent_attempts(200);

        echo '
        <!-- Include DataTables CSS & JS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.jqueryui.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background-color: #f5f6fa;
                margin: 20px;
            }
            h2 {
                text-align: center;
                color: #2f3640;
            }
            table.dataTable {
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                background: white;
            }
            .success {
                color: green;
                font-weight: bold;
            }
            .fail {
                color: red;
                font-weight: bold;
            }
        </style>

        <h2>📜 Recent Login Attempts</h2>
        <table id="logsTable" class="display" style="width:95%; margin:auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>User-Agent</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
        ';

        foreach ($logs as $r) {
            $status = $r['success']
                ? "<span class=\"success\">✅ Success</span>"
                : "<span class=\"fail\">❌ Failed</span>";

            echo "
                <tr>
                    <td>{$r['id']}</td>
                    <td>".htmlspecialchars($r['ip'])."</td>
                    <td>".htmlspecialchars($r['username'])."</td>
                    <td>{$status}</td>
                    <td>".htmlspecialchars($r['user_agent'])."</td>
                    <td>{$r['created_at']}</td>
                </tr>
            ";
        }

        echo '
            </tbody>
        </table>

        <script>
            $(document).ready(function() {
                $("#logsTable").DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    order: [[0, "desc"]],
                    language: {
                        search: "🔍 Cari:",
                        lengthMenu: "Tampilkan _MENU_ data per halaman",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        paginate: {
                            previous: "⬅️",
                            next: "➡️"
                        },
                        zeroRecords: "Tidak ada data ditemukan"
                    }
                });
            });
        </script>
        ';
    }


    /*
    * Untuk visualisasi statistik keseluruhan
    */
    public function view_stats() {
        global $config;
        $minutes = $config['WINDOW_MINUTES'] ?? 10;
        $stats = $this->get_summary_stats($this->pdo, $minutes);

        echo '
        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background: #f5f6fa;
                margin: 20px;
            }
            h2, h3 {
                text-align: center;
                color: #2f3640;
            }
            .stats-container {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 20px;
                margin: 30px 0;
            }
            .stat-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 3px 8px rgba(0,0,0,0.1);
                width: 250px;
                text-align: center;
                transition: 0.3s;
            }
            .stat-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 5px 12px rgba(0,0,0,0.15);
            }
            .stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #273c75;
            }
            .stat-label {
                color: #718093;
                font-size: 0.9em;
            }
            .list-container {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 40px;
            }
            ol {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                padding: 20px 30px;
                width: 300px;
                color: #2f3640;
            }
            li {
                margin-bottom: 8px;
            }
            .ip-item::before {
                content: "🌐 ";
            }
            .user-item::before {
                content: "👤 ";
            }
        </style>

        <h2>📊 Statistik '.$minutes.' Menit Terakhir</h2>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number">'.($stats["totals"]["total"] ?? 0).'</div>
                <div class="stat-label">Total Percobaan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color:#e84118;">'.($stats["totals"]["failed"] ?? 0).'</div>
                <div class="stat-label">Gagal</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color:#44bd32;">'.($stats["totals"]["success"] ?? 0).'</div>
                <div class="stat-label">Sukses</div>
            </div>
        </div>

        <div class="list-container">
            <div>
                <h3>🏆 Top IP</h3>
                <ol>
        ';

        foreach ($stats['top_ip'] as $r) {
            echo "<li class='ip-item'>{$r['ip']} — <strong>{$r['c']}</strong> attempts</li>";
        }

        echo '
                </ol>
            </div>
            <div>
                <h3>👥 Top Username</h3>
                <ol>
        ';

        foreach ($stats['top_user'] as $r) {
            $u = htmlspecialchars($r['username'] ?? '(kosong)');
            echo "<li class='user-item'>{$u} — <strong>{$r['c']}</strong> attempts</li>";
        }

        echo '
                </ol>
            </div>
        </div>
        ';
    }


    /*
    * Fungsi ini untuk melakukan blocking pada attacker
    */
    function is_ip_blocked($ip, $max_failed = 5, $block_duration = 30, $window_seconds = 30) {
        global $config;
        // Ambil N percobaan terakhir IP ini
        $stmt = $this->pdo->prepare("
            SELECT created_at FROM login_attempts
            WHERE ip = :ip AND success = 0
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':limit', (int)$config["MAX_FAILED_INPUT"], PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jika gagal kurang dari batas, tidak diblokir
        if (count($rows) < $config["MAX_FAILED_INPUT"]) {
            return false;
        }

        // Ambil waktu percobaan ke-N terakhir (gagal ke-$max_failed)
        $oldest = end($rows)['created_at'];

        // Hitung selisih waktu sekarang - percobaan ke-N
        $stmt = $this->pdo->query("SELECT TIMESTAMPDIFF(SECOND, '$oldest', NOW()) AS diff");
        $diff = (int)$stmt->fetch(PDO::FETCH_ASSOC)['diff'];

        // Jika semua gagal terjadi dalam rentang $window_seconds, maka blokir selama $block_duration detik
        return ($diff < $config["TIME_FAIL_INPUT"]) && ($diff < $config["BLOCK_DURATION"]);
    }



}
