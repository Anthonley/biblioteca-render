SELECT l.li_titulo, l.li_autor, g.ge_nombre
FROM libro l
JOIN tag t ON t.id_libro = l.id_libro
JOIN genero g ON g.id_genero = t.id_genero
ORDER BY l.li_titulo;