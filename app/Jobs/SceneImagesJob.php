<?php

namespace App\Jobs;

use App\Models\Scene;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SceneImagesJob extends Job
{
	public $url;

	public $name;

	public $disk = 'public';

	public $path = 'scenes';

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
			Log::error("[Upload] [Scene] " . $e->getMessage());
		}
	}
}
