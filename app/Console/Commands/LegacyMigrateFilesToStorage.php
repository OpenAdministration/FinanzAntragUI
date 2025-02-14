<?php

namespace App\Console\Commands;

use App\Models\Legacy\ExpensesReceipt;
use App\Models\Legacy\FileInfo;
use Illuminate\Console\Command;

class LegacyMigrateFilesToStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:migrate-files-to-storage {--delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates Files form DB to Storage';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        FileInfo::lazy(20)->each(function (FileInfo $fileInfo) {
            $data = $fileInfo->fileData;
            $link = $fileInfo->link;
            $beleg = ExpensesReceipt::find($link);
            $expenses_id = $beleg?->auslagen_id;
            $pdfData = $data?->data;
            $hash = $fileInfo->hashname;
            $path = "auslagen/$expenses_id/$hash.pdf";
            if ($pdfData !== null) {
                if (empty($data->diskpath)) {
                    $data->diskpath = $path;
                }
                if (! \Storage::has($path)) {
                    \Storage::put($path, $pdfData);
                }
                if ($this->option('delete') === true) {
                    $data->data = null;
                }
                $data->save();
                $this->info($path);
            }
        });
    }
}
