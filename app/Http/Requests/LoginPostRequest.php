<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use App\Traits\ValidationTrait;
use Illuminate\Http\JsonResponse;

class LoginPostRequest extends FormRequest
{
    use ValidationTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'email' => 'required',
            'password' => 'required',
            'sync' => 'required|boolean'
        ];
    }
}
