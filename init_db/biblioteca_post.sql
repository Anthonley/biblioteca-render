SET client_encoding = 'UTF8';

DROP TABLE IF EXISTS prestamo CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS ejemplar CASCADE;
DROP TABLE IF EXISTS socio CASCADE;
DROP TABLE IF EXISTS genero CASCADE;
DROP TABLE IF EXISTS libro CASCADE;
DROP TABLE IF EXISTS sede_biblioteca CASCADE;
DROP TABLE IF EXISTS usuario CASCADE;

-- id_sede ahora es manual con formato SE-0000 (ver sedes.php / sedes_guardar.php)
CREATE TABLE sede_biblioteca (
    id_sede VARCHAR(10) PRIMARY KEY,
    se_nombre VARCHAR(150) NOT NULL,
    se_direccion VARCHAR(250) NOT NULL
);

-- id_libro es manual con formato LI-0000 (ver libros.php / libros_guardar.php)
CREATE TABLE libro (
    id_libro VARCHAR(10) PRIMARY KEY,
    li_titulo VARCHAR(200) NOT NULL,
    li_autor VARCHAR(150) NOT NULL,
    li_editorial VARCHAR(100),
    li_genero VARCHAR(100),
    li_isbn VARCHAR(20)
);

-- id_genero ahora es manual con formato GE-0000 (ver generos_guardar.php)
CREATE TABLE genero (
    id_genero VARCHAR(10) PRIMARY KEY,
    ge_nombre VARCHAR(100) NOT NULL UNIQUE
);

-- id_tag ahora es manual con formato TA-0000 (ver libros_guardar.php)
CREATE TABLE tag (
    id_tag VARCHAR(10) PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_genero VARCHAR(10) NOT NULL,
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_genero) REFERENCES genero(id_genero) ON DELETE CASCADE
);

-- id_socio ahora es manual con formato SO-0000 (ver socios.php / socios_guardar.php)
CREATE TABLE socio (
    id_socio VARCHAR(10) PRIMARY KEY,
    so_cedula VARCHAR(10) NOT NULL UNIQUE,
    so_nombre VARCHAR(100) NOT NULL, 
    so_apellido VARCHAR(100) NOT NULL, 
    so_telefono VARCHAR(15),
    so_correo VARCHAR(150) NOT NULL
);

-- id_ejemplar ahora es manual con formato EJ-0000 (ver ejemplares.php / ejemplares_guardar.php)
-- id_libro es VARCHAR porque referencia a libro.id_libro (formato LI-0000)
-- id_sede es VARCHAR porque referencia a sede_biblioteca.id_sede (formato SE-0000)
CREATE TABLE ejemplar (
    id_ejemplar VARCHAR(10) PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_sede VARCHAR(10) NOT NULL,
    ej_estado VARCHAR(50) DEFAULT 'Disponible',
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_sede) REFERENCES sede_biblioteca(id_sede) ON DELETE CASCADE
);

-- id_prestamo ahora es manual con formato PR-0000 (ver prestamos.php / prestamos_guardar.php)
-- id_ejemplar/id_socio son VARCHAR porque referencian los nuevos IDs con prefijo
-- pr_estado_devolucion y pr_multa se llenan solo al devolver (ver prestamos_devolver.php)
CREATE TABLE prestamo (
    id_prestamo VARCHAR(10) PRIMARY KEY,
    id_ejemplar VARCHAR(10) NOT NULL,
    id_socio VARCHAR(10) NOT NULL,
    pr_f_pres DATE NOT NULL DEFAULT CURRENT_DATE,
    pr_f_dev_esperada DATE NOT NULL,
    pr_f_dev_real DATE,
    pr_estado_devolucion VARCHAR(20),
    pr_multa NUMERIC(8,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_ejemplar) REFERENCES ejemplar(id_ejemplar) ON DELETE CASCADE,
    FOREIGN KEY (id_socio) REFERENCES socio(id_socio) ON DELETE CASCADE
);

-- usuario se mantiene igual: id numérico autoincremental (SERIAL)
CREATE TABLE usuario (
    id_usuario SERIAL PRIMARY KEY,
    us_usuario VARCHAR(50) NOT NULL UNIQUE,
    us_password_hash VARCHAR(255) NOT NULL,
    us_rol VARCHAR(20) NOT NULL DEFAULT 'bibliotecario',
    us_estado SMALLINT NOT NULL DEFAULT 1
);

INSERT INTO usuario (us_usuario, us_password_hash, us_rol, us_estado) VALUES
('admin', '1234', 'admin', 1);

INSERT INTO sede_biblioteca (id_sede, se_nombre, se_direccion) VALUES
('SE-0001', 'Biblioteca Central', 'Av. 10 de Agosto y Patria'),
('SE-0002', 'Sede Norte', 'Av. Amazonas y Naciones Unidas'),
('SE-0003', 'Sede Sur', 'Av. Teniente Hugo Ortiz'),
('SE-0004', 'Sede Valle', 'Av. General Rumiñahui'),
('SE-0005', 'Sede Universitaria', 'Campus Principal Calle 1'),
('SE-0006', 'Sede Centro Histórico', 'Calle Guayaquil y Sucre'),
('SE-0007', 'Biblioteca Infantil', 'Parque de la Carolina'),
('SE-0008', 'Sede Tecnológica', 'Av. Simón Bolívar'),
('SE-0009', 'Sede Este', 'Calle Los Pinos'),
('SE-0010', 'Biblioteca Móvil', 'Unidad de Transporte 01');

INSERT INTO libro (id_libro, li_titulo, li_autor, li_editorial, li_genero, li_isbn) VALUES
('LI-0001', 'Cien años de soledad', 'Gabriel García Márquez', 'Sudamericana', 'Ficción Mágica', '978-84-376-0494-7'),
('LI-0002', '1984', 'George Orwell', 'Secker & Warburg', 'Ciencia Ficción', '9780452284234'),
('LI-0003', 'El Hobbit', 'J.R.R. Tolkien', 'Allen & Unwin', 'Fantasía', '978061102217'),
('LI-0004', 'Fundación', 'Isaac Asimov', 'Gnome Press', 'Ciencia Ficción', '9780553293350'),
('LI-0005', 'Orgullo y Prejuicio', 'Jane Austen', 'T. Egerton', 'Romance', '9780141439518'),
('LI-0006', 'El nombre de la rosa', 'Umberto Eco', 'Bompiani', 'Misterio', '9788408043646'),
('LI-0007', 'Fahrenheit 451', 'Ray Bradbury', 'Ballantine Books', 'Ciencia Ficción', '9781451673319'),
('LI-0008', 'La sombra del viento', 'Carlos Ruiz Zafón', 'Planeta', 'Misterio', '9788408043647'),
('LI-0009', 'Rayuela', 'Julio Cortázar', 'Sudamericana', 'Novela Contemporánea', '9788437604954'),
('LI-0010', 'Dune', 'Frank Herbert', 'Chilton Books', 'Ciencia Ficción', '9780441013593');

INSERT INTO socio (id_socio, so_cedula, so_nombre, so_apellido, so_telefono, so_correo) VALUES
('SO-0001', '1712345671', 'Juan', 'Perez', '0991111111', 'juan.perez@example.com'),
('SO-0002', '1712345672', 'Maria', 'Gomez', '0992222222', 'maria.gomez@example.com'),
('SO-0003', '1712345673', 'Carlos', 'Lopez', '0993333333', 'carlos.lopez@example.com'),
('SO-0004', '1712345674', 'Ana', 'Martinez', '0994444444', 'ana.martinez@example.com'),
('SO-0005', '1712345675', 'Luis', 'Rodriguez', '0995555555', 'luis.rodriguez@example.com'),
('SO-0006', '1712345676', 'Elena', 'Sanchez', '0996666666', 'elena.sanchez@example.com'),
('SO-0007', '1712345677', 'Javier', 'Romero', '0997777777', 'javier.romero@example.com'),
('SO-0008', '1712345678', 'Sofia', 'Torres', '0998888888', 'sofia.torres@example.com'),
('SO-0009', '1712345679', 'Diego', 'Herrera', '0999999999', 'diego.herrera@example.com'),
('SO-0010', '1712345680', 'Lucia', 'Castro', '0990000000', 'lucia.castro@example.com');

INSERT INTO ejemplar (id_ejemplar, id_libro, id_sede, ej_estado) VALUES
('EJ-0001', 'LI-0001', 'SE-0001', 'Disponible'),
('EJ-0002', 'LI-0002', 'SE-0001', 'Disponible'),
('EJ-0003', 'LI-0003', 'SE-0002', 'Disponible'),
('EJ-0004', 'LI-0004', 'SE-0003', 'Extraviado'),
('EJ-0005', 'LI-0005', 'SE-0004', 'En Reparación'),
('EJ-0006', 'LI-0006', 'SE-0005', 'Prestado'),
('EJ-0007', 'LI-0007', 'SE-0006', 'Prestado'),
('EJ-0008', 'LI-0008', 'SE-0007', 'Disponible'),
('EJ-0009', 'LI-0009', 'SE-0008', 'Prestado'),
('EJ-0010', 'LI-0010', 'SE-0009', 'Prestado');

-- Ejemplos de los 3 casos que puede tener un préstamo hoy (hoy = 2026-07-29):
--  · PR-0002: ATRASADO -> pr_f_dev_esperada ya pasó y pr_f_dev_real sigue NULL
--  · PR-0003, PR-0007, PR-0009: ACTIVOS a tiempo -> todavía no llega su fecha esperada
--  · PR-0004, PR-0006, PR-0008: DEVUELTOS, con distintos pr_estado_devolucion y su multa calculada
--     (PR-0004 = tarde pero en buen estado, PR-0006 = dañado, PR-0008 = perdido)
INSERT INTO prestamo (id_prestamo, id_ejemplar, id_socio, pr_f_pres, pr_f_dev_esperada, pr_f_dev_real, pr_estado_devolucion, pr_multa) VALUES
('PR-0001', 'EJ-0002', 'SO-0001', '2026-07-01', '2026-07-10', '2026-07-10', 'Bueno', 0.00),
('PR-0002', 'EJ-0006', 'SO-0002', '2026-07-05', '2026-07-20', NULL, NULL, 0.00),
('PR-0003', 'EJ-0010', 'SO-0003', '2026-07-15', '2026-08-15', NULL, NULL, 0.00),
('PR-0004', 'EJ-0001', 'SO-0004', '2026-06-10', '2026-06-20', '2026-06-25', 'Bueno', 2.50),
('PR-0005', 'EJ-0003', 'SO-0005', '2026-06-25', '2026-07-02', '2026-07-02', 'Bueno', 0.00),
('PR-0006', 'EJ-0005', 'SO-0006', '2026-05-15', '2026-05-30', '2026-06-05', 'Dañado', 8.00),
('PR-0007', 'EJ-0007', 'SO-0007', '2026-07-20', '2026-08-20', NULL, NULL, 0.00),
('PR-0008', 'EJ-0004', 'SO-0008', '2026-04-10', '2026-04-20', '2026-04-28', 'Perdido', 19.00),
('PR-0009', 'EJ-0009', 'SO-0009', '2026-07-22', '2026-08-22', NULL, NULL, 0.00),
('PR-0010', 'EJ-0008', 'SO-0010', '2026-01-10', '2026-01-25', '2026-01-25', 'Bueno', 0.00);

INSERT INTO genero (id_genero, ge_nombre) VALUES
('GE-0001', 'Ficción Mágica'),
('GE-0002', 'Ciencia Ficción'),
('GE-0003', 'Fantasía'),
('GE-0004', 'Romance'),
('GE-0005', 'Misterio'),
('GE-0006', 'Novela Contemporánea');

INSERT INTO tag (id_tag, id_libro, id_genero) VALUES
('TA-0001', 'LI-0001', 'GE-0001'),
('TA-0002', 'LI-0002', 'GE-0002'),
('TA-0003', 'LI-0003', 'GE-0003'),
('TA-0004', 'LI-0004', 'GE-0002'),
('TA-0005', 'LI-0005', 'GE-0004'),
('TA-0006', 'LI-0006', 'GE-0005'),
('TA-0007', 'LI-0007', 'GE-0002'),
('TA-0008', 'LI-0008', 'GE-0005'),
('TA-0009', 'LI-0009', 'GE-0006'),
('TA-0010', 'LI-0010', 'GE-0002');