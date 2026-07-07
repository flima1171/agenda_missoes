<?php

namespace App\Livewire\Auth;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Login feito à mão (sem Breeze/Fortify/npm), para funcionar offline.
 * Usa Auth::attempt + rate limiting por usuário+IP para conter força bruta.
 */
class Login extends Component
{
    #[Validate('required|string')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'As credenciais informadas não conferem.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        ActivityLog::record('login', 'usuario', Auth::id(), Auth::user()->nomeExibicao().' entrou no sistema.');

        $this->redirectRoute('painel', navigate: false);
    }

    /**
     * Bloqueia após tentativas demais do mesmo usuário+IP.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => 'Tentativas demais. Tente novamente em '.$seconds.' segundos.',
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
