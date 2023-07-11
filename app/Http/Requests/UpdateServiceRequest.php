<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use App\Models\Service;
use App\Rules\ServiceValue;
use App\Traits\ValidationTrait;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
            'id' => 'required|numeric',
            'type' => ['required', Rule::in(Service::$serviceTypes)],
            'value' => ['required', new ServiceValue]
        ];
    }
}
