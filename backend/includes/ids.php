<?php
declare(strict_types=1);

/**
 * Genera el siguiente ID correlativo con formato "PREFIJO-0000" para una tabla.
 *
 * Ej: generarSiguienteId($pdo, 'sede_biblioteca', 'id_sede', 'SE') -> "SE-0007"
 *     (si la sede con el número más alto es SE-0006)
 *
 * Sigue el mismo patrón que ya se usaba para libro.id_libro (LI-0000).
 */
function generarSiguienteId(PDO $pdo, string $tabla, string $columna, string $prefijo, int $digitos = 4): string
{
    // La parte numérica empieza justo después de "PREFIJO-"
    $posicion = strlen($prefijo) + 2;

    $stmt = $pdo->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING($columna FROM $posicion) AS INTEGER)), 0) FROM $tabla"
    );
    $maxNum = (int) $stmt->fetchColumn();

    return $prefijo . '-' . str_pad((string)($maxNum + 1), $digitos, '0', STR_PAD_LEFT);
}

/**
 * Normaliza un ID escrito a mano por el usuario ("SE-0007", "se-7", "7") al
 * formato canónico "PREFIJO-0000". Devuelve null si no es válido.
 */
function normalizarId(string $valorCrudo, string $prefijo, int $digitos = 4): ?string
{
    $v = strtoupper(trim($valorCrudo));
    if (str_starts_with($v, $prefijo . '-')) {
        $v = substr($v, strlen($prefijo) + 1);
    }
    $v = preg_replace('/\D/', '', $v);

    if ($v === '' || strlen($v) > $digitos) {
        return null;
    }
    return $prefijo . '-' . str_pad($v, $digitos, '0', STR_PAD_LEFT);
}