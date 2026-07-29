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
    //categoria 
    case 'obtener_por_categoria':
        $id_categoria = intval($_REQUEST['id_categoria'] ?? 0);

        if ($id_categoria > 0) {
            // Consultar la categoría y hasta 2 libros
            $sqlCat = "SELECT nombre FROM categorias WHERE id_categoria = :id_cat";
            $stmtCat = $pdo->prepare($sqlCat);
            $stmtCat->execute([':id_cat' => $id_categoria]);
            $catRow = $stmtCat->fetch();

            $sqlLibros = "SELECT id_libro, titulo, autor, fecha_publicacion, imagen_url, es_proximo 
                          FROM libros 
                          WHERE id_categoria = :id_cat 
                          ORDER BY id_libro ASC LIMIT 2";
            $stmtLibros = $pdo->prepare($sqlLibros);
            $stmtLibros->execute([':id_cat' => $id_categoria]);
            $libros = $stmtLibros->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                "status" => "ok",
                "categoria" => $catRow['nombre'] ?? 'Categoría',
                "libros" => $libros
            ]);
        } else {
            echo json_encode(["status" => "error", "mensaje" => "ID de categoría inválido"]);
        }
        break;  
    case 'buscar':
        $query = trim($_REQUEST['q'] ?? '');

        if (!empty($query)) {
            // 1. Buscar si coincide con el nombre de una CATEGORÍA
            $sqlCat = "SELECT id_categoria, nombre FROM categorias WHERE LOWER(nombre) LIKE LOWER(:q) LIMIT 1";
            $stmtCat = $pdo->prepare($sqlCat);
            $stmtCat->execute([':q' => "%$query%"]);
            $categoria = $stmtCat->fetch();

            if ($categoria) {
                echo json_encode([
                    "status" => "ok",
                    "tipo" => "categoria",
                    "id_categoria" => $categoria['id_categoria'],
                    "nombre" => $categoria['nombre']
                ]);
                break;
            }

            // 2. Si no es categoría, buscar si coincide con el título de un LIBRO
            $sqlLibro = "SELECT id_libro, titulo FROM libros WHERE LOWER(titulo) LIKE LOWER(:q) LIMIT 1";
            $stmtLibro = $pdo->prepare($sqlLibro);
            $stmtLibro->execute([':q' => "%$query%"]);
            $libro = $stmtLibro->fetch();

            if ($libro) {
                echo json_encode([
                    "status" => "ok",
                    "tipo" => "libro",
                    "id_libro" => $libro['id_libro'],
                    "titulo" => $libro['titulo']
                ]);
                break;
            }

            // 3. Si no se encontró nada
            echo json_encode(["status" => "error", "mensaje" => "No se encontraron resultados"]);
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Consulta vacía"]);
        }
        break;
        // ==========================================
    // 📁 PRÉSTAMOS ACTIVOS (Icono Folder)
    // ==========================================
    case 'obtener_prestamos_activos':
        $id_usuario = intval($_REQUEST['id_usuario'] ?? 1); // ID 1 por defecto

        $sql = "SELECT p.id_prestamo, l.titulo, c.nombre AS genero, 
                       TO_CHAR(p.fecha_prestamo, 'DD/MM/YYYY') AS fecha_prestamo 
                FROM prestamos p
                INNER JOIN libros l ON p.id_libro = l.id_libro
                INNER JOIN categorias c ON l.id_categoria = c.id_categoria
                WHERE p.id_usuario = :id_user AND p.estado = 'activo'
                ORDER BY p.fecha_prestamo DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_user' => $id_usuario]);
        
        echo json_encode(["status" => "ok", "prestamos" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ==========================================
    // HISTORIAL DE DEVOLUCIONES (Icono Reloj)
    // ==========================================
    case 'obtener_historial':
        $id_usuario = intval($_REQUEST['id_usuario'] ?? 1);

        $sql = "SELECT p.id_prestamo, l.titulo, c.nombre AS genero, 
                       TO_CHAR(p.fecha_prestamo, 'DD/MM/YYYY') AS fecha_prestamo,
                       TO_CHAR(p.fecha_devolucion, 'DD/MM/YYYY') AS fecha_devolucion 
                FROM prestamos p
                INNER JOIN libros l ON p.id_libro = l.id_libro
                INNER JOIN categorias c ON l.id_categoria = c.id_categoria
                WHERE p.id_usuario = :id_user AND p.estado = 'devuelto'
                ORDER BY p.fecha_devolucion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_user' => $id_usuario]);
        
        echo json_encode(["status" => "ok", "historial" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ==========================================
    //  DEVOLVER LIBRO
    // ==========================================
    case 'devolver_libro':
        $id_prestamo = intval($_REQUEST['id_prestamo'] ?? 0);

        if ($id_prestamo > 0) {
            // 1. Obtener el id_libro para devolverle el stock
            $stmtFind = $pdo->prepare("SELECT id_libro FROM prestamos WHERE id_prestamo = :id_p");
            $stmtFind->execute([':id_p' => $id_prestamo]);
            $row = $stmtFind->fetch();

            if ($row) {
                // 2. Marcar como devuelto
                $sqlDev = "UPDATE prestamos 
                           SET estado = 'devuelto', fecha_devolucion = CURRENT_TIMESTAMP 
                           WHERE id_prestamo = :id_p";
                $pdo->prepare($sqlDev)->execute([':id_p' => $id_prestamo]);

                // 3. Aumentar el stock del libro
                $sqlStock = "UPDATE libros SET stock = stock + 1 WHERE id_libro = :id_l";
                $pdo->prepare($sqlStock)->execute([':id_l' => $row['id_libro']]);

                echo json_encode(["status" => "ok", "mensaje" => "Libro devuelto con éxito"]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Préstamo no encontrado"]);
            }
        } else {
            echo json_encode(["status" => "error", "mensaje" => "ID de préstamo inválido"]);
        }
        break;
    // ==========================================
    // 1. REGISTRO DE USUARIOS
    // ==========================================
    case 'registrar_usuario':
        $nombre   = trim($_REQUEST['nombre'] ?? '');
        $email    = trim($_REQUEST['email'] ?? '');
        $password = trim($_REQUEST['password'] ?? '');

        if (!empty($nombre) && !empty($email) && !empty($password)) {
            // Verificar si el correo ya existe
            $stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE LOWER(email) = LOWER(:email)");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                echo json_encode(["status" => "error", "mensaje" => "El correo ya está registrado"]);
            } else {
                // Registrar el nuevo usuario
                $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password) RETURNING id_usuario";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':email'    => $email,
                    ':password' => $password
                ]);
                $user = $stmt->fetch();

                echo json_encode([
                    "status" => "ok",
                    "mensaje" => "Registro exitoso",
                    "id_usuario" => $user['id_usuario'],
                    "nombre" => $nombre
                ]);
            }
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Completa todos los campos"]);
        }
        break;

    // ==========================================
    // 2. INICIO DE SESIÓN (LOGIN)
    // ==========================================
    case 'login':
        $email    = trim($_REQUEST['email'] ?? '');
        $password = trim($_REQUEST['password'] ?? '');

        if (!empty($email) && !empty($password)) {
            $sql = "SELECT id_usuario, nombre FROM usuarios WHERE LOWER(email) = LOWER(:email) AND password = :password";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':password' => $password]);
            $user = $stmt->fetch();

            if ($user) {
                echo json_encode([
                    "status" => "ok",
                    "mensaje" => "Bienvenido",
                    "id_usuario" => $user['id_usuario'],
                    "nombre" => $user['nombre']
                ]);
            } else {
                echo json_encode(["status" => "error", "mensaje" => "Credenciales incorrectas"]);
            }
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Ingresa email y contraseña"]);
        }
        break;      
    // Acción no reconocida
    default:
        echo json_encode(["status" => "error", "mensaje" => "Acción no especificada o no válida"]);
        break;
}
?>