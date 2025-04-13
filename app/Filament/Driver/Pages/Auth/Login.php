<?php

namespace App\Filament\Driver\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Using the correct method name that exists in BaseLogin
//        $this->form->fill([
//            'email' => request()->input('email'),
//        ]);

        // Alternatively, if you're not sure of the field name, you can check
        // if the field exists first:
        // $emailField = 'email';
        // if ($this->form->hasComponent($emailField)) {
        //     $this->form->fill([$emailField => request()->input('email')]);
        // }
    }
}
