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
                $tempFiles = Storage::disk($tempDisk)->allFiles($tempFolder);
                $this->info('Total temporary files: ' . count($tempFiles));
                if (Storage::disk($tempDisk)->deleteDirectory($tempFolder)) {
                    $this->info('Deleted temporary files: ' . count($tempFiles));
                    return 0;
                }
                $this->info('Could not delete files.');
                return 1;
            }
            $this->info('Operation cancelled.');
            return 0;
        }

        $expiredFiles = Filepond::where('expires_at', '<=', now());
        $this->info('Total expired files: ' . $expiredFiles->count());
        if (Storage::disk($tempDisk)->delete($expiredFiles->pluck('filepath')->toArray())) {
            $this->info('Deleted expired files: ' . $expiredFiles->count());
            $expiredFiles->forceDelete();
            return 0;
        }
        $this->info('Could not delete files.');
        return 1;
    }
}
