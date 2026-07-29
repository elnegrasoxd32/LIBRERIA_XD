<?php
// Configuración de errores y codificación UTF-8
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

// 1. CREDENCIALES DE CONEXIÓN (Render PostgreSQL)
$db_host = "dpg-d9kllh2jnfac739e8m0g-a"; // Host interno para Web Service en Render
$db_port = "5432";
$db_name = "biblioteca_db_a94n";
$db_login = "admin_user";
$db_pswd  = "qcbHmVRmcbgTLPxWu6eazBkhh0nsEQHh";

try {
    // Conexión mediante PDO a PostgreSQL
    $pdo = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_login, $db_pswd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error de conexión a la BD: " . $e->getMessage()]);
    exit;
}

// Recibir la acción mediante GET o POST
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    // ==========================================
    // 1. OBTENER DETALLE DE UN LIBRO
    // ==========================================
    case 'obtener_detalle':
        // Acepta id_libro tanto por GET como por POST
        $id_libro = intval($_REQUEST['id_libro'] ?? 0);

        if ($id_libro > 0) {
            $sql = "SELECT l.id_libro, l.titulo, l.autor, l.descripcion_corta, l.descripcion, l.stock, l.imagen_url, c.nombre AS categoria 
                    FROM libros l 
                    INNER JOIN categorias c ON l.id_categoria = c.id_categoria 
                    WHERE l.id_libro = :id_libro";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_libro' => $id_libro]);
            $row = $stmt->fetch();

            if ($row) {
                echo json_encode([
                    "status" => "ok",
                    "titulo" => $row['titulo'],
                    "autor" => $row['autor'],
                    "descripcion_corta" => $row['descripcion_corta'],
                    "descripcion" => $row['descripcion'],
                    "categoria" => $row['categoria'],
                    "disponibles" => $row['stock'],
                    "imagen_url" => $row['imagen_url']
                ]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Libro no encontrado en la base de datos"]);
            }
        } else {
            echo json_encode(["status" => "error", "mensaje" => "ID de libro no valido"]);
        }
        break;

    // ==========================================
    // 2. REGISTRO DE USUARIOS
    // ==========================================
    case 'registrar_usuario':
        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($nombre) && !empty($email) && !empty($password)) {
            // Verificar si el correo ya existe
            $stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                echo json_encode(["status" => "error", "mensaje" => "El correo ya está registrado"]);
            } else {
                $sqlInsert = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                
                if ($stmtInsert->execute([':nombre' => $nombre, ':email' => $email, ':password' => $password])) {
                    echo json_encode(["status" => "ok", "mensaje" => "Usuario registrado con éxito"]);
                } else {
                    echo json_encode(["status" => "error", "mensaje" => "Error al registrar"]);
                }
            }
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos requeridos"]);
        }
        break;

    // ==========================================
    // 3. INICIO DE SESIÓN (LOGIN)
    // ==========================================
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $sql = "SELECT id_usuario, nombre, email FROM usuarios WHERE email = :email AND password = :password";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':password' => $password]);
        $row = $stmt->fetch();

        if ($row) {
            echo json_encode([
                "status" => "ok",
                "id_usuario" => $row['id_usuario'],
                "nombre" => $row['nombre'],
                "email" => $row['email']
            ]);
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Correo o contraseña incorrectos"]);
        }
        break;

    // Acción no reconocida
    default:
        echo json_encode(["status" => "error", "mensaje" => "Acción no especificada o no válida"]);
        break;
}
?>