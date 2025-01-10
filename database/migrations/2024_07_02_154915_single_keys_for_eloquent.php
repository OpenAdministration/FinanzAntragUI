<?php

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\Models\Legacy\BookingInstruction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_instruction', function (Blueprint $table) {
            $table->date('instruct_date')->nullable();
        });

        Schema::table('konto_type', function (Blueprint $table) {
            $table->boolean('manually_enterable')->default(false);
        });

        // schema changes are not transaction-able
        DB::transaction(function () {
            $lastId = BankAccount::orderBy('id', 'desc')->pluck('id')->first();
            $newId = $lastId + 1;
            // migrate the old negative keys
            BankAccount::where('id', '<', 1)
                ->each(function ($account) use (&$newId) {
                    $account->manually_enterable = true;
                    $account->id = $newId;
                    $account->save();

                    // update the other foreign keys to the new id
                    BankTransaction::where('konto_id', $account->id)->update(['konto_id' => $newId]);
                    Booking::where('zahlung_type', $account->id)->update(['zahlung_type' => $newId]);
                    BookingInstruction::where('zahlung_type', $account->id)->update(['zahlung_type' => $newId]);
                });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_instruction', function (Blueprint $table) {
            $table->dropColumn('instruct_date');
        });

        Schema::table('konto_type', function (Blueprint $table) {
            $table->dropColumn('manually_enterable');
        });
    }
};
