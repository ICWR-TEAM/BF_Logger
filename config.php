<?php
// config.php
return [
  'db' => [
    'dsn' => 'mysql:host=localhost;dbname=bf_logger;charset=utf8mb4', 
    'user' => 'root', #USERNAME DB
    'pass' => '', #PASSWORD DB
    'opts' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
    ],
  ],
  "data" => [
    "key_username" => "user"
  ],
  'WINDOW_MINUTES' => 10000, // Atur untuk menyesuaikan log pencatatan terakhir(menit)
  "BLOCK_USER" => true, //true jika menginginkan fitur block user false jika tidak menginginkannya
  "MAX_FAILED_INPUT" => 5,      // berapa kali gagal sebelum blokir | BERELASI DENGAN BLOCK_USER
  "BLOCK_DURATION" => 30,          // durasi blokir (detik) | BERELASI DENGAN BLOCK_USER
  "TIME_FAIL_INPUT" => 30,          // durasi kegagalan | BERELASI DENGAN BLOCK_USER
  "DESCRIPTION_MAX_FAILED_ATTEMPTS" => "⚠️ IP kamu diblokir sementara 30 detik karena terlalu banyak percobaan gagal.", // deskripsi jika percobaan gagal berkali-kali | BERELASI DENGAN BLOCK_USER
];
