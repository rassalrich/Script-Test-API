<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		AuthorizationException::class,
		OAuthServerException::class,
		HttpException::class,
		ModelNotFoundException::class,
		ValidationException::class,
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Throwable  $exception
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function report(Throwable $exception)
	{
		parent::report($exception);
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Throwable  $exception
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 *
	 * @throws \Throwable
	 */
	public function render($request, Throwable $e)
	{
		$rendered = parent::render($request, $e);

		if ($e instanceof HttpException) {
			return resJson([], $e->getMessage(), false, $rendered->getStatusCode());
		} else if ($e instanceof OAuthServerException) {
			return resJson([], $e->getMessage(), false, 401);
		}

		return resJson([], "Something went wrong.", false, $rendered->getStatusCode());
	}
}
