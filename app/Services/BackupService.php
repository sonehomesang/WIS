<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Admin database backup / restore (Settings › Backup). Backups are gzipped
 * mysqldumps stored under storage/app/backups (NOT web-accessible). Restore
 * is destructive and super_admin-only.
 *
 * Binary paths default to PATH (`mysqldump`/`mysql`) for production; on the
 * Laragon dev box they fall back to the bundled MySQL bin. Override with
 * BACKUP_MYSQLDUMP_BINARY / BACKUP_MYSQL_BINARY in .env.
 */
class BackupService
{
    private const DIR = 'backups';

    /** @return string filename created */
    public function create(): string
    {
        $db = config('database.connections.mysql');
        $name = 'wh-'.Carbon::now()->format('Ymd-His').'.sql.gz';

        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->run([
                $this->bin('mysqldump'),
                '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'],
                '--single-transaction', '--no-tablespaces', '--routines', '--skip-lock-tables',
                $db['database'],
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput()) ?: 'unknown error');
        }

        Storage::disk('local')->put(self::DIR.'/'.$name, gzencode($result->output(), 6));

        return $name;
    }

    /** @return array<int,array{name:string,size:int,at:int}> newest first */
    public function list(): array
    {
        return collect(Storage::disk('local')->files(self::DIR))
            ->filter(fn ($p) => str_ends_with($p, '.sql.gz'))
            ->map(fn ($p) => [
                'name' => basename($p),
                'size' => Storage::disk('local')->size($p),
                'at' => Storage::disk('local')->lastModified($p),
            ])
            ->sortByDesc('at')->values()->all();
    }

    public function restore(string $name): void
    {
        $name = basename($name);   // prevent path traversal
        $path = self::DIR.'/'.$name;
        if (! Storage::disk('local')->exists($path)) {
            throw new RuntimeException('Backup not found.');
        }

        $sql = gzdecode(Storage::disk('local')->get($path));
        $db = config('database.connections.mysql');

        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
            ->input($sql)
            ->run([
                $this->bin('mysql'),
                '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'],
                $db['database'],
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('restore failed: '.trim($result->errorOutput()));
        }
    }

    public function delete(string $name): void
    {
        Storage::disk('local')->delete(self::DIR.'/'.basename($name));
    }

    public function path(string $name): ?string
    {
        $full = Storage::disk('local')->path(self::DIR.'/'.basename($name));

        return is_file($full) ? $full : null;
    }

    /** Resolve a mysql client binary: env override → Laragon bundle → PATH. */
    private function bin(string $tool): string
    {
        $env = env('BACKUP_'.strtoupper($tool).'_BINARY');
        if ($env) {
            return $env;
        }
        foreach (glob('C:/laragon/bin/mysql/*/bin/'.$tool.'.exe') ?: [] as $candidate) {
            return $candidate;
        }

        return $tool;
    }
}
