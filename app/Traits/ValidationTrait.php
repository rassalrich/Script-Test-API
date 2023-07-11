<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ValidationTrait {
    protected function errorResponse(): ?JsonResponse
    {
        return resJson([], $this->validator->errors()->first(), false);
    }
}
