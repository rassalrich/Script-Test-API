<?php

namespace App\Providers;

use App\Events\ThermostatEvent;
use App\Listeners\ThermostatListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\ChannelManager;
use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	/**
	 * Register any events for your application.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		Event::listen(
			ThermostatEvent::class,
			[ThermostatListener::class, 'handle']
		);
	}
}
