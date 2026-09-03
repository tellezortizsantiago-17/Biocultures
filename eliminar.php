<?php
require_once 'conexion.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM ensayos WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        // Silencioso por ahora
    }
}

header('Location: index.php');
exit;
