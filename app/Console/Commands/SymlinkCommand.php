<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SymlinkCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'storage:link';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'To create the symbolic link, you may use the storage:link Artisan command';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$target = base_path('storage/app/public');
		$link = base_path('public/storage');

		symlink($target, $link);
		echo readlink($link) . "\n";
	}
}
