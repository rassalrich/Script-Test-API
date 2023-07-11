<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SceneJobCurtainValidation implements Rule
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
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes($attribute, $curtains): bool
	{
		try {
			if ($curtains === null) return true;

			$pattern = "/[^0-9,;]/i";
			$curtains = trim(preg_replace($pattern, '', $curtains));

			$curtains = explode(';', $curtains);
			if (!is_array($curtains)) throw new \Exception();

			for ($i = 0; $i < count($curtains); $i++) {
				$c = explode(',', $curtains[$i]);
				if (!is_array($c)) throw new \Exception();

				if (count($c) !== 2) throw new \Exception();

				if (!is_numeric($c[0]) || !is_numeric($c[1])) throw new \Exception();

				if (!in_array($c[1], ['0', '1'])) throw new \Exception();
			}

			return true;
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
