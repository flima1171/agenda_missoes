<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Cria/redefine usuário pela linha de comando. Essencial no deploy offline
 * (intranet air-gapped) — não há e-mail, logo não há "esqueci a senha"; o
 * admin da VM redefine senhas por aqui. Se o usuário já existir, atualiza a
 * senha (e o papel) em vez de duplicar.
 */
class CreateUser extends Command
{
    protected $signature = 'app:create-user
        {--name= : Nome completo}
        {--username= : Usuário (identificador de login)}
        {--password= : Senha (mínimo 8 caracteres)}
        {--nome-guerra= : Nome de guerra para exibição (opcional)}
        {--admin : Marca o usuário como administrador}';

    protected $description = 'Cria um novo usuário ou redefine a senha de um existente (offline)';

    public function handle(): int
    {
        $username = $this->option('username') ?: $this->ask('Usuário');
        $password = $this->option('password') ?: $this->secret('Senha');
        $isAdmin = (bool) $this->option('admin');

        $existente = User::where('username', $username)->first();

        $name = $this->option('name') ?: ($existente->name ?? $this->ask('Nome completo'));
        $nomeGuerra = $this->option('nome-guerra') ?: ($existente->nome_guerra ?? null);

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        if ($existente) {
            $existente->update([
                'name' => $name,
                'nome_guerra' => $nomeGuerra,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]);
            $this->info('Usuário "'.$username.'" atualizado (senha redefinida).');
        } else {
            User::create([
                'name' => $name,
                'nome_guerra' => $nomeGuerra,
                'username' => $username,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]);
            $this->info('Usuário "'.$username.'" criado'.($isAdmin ? ' como administrador.' : '.'));
        }

        return self::SUCCESS;
    }
}
