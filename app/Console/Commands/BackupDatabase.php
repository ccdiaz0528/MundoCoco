<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * RNF04/RNF05: respaldo diario de la base de datos.
 * Genera un volcado SQL en storage/app/backups y purga copias
 * con más de 30 días. Funciona con SQLite y MySQL sin
 * herramientas externas (no requiere mysqldump).
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--retencion=30 : Días de copias a conservar}';

    protected $description = 'Genera un respaldo SQL de la base de datos en storage/app/backups';

    public function handle(): int
    {
        $fecha = now()->format('Y-m-d_His');
        $archivo = "backups/backup-{$fecha}.sql";

        Storage::put($archivo, $this->volcado());
        $ruta = Storage::path($archivo);
        $this->info("Respaldo creado: {$ruta} (".number_format(Storage::size($archivo) / 1024, 1).' KB)');

        $this->purgar((int) $this->option('retencion'));

        return self::SUCCESS;
    }

    /**
     * Volcado SQL esquema + datos de todas las tablas de la conexión actual.
     */
    private function volcado(): string
    {
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();
        $lineas = [
            '-- Respaldo MundoCoco '.now()->toDateTimeString(),
            '-- Driver: '.$driver.' | Base: '.DB::connection()->getDatabaseName(),
            '',
        ];

        foreach ($this->tablas($pdo, $driver) as $tabla) {
            $lineas[] = "-- Tabla: {$tabla}";
            $lineas[] = $this->crearTabla($pdo, $driver, $tabla).';';
            foreach ($this->filas($pdo, $driver, $tabla) as $fila) {
                $valores = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($fila));
                $lineas[] = "INSERT INTO \"{$tabla}\" VALUES (".implode(', ', $valores).');';
            }
            $lineas[] = '';
        }

        return implode(PHP_EOL, $lineas);
    }

    /** @return array<int, string> */
    private function tablas(\PDO $pdo, string $driver): array
    {
        $sql = $driver === 'mysql'
            ? 'SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\''
            : "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name";

        return array_column($pdo->query($sql)->fetchAll(\PDO::FETCH_NUM), 0);
    }

    private function crearTabla(\PDO $pdo, string $driver, string $tabla): string
    {
        if ($driver === 'mysql') {
            return $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(\PDO::FETCH_NUM)[1];
        }

        return $pdo->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '{$tabla}'")->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    private function filas(\PDO $pdo, string $driver, string $tabla): array
    {
        $comilla = $driver === 'mysql' ? '`' : '"';

        return $pdo->query("SELECT * FROM {$comilla}{$tabla}{$comilla}")->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function purgar(int $retencion): void
    {
        $eliminados = 0;
        foreach (Storage::files('backups') as $archivo) {
            if (Storage::lastModified($archivo) < now()->subDays($retencion)->timestamp) {
                Storage::delete($archivo);
                $eliminados++;
            }
        }

        if ($eliminados > 0) {
            $this->info("Copias antiguas eliminadas: {$eliminados}");
        }
    }
}
