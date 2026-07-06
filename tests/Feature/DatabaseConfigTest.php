<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase A4: trava o hardening de SQLite (modo WAL) feito em config/database.php.
 * O banco dos testes é :memory: (não vira WAL), então além de checar os valores
 * de config validamos o COMPORTAMENTO num banco em ARQUIVO temporário: o
 * SQLiteConnector do Laravel aplica `pragma journal_mode = WAL` ao conectar.
 */
class DatabaseConfigTest extends TestCase
{
    public function test_config_sqlite_define_wal_normal_e_busy_timeout(): void
    {
        $this->assertSame('WAL', config('database.connections.sqlite.journal_mode'));
        $this->assertSame('NORMAL', config('database.connections.sqlite.synchronous'));
        $this->assertSame(5000, config('database.connections.sqlite.busy_timeout'));
    }

    public function test_banco_em_arquivo_abre_em_modo_wal(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'walcheck_').'.sqlite';
        touch($tmp);

        config()->set('database.connections.walcheck', array_merge(
            config('database.connections.sqlite'),
            ['database' => $tmp]
        ));

        try {
            $mode = DB::connection('walcheck')->select('pragma journal_mode')[0]->journal_mode;
            $this->assertSame('wal', strtolower($mode));
        } finally {
            DB::purge('walcheck');
            @unlink($tmp);
            @unlink($tmp.'-wal');
            @unlink($tmp.'-shm');
        }
    }
}
