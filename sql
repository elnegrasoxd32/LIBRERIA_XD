-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- 2. Tabla de Categorías
CREATE TABLE IF NOT EXISTS categorias (
    id_categoria SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- 3. Tabla de Libros (Disponibles y Próximamente)
CREATE TABLE IF NOT EXISTS libros (
    id_libro SERIAL PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    autor VARCHAR(100) NOT NULL,
    descripcion_corta TEXT,
    descripcion TEXT,
    id_categoria INT NOT NULL,
    stock INT DEFAULT 1,
    imagen_url VARCHAR(500), -- Ampliado para URLs largas
    es_proximo BOOLEAN DEFAULT FALSE, -- FALSE = Ya disponible / TRUE = Próximamente
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE
);

-- 4. Tabla de Préstamos / Historial (Para libros actuales y devueltos)
CREATE TABLE IF NOT EXISTS prestamos (
    id_prestamo SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_libro INT NOT NULL,
    fecha_prestamo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion TIMESTAMP NULL,
    estado VARCHAR(20) DEFAULT 'activo' CHECK (estado IN ('activo', 'devuelto')),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE
);
