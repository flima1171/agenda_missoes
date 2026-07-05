<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Fase A2: criar/redefinir usuário pela linha de comando. Essencial no deploy
 * offline (intranet air-gapped) — não há e-mail, logo não há "esqueci a senha";
 * o admin da VM redefine senhas por aqui. Se o e-mail já existir, atualiza a
 * senha (e o papel) em vez de duplicar.
 */
class CreateUser extends Command
{
    protected $signature = 'app:create-user
        {--name= : Nome completo}
        {--email= : E-mail (identificador de login)}
        {--password= : Senha (mínimo 8 caracteres)}
        {--nome-guerra= : Nome de guerra para exibição (opcional)}
        {--admin : Marca o usuário como administrador}';

    protected $description = 'Cria um novo usuário ou redefine a senha de um existente (offline)';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('E-mail');
        $password = $this->option('password') ?: $this->secret('Senha');
        $isAdmin = (bool) $this->option('admin');

        $existente = User::where('email', $email)->first();

        $name = $this->option('name') ?: ($existente->name ?? $this->ask('Nome completo'));
        $nomeGuerra = $this->option('nome-guerra') ?: ($existente->nome_guerra ?? null);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
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
            $this->info('Usuário "'.$email.'" atualizado (senha redefinida).');
        } else {
            User::create([
                'name' => $name,
                'nome_guerra' => $nomeGuerra,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]);
            $this->info('Usuário "'.$email.'" criado'.($isAdmin ? ' como administrador.' : '.'));
        }

        return self::SUCCESS;
    }
}
