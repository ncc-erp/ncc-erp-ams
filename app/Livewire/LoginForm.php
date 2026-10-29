<?php

namespace App\Livewire;

use Livewire\Component;

class LoginForm extends Component
{
    public $username = '';
    public $password = '';
    public $can_submit = false;

    public function rules()
    {
        return [
            'username' => 'required|string|max:255',
            'password' => 'required',
        ];
    }

    public function updated($field): void
    {
        if (is_null($field) || empty($field)) {
            $this->can_submit = false;
            return;
        }

        $this->validateOnly($field);
        $errors = $this->getErrorBag();
        $this->can_submit = $this->username !== ''
            && $this->password !== ''
            && ! $errors->has('username')
            && ! $errors->has('password');
    }

    public function submitForm()
    {
        if (auth()->attempt($this->validate())) {
            return redirect()->intended('/');
        }

        return session()->flash('error', trans('auth/message.account_not_found'));
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}
