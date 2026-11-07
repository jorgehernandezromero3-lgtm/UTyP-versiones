# Compatibilidad con PHP 7.4 y MariaDB 10.11

Fecha de la revisión: 6 de noviembre de 2026

## PHP

- Entorno de ejecución disponible: PHP 8.5.12 CLI (XAMPP, Windows x64).
- Archivos PHP entregables revisados: 64.
- Resultado de `php -l` sobre los 64 archivos: sin errores de sintaxis.
- Búsqueda de funciones y construcciones exclusivas de PHP 8
  (`str_contains`, `str_starts_with`, `str_ends_with`, `array_is_list`,
  `match`, `?->`, `readonly`, `enum`): sin coincidencias en el código entregable.
- El código usa construcciones disponibles desde PHP 7.4: tipos escalares,
  propiedades tipadas, funciones flecha, fusión nula y tipos de retorno.

Limitación honesta: no se dispone de un binario PHP 7.4 en este equipo, por lo
que la comprobación fue estática y de sintaxis, no una ejecución real bajo 7.4.
Antes de publicar en un hosting con PHP 7.4 se recomienda ejecutar
`php -l` y abrir `asistente/demo.php` en ese entorno.

## MariaDB

- Servidor usado en las pruebas: MariaDB 13.0.2.
- Cliente SQL disponible: MariaDB 10.11.16.
- Versión objetivo del proyecto: MariaDB 10.11.
- El esquema usa InnoDB, utf8mb4, claves foráneas, `ON DUPLICATE KEY UPDATE`,
  `ENUM`, `JSON` almacenado como texto y marcas de tiempo estándar; no usa
  funciones exclusivas de versiones 11 o 12.
- Las consultas usan PDO con sentencias preparadas nativas y placeholders
  únicos (sin reutilizar el mismo marcador con emulación desactivada).

## Comandos usados para la revisión

```bash
# Sintaxis de todos los archivos PHP
find private public database tests integracion -name "*.php" -type f \
  -exec php -l {} \;

# Búsqueda de construcciones exclusivas de PHP 8
grep -rnE "\b(str_contains|str_starts_with|str_ends_with|array_is_list|enum |readonly|match\s*\(|\?->)" \
  private public database tests integracion --include="*.php"
```

Resultado: 64 archivos analizados, 0 errores de sintaxis, 0 coincidencias de
construcciones exclusivas de PHP 8.
