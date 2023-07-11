<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ServiceValue implements Rule
{
	/**
	 * Create a new rule instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
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
		// dali_light, cct.dt6, cct.dt8, rgb.dt6, rgb.dt8, curtain, relay

		if (request()->type === 'dali_light') return $this->daliLight($value);

		if (request()->type === 'cct.dt6' || request()->type === 'cct.dt8') return $this->cct($value);

		if (request()->type === 'rgb.dt6' || request()->type === 'rgb.dt8') return $this->rgb($value);

		if (request()->type === 'relay' || request()->type === 'curtain') return is_bool($value);

		return false;
	}

	private function daliLight($value): bool
	{
		return is_numeric($value) && $value >= 0 && $value <= 254;
	}

	private function cct(string $value): bool
	{
		$valueAsArray = explode(',', $value);
		return is_array($valueAsArray) && count($valueAsArray) === 2 && $this->array_is_numeric($valueAsArray);
	}

	private function rgb(string $value): bool
	{
		$valueAsArray = explode(',', $value);
		return is_array($valueAsArray) && count($valueAsArray) === 5 && $this->array_is_numeric($valueAsArray);
	}

	private function array_is_numeric(array $arr): bool
	{
		for ($i = 0; $i < count($arr); $i++) {
			$value = $arr[$i];
			if (!is_numeric($arr[$i])) return false;
		}
		return true;
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
