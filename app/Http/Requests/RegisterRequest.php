<?php

namespace App\Http\Requests;

use App\Rules\UniqueGitHubUser;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'github_id' => $this->session->get('githubData.id'),
            'github_username' => $this->session->get('githubData.username'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'username' => 'required|alpha_dash|max:40|unique:users',
            'rules' => 'accepted',
            'terms' => 'accepted',
            'github_id' => ['required', new UniqueGitHubUser],
        ];
    }

    public function name(): string
    {
        return $this->get('name');
    }

    public function emailAddress(): string
    {
        return $this->get('email');
    }

    public function username(): string
    {
        return $this->get('username');
    }

    public function githubId(): string
    {
        return $this->get('github_id');
    }

    public function githubUsername(): string
    {
        return $this->get('github_username', '');
    }
}
