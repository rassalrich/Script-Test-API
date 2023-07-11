<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use App\Traits\ValidationTrait;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest
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
            'every' => 'nullable|array',
            'every.*' => [Rule::in(["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"])],
            'time' => 'required|date_format:"H:i"',
            'date' => 'nullable|date_format:"Y-m-d"',
        ];
    }
}
