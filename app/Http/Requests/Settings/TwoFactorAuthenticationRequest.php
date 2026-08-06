<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Laravel\Fortify\Features;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;
use Illuminate\Contracts\Validation\ValidationRule;

final class TwoFactorAuthenticationRequest extends FormRequest
{
    use InteractsWithTwoFactorState;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Features::enabled(Features::twoFactorAuthentication());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
