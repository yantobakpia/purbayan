<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        
        if (session()->has('url.intended') && str_contains(session()->get('url.intended'), 'unread-notifications')) {
            session()->forget('url.intended');
        }

        if ($user && ($user->is_admin || $user->email === 'admin@ruangan.com')) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended('/');
    }
}
