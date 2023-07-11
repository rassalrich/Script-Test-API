<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Boot the authentication services for the application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$now = Carbon::now();

		Passport::tokensExpireIn($now->addDays(30));
		Passport::refreshTokensExpireIn($now->addDays(37));
		Passport::personalAccessTokensExpireIn($now->addMonths(7));
	}
}
