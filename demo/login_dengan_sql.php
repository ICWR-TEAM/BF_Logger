<?php
require "../checker.php";

$logger = new Logger();
$pdo = $logger->getPdo(); // butuh method getPdo() di class Logger

$input_user = $_POST['user'] ?? '';
$input_pass = $_POST['pass'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip = $logger->get_client_ip();

$auth_ok = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil user dari DB berdasarkan username
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([ ':u' => $input_user ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {

        if (password_verify($input_pass, $row['password'])) { 
            $auth_ok = true;
            $message = "Login sukses";
            $logger->success_login();
            // set session, dsb
        } else {
            $message = "Username atau password salah";
            $logger->fail_login();
        }
    } else {
        $message = "Username atau password salah";
    }

}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login</title>
</head>
<body>
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="text" name="user" placeholder="user" value="<?= htmlspecialchars($input_user) ?>">
        <br><br>
        <input type="password" name="pass" placeholder="pass">
        <br><br>
        <input type="submit" value="submit">
    </form>
</body>
</html>
