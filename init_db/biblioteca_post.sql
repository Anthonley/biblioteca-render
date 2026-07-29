-- =====================================================
-- BIBLIOTECA - Script actualizado
-- IDs con formato PREFIJO-0000 (excepto usuario)
-- Se agrega ISBN a libro y correo a socio
-- Fechas de devolución completadas (futuras si el préstamo sigue activo)
-- =====================================================

DROP TABLE IF EXISTS prestamo CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS ejemplar CASCADE;
DROP TABLE IF EXISTS socio CASCADE;
DROP TABLE IF EXISTS genero CASCADE;
DROP TABLE IF EXISTS libro CASCADE;
DROP TABLE IF EXISTS sede_biblioteca CASCADE;
DROP TABLE IF EXISTS usuario CASCADE;

-- =====================================================
-- TABLAS
-- =====================================================

CREATE TABLE sede_biblioteca (
    id_sede VARCHAR(10) PRIMARY KEY,
    se_nombre VARCHAR(150) NOT NULL,
    se_direccion VARCHAR(250) NOT NULL
);

CREATE TABLE libro (
    id_libro VARCHAR(10) PRIMARY KEY,
    li_titulo VARCHAR(200) NOT NULL,
    li_autor VARCHAR(150) NOT NULL,
    li_editorial VARCHAR(100),
    li_genero VARCHAR(100),
    li_isbn VARCHAR(20)
);

CREATE TABLE genero (
    id_genero VARCHAR(10) PRIMARY KEY,
    ge_nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE tag (
    id_tag VARCHAR(10) PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_genero VARCHAR(10) NOT NULL,
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_genero) REFERENCES genero(id_genero) ON DELETE CASCADE
);

CREATE TABLE socio (
    id_socio VARCHAR(10) PRIMARY KEY,
    so_cedula VARCHAR(10) NOT NULL UNIQUE,
    so_nombre VARCHAR(100) NOT NULL,
    so_apellido VARCHAR(100) NOT NULL,
    so_telefono VARCHAR(15),
    so_correo VARCHAR(150)
);

CREATE TABLE ejemplar (
    id_ejemplar VARCHAR(10) PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_sede VARCHAR(10) NOT NULL,
    ej_estado VARCHAR(50) DEFAULT 'Disponible',
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_sede) REFERENCES sede_biblioteca(id_sede) ON DELETE CASCADE
);

CREATE TABLE prestamo (
    id_prestamo VARCHAR(10) PRIMARY KEY,
    id_ejemplar VARCHAR(10) NOT NULL,
    id_socio VARCHAR(10) NOT NULL,
    pr_f_pres DATE NOT NULL DEFAULT CURRENT_DATE,
    pr_f_dev DATE,
    FOREIGN KEY (id_ejemplar) REFERENCES ejemplar(id_ejemplar) ON DELETE CASCADE,
    FOREIGN KEY (id_socio) REFERENCES socio(id_socio) ON DELETE CASCADE
);

-- usuario se mantiene igual (SERIAL), sin prefijo
CREATE TABLE usuario (
    id_usuario SERIAL PRIMARY KEY,
    us_usuario VARCHAR(50) NOT NULL UNIQUE,
    us_password_hash VARCHAR(255) NOT NULL,
    us_rol VARCHAR(20) NOT NULL DEFAULT 'bibliotecario',
    us_estado SMALLINT NOT NULL DEFAULT 1
);

-- =====================================================
-- DATOS
-- =====================================================

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
('LI-0002', '1984', 'George Orwell', 'Secker & Warburg', 'Ciencia Ficción', '978-0-452-28423-4'),
('LI-0003', 'El Hobbit', 'J.R.R. Tolkien', 'Allen & Unwin', 'Fantasía', '978-0-261-10221-7'),
('LI-0004', 'Fundación', 'Isaac Asimov', 'Gnome Press', 'Ciencia Ficción', '978-0-553-29335-0'),
('LI-0005', 'Orgullo y Prejuicio', 'Jane Austen', 'T. Egerton', 'Romance', '978-0-14-143951-8'),
('LI-0006', 'El nombre de la rosa', 'Umberto Eco', 'Bompiani', 'Misterio', '978-84-08-04364-6'),
('LI-0007', 'Fahrenheit 451', 'Ray Bradbury', 'Ballantine Books', 'Ciencia Ficción', '978-1-4516-7331-9'),
('LI-0008', 'La sombra del viento', 'Carlos Ruiz Zafón', 'Planeta', 'Misterio', '978-84-08-04364-7'),
('LI-0009', 'Rayuela', 'Julio Cortázar', 'Sudamericana', 'Novela Contemporánea', '978-84-376-0495-4'),
('LI-0010', 'Dune', 'Frank Herbert', 'Chilton Books', 'Ciencia Ficción', '978-0-441-01359-3');

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

INSERT INTO socio (id_socio, so_cedula, so_nombre, so_apellido, so_telefono, so_correo) VALUES
('SO-0001', '1712345671', 'Juan', 'Perez', '0991111111', 'juan.perez@correo.com'),
('SO-0002', '1712345672', 'Maria', 'Gomez', '0992222222', 'maria.gomez@correo.com'),
('SO-0003', '1712345673', 'Carlos', 'Lopez', '0993333333', 'carlos.lopez@correo.com'),
('SO-0004', '1712345674', 'Ana', 'Martinez', '0994444444', 'ana.martinez@correo.com'),
('SO-0005', '1712345675', 'Luis', 'Rodriguez', '0995555555', 'luis.rodriguez@correo.com'),
('SO-0006', '1712345676', 'Elena', 'Sanchez', '0996666666', 'elena.sanchez@correo.com'),
('SO-0007', '1712345677', 'Javier', 'Romero', '0997777777', 'javier.romero@correo.com'),
('SO-0008', '1712345678', 'Sofia', 'Torres', '0998888888', 'sofia.torres@correo.com'),
('SO-0009', '1712345679', 'Diego', 'Herrera', '0999999999', 'diego.herrera@correo.com'),
('SO-0010', '1712345680', 'Lucia', 'Castro', '0990000000', 'lucia.castro@correo.com');

INSERT INTO ejemplar (id_ejemplar, id_libro, id_sede, ej_estado) VALUES
('EJ-0001', 'LI-0001', 'SE-0001', 'Disponible'),
('EJ-0002', 'LI-0002', 'SE-0001', 'Prestado'),
('EJ-0003', 'LI-0003', 'SE-0002', 'Disponible'),
('EJ-0004', 'LI-0004', 'SE-0003', 'En Reparación'),
('EJ-0005', 'LI-0005', 'SE-0004', 'Disponible'),
('EJ-0006', 'LI-0006', 'SE-0005', 'Prestado'),
('EJ-0007', 'LI-0007', 'SE-0006', 'Disponible'),
('EJ-0008', 'LI-0008', 'SE-0007', 'Extraviado'),
('EJ-0009', 'LI-0009', 'SE-0008', 'Disponible'),
('EJ-0010', 'LI-0010', 'SE-0009', 'Prestado');

-- pr_f_dev: se completan todas. Las que estaban NULL (préstamos activos)
-- reciben una fecha futura respecto a hoy (2026-07-29), simulando la
-- devolución esperada; luego se ajustará con la lógica real de la página.
INSERT INTO prestamo (id_prestamo, id_ejemplar, id_socio, pr_f_pres, pr_f_dev) VALUES
('PR-0001', 'EJ-0002', 'SO-0001', '2026-07-01', '2026-07-10'),
('PR-0002', 'EJ-0006', 'SO-0002', '2026-07-05', '2026-08-05'),
('PR-0003', 'EJ-0010', 'SO-0003', '2026-07-15', '2026-08-15'),
('PR-0004', 'EJ-0001', 'SO-0004', '2026-06-10', '2026-06-20'),
('PR-0005', 'EJ-0003', 'SO-0005', '2026-06-25', '2026-07-02'),
('PR-0006', 'EJ-0005', 'SO-0006', '2026-05-15', '2026-05-30'),
('PR-0007', 'EJ-0007', 'SO-0007', '2026-07-20', '2026-08-20'),
('PR-0008', 'EJ-0004', 'SO-0008', '2026-04-10', '2026-04-20'),
('PR-0009', 'EJ-0009', 'SO-0009', '2026-07-22', '2026-08-22'),
('PR-0010', 'EJ-0008', 'SO-0010', '2026-01-10', '2026-01-25');