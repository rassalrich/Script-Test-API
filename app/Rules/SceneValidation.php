<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SceneValidation implements Rule
{
	private $userId;

	/**
	 * Create a new rule instance.
	 *
	 * @return void
	 */
	public function __construct($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * Determine if the validation rule passes.
	 *ُ
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes($attribute, $value): bool
	{
		try {
			$query = "SELECT scenes.id FROM scenes INNER JOIN buildings ON scenes.building_id = buildings.id WHERE buildings.user_id = " . $this->userId;
			$result = DB::select($query);

			$scenes = collect($result)->pluck('id')->toArray();

			return in_array($value, $scenes);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get the validation error message.
	 *
	 * @return string
	 */
	public function message(): string
	{
		return 'The :attribute must be valid.';
	}
}
