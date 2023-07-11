<?php

namespace App\Http\Requests;

use Anik\Form\FormRequest;
use App\Rules\SceneJobCurtainValidation;
use App\Rules\SceneValidation;
use App\Traits\ValidationTrait;

class CreateSceneJobRequest extends FormRequest
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
        $user  = request()->user();

        return [
            'name' => 'required|max:32',
            'scene_id' => ['bail', 'required', 'numeric', new SceneValidation($user->id)],
            'run_at' => 'bail|required|date_format:H:i',
            'curtains' => [new SceneJobCurtainValidation($user->id)]
        ];
    }
}
