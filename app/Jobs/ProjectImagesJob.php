<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectImagesJob implements ShouldQueue
{
	use InteractsWithQueue, Queueable, SerializesModels;

	public $url;

	public $name;

	public $disk = 'public';

	public $path = 'projects';

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(string $url, string $name)
	{
		$this->url = $url;
		$this->name = $name;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		try {
			$content = file_get_contents($this->url);

			Storage::disk($this->disk)->put($this->path . '/' . $this->name, $content);
		} catch (\Exception $e) {
			Log::error("[Upload] [Project] " . $e->getMessage());
		}
	}
}
