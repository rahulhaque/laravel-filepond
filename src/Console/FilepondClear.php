<?php

namespace RahulHaque\Filepond\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Models\Filepond;

class FilepondClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filepond:clear {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear temporary files uploaded with FilePond';

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
     * @return int
     */
    public function handle()
    {
        $tempDisk = config('filepond.temp_disk', 'local');
        $tempFolder = config('filepond.temp_folder', 'filepond/temp');

        if ($this->option('all')) {
            if ($this->confirm('Are you sure?', true)) {
                Filepond::truncate();
                $this->info('Fileponds table truncated.');
                Storage::disk($tempDisk)->deleteDirectory($tempFolder);
                $this->info('Temporary files and folders deleted.');
                return 0;
            }
            $this->info('Operation cancelled.');
            return 0;
        }

        $expiredFiles = Filepond::withTrashed()->where('expires_at', '<=', now())->select(['id', 'filepath']);
        $this->info('Total expired files and folders: '.$expiredFiles->count());
        if ($expiredFiles->count() > 0) {
            foreach ($expiredFiles->get() as $expiredFile) {
                Storage::disk($tempDisk)->delete($expiredFile->filepath);
                Storage::disk($tempDisk)->deleteDirectory($tempFolder.'/'.$expiredFile->id);
            }
            $this->info('Temporary files and folders deleted.');
            $expiredFiles->forceDelete();
        }
        return 0;
    }
}
