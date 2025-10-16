
<?php

require "../checker.php"; // otomatis aktifkan logging

$logger = new logger();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if ($user === "anjay" && $pass === "slebew") {
        echo "OKEEEE LOGIN BOSQUEEE";
        $logger->success_login();
        
    } else {
        echo "GAGAL LOGIN";
        $logger->fail_login();

    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN TEST</title>
</head>
<body>
    <form method="post" autocomplete="off">
        <input type="text" name="user" placeholder="user" value="">
        <br><br>
        <input type="password" name="pass" placeholder="pass">
        <br><br>
        <input type="submit" value="submit">
    </form>
</body>
</html>