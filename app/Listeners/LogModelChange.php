<?php

namespace App\Listeners;

use App\Events\UpdatingModel;
use App\Models\Changelog;

class LogModelChange
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UpdatingModel $modelChange): void
    {
        $model = $modelChange->model;
        // $m->getChanges() is only populated after the save,
        // so we have to collect the old values from the dirty keys
        $changes = collect($model->getDirty())
            ->map(fn ($item, $key) => $model->getOriginal($key));

        Changelog::create([
            'type' => get_class($model),
            'type_id' => $model->getKey(),
            'previous_data' => $changes,
            'user_id' => \Auth::user()->id,
        ]);
    }
}
