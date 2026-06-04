<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePasswordModal extends Component
{
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function save(): void
    {
        $this->validate([
            'current_password' => ['required', function (string $attribute, mixed $value, \Closure $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('A palavra-passe actual está incorrecta.');
                }
            }],
            'new_password' => ['required', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'A palavra-passe actual é obrigatória.',
            'new_password.required' => 'A nova palavra-passe é obrigatória.',
            'new_password.min' => 'A nova palavra-passe deve ter pelo menos 6 caracteres.',
            'new_password.confirmed' => 'A confirmação da palavra-passe não coincide.',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($this->new_password);
        $user->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('password-changed');

        Notification::make()
            ->success()
            ->title('Palavra-passe alterada')
            ->body('A sua palavra-passe foi alterada com sucesso.')
            ->send();
    }

    public function render()
    {
        return view('livewire.change-password-modal');
    }
}
