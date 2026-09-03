<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Clases.php';
require_once 'conexion.php'; // Incluye la conexión PDO

$mensajeError = "";

// 1. Procesamiento del Formulario e Inserción en BD (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreMicro = trim($_POST['microorganismo'] ?? '');
    $tipoMicro = trim($_POST['tipo'] ?? '');
    $nombreMedio = trim($_POST['medio'] ?? '');
    $temp = filter_var($_POST['temperatura'] ?? 0, FILTER_VALIDATE_INT);
    $horas = filter_var($_POST['horas'] ?? 0, FILTER_VALIDATE_INT);

    if (!empty($nombreMicro) && !empty($tipoMicro) && !empty($nombreMedio) && $temp !== false && $horas !== false) {
        
        // Crear objetos POO para usar la regla de negocio
        $micro = new Microorganismo($nombreMicro, $tipoMicro);
        $medio = new MedioCultivo($nombreMedio);
        $resultado = EvaluadorCrecimiento::clasificar($temp, $horas);

        // Guardar en la base de datos MySQL mediante $pdo
        $sql = "INSERT INTO ensayos (microorganismo, tipo, medio, temperatura, horas, resultado) 
                VALUES (:microorganismo, :tipo, :medio, :temperatura, :horas, :resultado)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':microorganismo' => $micro->getNombre(),
            ':tipo'          => $micro->getTipo(),
            ':medio'         => $medio->getNombre(),
            ':temperatura'   => $temp,
            ':horas'         => $horas,
            ':resultado'     => $resultado
        ]);

        header('Location: index.php');
        exit;
    } else {
        $mensajeError = "Por favor, llene todos los campos con valores válidos.";
    }
}

// 2. Consulta y Búsqueda desde MySQL (GET)
$busqueda = trim($_GET['buscar'] ?? '');

if (!empty($busqueda)) {
    $sql = "SELECT * FROM ensayos WHERE microorganismo LIKE :buscar OR resultado LIKE :buscar ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':buscar' => "%$busqueda%"]);
} else {
    $sql = "SELECT * FROM ensayos ORDER BY id DESC";
    $stmt = $pdo->query($sql);
}

$filas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BioCulture - Crecimiento Microbiológico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4 text-success">BioCulture 🌱</h1>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert alert-danger"><?= $mensajeError ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">Registrar Nuevo Ensayo</div>
                <div class="card-body">
                    <form action="index.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label">Microorganismo:</label>
                            <input type="text" name="microorganismo" class="form-control" required placeholder="Ej: E. coli">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tipo:</label>
                            <select name="tipo" class="form-select" required>
                                <option value="Bacteria">Bacteria</option>
                                <option value="Hongo">Hongo</option>
                                <option value="Levadura">Levadura</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Medio de Cultivo:</label>
                            <input type="text" name="medio" class="form-control" required placeholder="Ej: Agar Nutritivo">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Temperatura (°C):</label>
                            <input type="number" name="temperatura" class="form-control" required placeholder="25">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiempo Incubación (Horas):</label>
                            <input type="number" name="horas" class="form-control" required placeholder="24">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Guardar Ensayo</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla y Búsqueda -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Ensayos Registrados</span>
                    <form action="index.php" method="GET" class="d-flex gap-2">
                        <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Buscar</button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (empty($filas)): ?>
                        <p class="text-muted text-center">No hay ensayos registrados o no coinciden con la búsqueda.</p>
                    <?php else: ?>
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Microorganismo</th>
                                    <th>Temperatura</th>
                                    <th>Resultado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filas as $fila): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fila['microorganismo']) ?></td>
                                        <td><?= $fila['temperatura'] ?> °C</td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?= htmlspecialchars($fila['resultado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="detalle.php?id=<?= $fila['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
<a href="eliminar.php?id=<?= $fila['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ensayo?');">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
