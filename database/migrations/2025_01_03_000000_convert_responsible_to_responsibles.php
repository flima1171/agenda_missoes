<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migração: troca o campo "responsible" (uma pessoa) por
     * "responsibles" (lista em JSON), permitindo mais de um responsável.
     */
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->text('responsibles')->nullable()->after('responsible');
        });

        DB::table('missions')->orderBy('id')->get(['id', 'responsible'])->each(function ($row) {
            DB::table('missions')->where('id', $row->id)->update([
                'responsibles' => json_encode(array_filter([$row->responsible])),
            ]);
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('responsible');
        });
    }

    /**
     * Reverte a migração.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('responsible', 80)->nullable()->after('time');
        });

        DB::table('missions')->orderBy('id')->get(['id', 'responsibles'])->each(function ($row) {
            $list = json_decode((string) $row->responsibles, true) ?: [];
            DB::table('missions')->where('id', $row->id)->update([
                'responsible' => $list[0] ?? null,
            ]);
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('responsibles');
        });
    }
};
