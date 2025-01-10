<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            // redefined columns
            $table->renameColumn('fullname', 'name');
            $table->string('username')->change();
            $table->string('email')->change();
            // new columns
            $table->string('provider');
            $table->string('provider_uid');
            $table->string('picture_url')->nullable();
            $table->string('iban')->nullable()->change();
            $table->string('address')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('user', 'name')) {
            Schema::table('user', function (Blueprint $table) {
                $table->renameColumn('name', 'fullname');
            });
        }
    }
};
