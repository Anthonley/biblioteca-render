DROP TABLE IF EXISTS prestamo CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS ejemplar CASCADE;
DROP TABLE IF EXISTS socio CASCADE;
DROP TABLE IF EXISTS genero CASCADE;
DROP TABLE IF EXISTS libro CASCADE;
DROP TABLE IF EXISTS sede_biblioteca CASCADE;
DROP TABLE IF EXISTS usuario CASCADE;

CREATE TABLE sede_biblioteca (
    id_sede SERIAL PRIMARY KEY,
    se_nombre VARCHAR(150) NOT NULL,
    se_direccion VARCHAR(250) NOT NULL
);

-- id_libro ahora es manual con formato LI-0000 (ver libros.php / libros_guardar.php)
CREATE TABLE libro (
    id_libro VARCHAR(10) PRIMARY KEY,
    li_titulo VARCHAR(200) NOT NULL,
    li_autor VARCHAR(150) NOT NULL,
    li_editorial VARCHAR(100),
    li_genero VARCHAR(100),
    li_isbn VARCHAR(20)
);

CREATE TABLE genero (
    id_genero SERIAL PRIMARY KEY,
    ge_nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE tag (
    id_tag SERIAL PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_genero INT NOT NULL,
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_genero) REFERENCES genero(id_genero) ON DELETE CASCADE
);

CREATE TABLE socio (
    id_socio SERIAL PRIMARY KEY,
    so_cedula VARCHAR(10) NOT NULL UNIQUE,
    so_nombre VARCHAR(100) NOT NULL, 
    so_apellido VARCHAR(100) NOT NULL, 
    so_telefono VARCHAR(15)
);

-- id_libro es VARCHAR porque referencia a libro.id_libro (formato LI-0000)
CREATE TABLE ejemplar (
    id_ejemplar SERIAL PRIMARY KEY,
    id_libro VARCHAR(10) NOT NULL,
    id_sede INT NOT NULL,
    ej_estado VARCHAR(50) DEFAULT 'Disponible',
    FOREIGN KEY (id_libro) REFERENCES libro(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_sede) REFERENCES sede_biblioteca(id_sede) ON DELETE CASCADE
);

CREATE TABLE prestamo (
    id_prestamo SERIAL PRIMARY KEY,
    id_ejemplar INT NOT NULL,
    id_socio INT NOT NULL,
    pr_f_pres DATE NOT NULL DEFAULT CURRENT_DATE, 
    pr_f_dev DATE,
    FOREIGN KEY (id_ejemplar) REFERENCES ejemplar(id_ejemplar) ON DELETE CASCADE,
    FOREIGN KEY (id_socio) REFERENCES socio(id_socio) ON DELETE CASCADE
);

CREATE TABLE usuario (
    id_usuario SERIAL PRIMARY KEY,
    us_usuario VARCHAR(50) NOT NULL UNIQUE,
    us_password_hash VARCHAR(255) NOT NULL,
    us_rol VARCHAR(20) NOT NULL DEFAULT 'bibliotecario',
    us_estado SMALLINT NOT NULL DEFAULT 1
);

INSERT INTO usuario (us_usuario, us_password_hash, us_rol, us_estado) VALUES
('admin', '1234', 'admin', 1);

INSERT INTO sede_biblioteca (se_nombre, se_direccion) VALUES
('Biblioteca Central', 'Av. 10 de Agosto y Patria'),
('Sede Norte', 'Av. Amazonas y Naciones Unidas'),
('Sede Sur', 'Av. Teniente Hugo Ortiz'),
('Sede Valle', 'Av. General Rumiñahui'),
('Sede Universitaria', 'Campus Principal Calle 1'),
('Sede Centro Histórico', 'Calle Guayaquil y Sucre'),
('Biblioteca Infantil', 'Parque de la Carolina'),
('Sede Tecnológica', 'Av. Simón Bolívar'),
('Sede Este', 'Calle Los Pinos'),
('Biblioteca Móvil', 'Unidad de Transporte 01');

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

INSERT INTO socio (so_cedula, so_nombre, so_apellido, so_telefono) VALUES
('1712345671', 'Juan', 'Perez', '0991111111'),
('1712345672', 'Maria', 'Gomez', '0992222222'),
('1712345673', 'Carlos', 'Lopez', '0993333333'),
('1712345674', 'Ana', 'Martinez', '0994444444'),
('1712345675', 'Luis', 'Rodriguez', '0995555555'),
('1712345676', 'Elena', 'Sanchez', '0996666666'),
('1712345677', 'Javier', 'Romero', '0997777777'),
('1712345678', 'Sofia', 'Torres', '0998888888'),
('1712345679', 'Diego', 'Herrera', '0999999999'),
('1712345680', 'Lucia', 'Castro', '0990000000');

INSERT INTO ejemplar (id_libro, id_sede, ej_estado) VALUES
('LI-0001', 1, 'Disponible'),
('LI-0002', 1, 'Prestado'),
('LI-0003', 2, 'Disponible'),
('LI-0004', 3, 'En Reparación'),
('LI-0005', 4, 'Disponible'),
('LI-0006', 5, 'Prestado'),
('LI-0007', 6, 'Disponible'),
('LI-0008', 7, 'Extraviado'),
('LI-0009', 8, 'Disponible'),
('LI-0010', 9, 'Prestado');

INSERT INTO prestamo (id_ejemplar, id_socio, pr_f_pres, pr_f_dev) VALUES
(2, 1, '2026-07-01', '2026-07-10'), 
(6, 2, '2026-07-05', NULL),         
(10, 3, '2026-07-15', NULL),        
(1, 4, '2026-06-10', '2026-06-20'), 
(3, 5, '2026-06-25', '2026-07-02'), 
(5, 6, '2026-05-15', '2026-05-30'), 
(7, 7, '2026-07-20', NULL),         
(4, 8, '2026-04-10', '2026-04-20'), 
(9, 9, '2026-07-22', NULL),         
(8, 10, '2026-01-10', '2026-01-25');

INSERT INTO genero (ge_nombre) VALUES
('Ficción Mágica'),
('Ciencia Ficción'),
('Fantasía'),
('Romance'),
('Misterio'),
('Novela Contemporánea');

INSERT INTO tag (id_libro, id_genero) VALUES
('LI-0001', 1),
('LI-0002', 2),
('LI-0003', 3),
('LI-0004', 2),
('LI-0005', 4),
('LI-0006', 5),
('LI-0007', 2),
('LI-0008', 5),
('LI-0009', 6),
('LI-0010', 2);