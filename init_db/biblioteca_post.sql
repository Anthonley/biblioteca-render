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

CREATE TABLE libro (
    id_libro SERIAL PRIMARY KEY,
    li_titulo VARCHAR(200) NOT NULL,
    li_autor VARCHAR(150) NOT NULL,
    li_editorial VARCHAR(100),
    li_genero VARCHAR(100) 
);

CREATE TABLE genero (
    id_genero SERIAL PRIMARY KEY,
    ge_nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE tag (
    id_tag SERIAL PRIMARY KEY,
    id_libro INT NOT NULL,
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

CREATE TABLE ejemplar (
    id_ejemplar SERIAL PRIMARY KEY,
    id_libro INT NOT NULL,
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

INSERT INTO libro (li_titulo, li_autor, li_editorial, li_genero) VALUES
('Cien años de soledad', 'Gabriel García Márquez', 'Sudamericana', 'Ficción Mágica'),
('1984', 'George Orwell', 'Secker & Warburg', 'Ciencia Ficción'),
('El Hobbit', 'J.R.R. Tolkien', 'Allen & Unwin', 'Fantasía'),
('Fundación', 'Isaac Asimov', 'Gnome Press', 'Ciencia Ficción'),
('Orgullo y Prejuicio', 'Jane Austen', 'T. Egerton', 'Romance'),
('El nombre de la rosa', 'Umberto Eco', 'Bompiani', 'Misterio'),
('Fahrenheit 451', 'Ray Bradbury', 'Ballantine Books', 'Ciencia Ficción'),
('La sombra del viento', 'Carlos Ruiz Zafón', 'Planeta', 'Misterio'),
('Rayuela', 'Julio Cortázar', 'Sudamericana', 'Novela Contemporánea'),
('Dune', 'Frank Herbert', 'Chilton Books', 'Ciencia Ficción');

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
(1, 1, 'Disponible'),
(2, 1, 'Prestado'),
(3, 2, 'Disponible'),
(4, 3, 'En Reparación'),
(5, 4, 'Disponible'),
(6, 5, 'Prestado'),
(7, 6, 'Disponible'),
(8, 7, 'Extraviado'),
(9, 8, 'Disponible'),
(10, 9, 'Prestado');

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
(1, 1),
(2, 2),
(3, 3),
(4, 2),
(5, 4),
(6, 5),
(7, 2),
(8, 5),
(9, 6),
(10, 2);