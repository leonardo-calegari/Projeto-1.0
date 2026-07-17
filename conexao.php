<?php

$conn = new mysqli(
    "localhost",      // Server host
    "root",           // Username
    "",               // Password
    "credenciamento"  // Schema (banco)
);

if ($conn->connect_error) {
    die("Erro ao conectar: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");