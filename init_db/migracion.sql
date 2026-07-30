-- Migración: separar la fecha de devolución en dos columnas
-- pr_f_dev_esperada = se define al CREAR el préstamo (ya no es libre)
-- pr_f_dev_real      = se llena solo cuando se marca como devuelto
--
-- Ejecutar UNA sola vez contra la base ya existente (local y/o Render).

ALTER TABLE prestamo RENAME COLUMN pr_f_dev TO pr_f_dev_real;
ALTER TABLE prestamo ADD COLUMN pr_f_dev_esperada DATE;

-- A los préstamos que ya existan les ponemos una fecha esperada de relleno
-- (14 días después del préstamo) para poder dejar la columna NOT NULL.
UPDATE prestamo
SET pr_f_dev_esperada = pr_f_pres + INTERVAL '14 days'
WHERE pr_f_dev_esperada IS NULL;

ALTER TABLE prestamo ALTER COLUMN pr_f_dev_esperada SET NOT NULL;