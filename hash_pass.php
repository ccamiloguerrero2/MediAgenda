<?php
$contrasenaPlana = 'Angelyo1'; // ¡Cambia esto!
$hash = password_hash($contrasenaPlana, PASSWORD_DEFAULT);
echo "Contraseña: " . $contrasenaPlana . "<br>";
echo "Hash: " . $hash;
?>