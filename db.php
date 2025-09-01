<?php
$servername = "localhost";
$username = "root"; // MySQL kullanıcı adınız
$password = ""; // MySQL şifreniz
$dbname = "on_muhasebe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
?>