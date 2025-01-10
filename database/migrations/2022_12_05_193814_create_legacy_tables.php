<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // remove legacy dbs
        Schema::dropIfExists('log_property');
        Schema::dropIfExists('log');
        Schema::dropIfExists('extern_data');
        Schema::dropIfExists('extern_meta');

        if (! Schema::hasTable('user')) {
            Schema::create('user', static function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('fullname', 255);
                $table->string('username', 32);
                $table->string('email', 128);
                $table->string('iban', 32)->default('');
            });
        }
        if (! Schema::hasTable('auslagen')) {
            Schema::create('auslagen', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('projekt_id');
                $table->string('name_suffix')->nullable();
                $table->string('state');
                $table->string('ok-belege')->default('');
                $table->string('ok-hv')->default('');
                $table->string('ok-kv')->default('');
                $table->string('payed')->default('');
                $table->string('rejected')->default('');
                $table->string('zahlung-iban', 1023);
                $table->string('zahlung-name', 127);
                $table->string('zahlung-vwzk', 127);
                $table->string('address', 1023)->default('');
                $table->dateTime('last_change')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->string('last_change_by')->default('');
                $table->string('etag');
                $table->integer('version')->default(1);
                $table->string('created')->default('');
            });
        }
        if (! Schema::hasTable('belege')) {
            Schema::create('belege', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('auslagen_id')->index('auslagen_id');
                $table->string('short', 45)->nullable();
                $table->dateTime('created_on')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('datum')->nullable();
                $table->text('beschreibung');
                $table->integer('file_id')->nullable();
            });
        }
        if (! Schema::hasTable('beleg_posten')) {
            Schema::create('beleg_posten', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('beleg_id')->index('beleg_id');
                $table->integer('short');
                $table->integer('projekt_posten_id');
                $table->decimal('ausgaben', 10, 2)->default(0);
                $table->decimal('einnahmen', 10, 2)->default(0);

                $table->unique(['short', 'beleg_id'], 'short');
            });
        }

        if (! Schema::hasTable('booking')) {
            Schema::create('booking', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('titel_id')->index('titel_id');
                $table->integer('kostenstelle');
                $table->integer('zahlung_id');
                $table->integer('zahlung_type');
                $table->integer('beleg_id');
                $table->string('beleg_type', 16);
                $table->integer('user_id');
                $table->dateTime('timestamp')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->string('comment', 2048);
                $table->float('value');
                $table->integer('canceled')->default(0);

                $table->index(['zahlung_id', 'zahlung_type'], 'zahlung_id');
            });
        }

        if (! Schema::hasTable('booking_instruction')) {
            Schema::create('booking_instruction', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id');
                $table->integer('zahlung');
                $table->integer('zahlung_type');
                $table->integer('beleg');
                $table->string('beleg_type', 16);
                $table->integer('by_user')->index('by_user');
                $table->boolean('done')->default(false);
            });
        }

        if (! Schema::hasTable('comments')) {
            Schema::create('comments', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('target_id');
                $table->string('target', 64)->nullable();
                $table->dateTime('timestamp')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->string('creator', 128);
                $table->string('creator_alias', 256);
                $table->string('text', 65535);
                $table->tinyInteger('type')->default(0)->comment('0 = comment, 1 = state_change, 2 = admin only');
            });
        }

        if (! Schema::hasTable('filedata')) {
            Schema::create('filedata', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->longText('data')->charset('binary')->nullable();
                $table->string('diskpath', 511)->nullable();
            });
        }

        if (! Schema::hasTable('fileinfo')) {
            Schema::create('fileinfo', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->string('link', 127); // fk zu belege.id
                $table->dateTime('added_on')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->string('hashname');
                $table->string('filename');
                $table->integer('size')->default(0);
                $table->string('fileextension', 45)->default('');
                $table->string('mime', 256)->nullable();
                $table->string('encoding', 45)->nullable();
                $table->integer('data')->nullable()->index('data');
            });
        }

        if (! Schema::hasTable('haushaltsgruppen')) {
            Schema::create('haushaltsgruppen', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('hhp_id')->index('hhp_id');
                $table->string('gruppen_name', 128);
                $table->boolean('type');
            });
        }

        if (! Schema::hasTable('haushaltsplan')) {
            Schema::create('haushaltsplan', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->date('von')->nullable();
                $table->date('bis')->nullable();
                $table->string('state', 64);
            });
        }

        if (! Schema::hasTable('haushaltstitel')) {
            Schema::create('haushaltstitel', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('hhpgruppen_id')->index('hhpgruppen_id');
                $table->string('titel_name', 128);
                $table->string('titel_nr', 10);
                $table->float('value', 10, 0);
            });
        }

        if (! Schema::hasTable('konto')) {
            Schema::create('konto', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id');
                $table->integer('konto_id')->index('konto_id');
                $table->date('date');
                $table->date('valuta');
                $table->string('type', 128);
                $table->string('empf_iban', 40)->default('');
                $table->string('empf_bic', 11)->nullable()->default('');
                $table->string('empf_name', 128)->default('');
                $table->float('primanota', 10, 0)->default(0);
                $table->decimal('value', 10, 2);
                $table->decimal('saldo', 10, 2);
                $table->string('zweck', 512);
                $table->string('comment', 128)->default('');
                $table->integer('gvcode')->default(0);
                $table->string('customer_ref', 128)->nullable();

                $table->primary(['id', 'konto_id']);
            });
        }

        if (! Schema::hasTable('konto_bank')) {
            Schema::create('konto_bank', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->string('url', 256);
                $table->integer('blz');
                $table->string('name', 256);
            });
        }

        if (! Schema::hasTable('konto_credentials')) {
            Schema::create('konto_credentials', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->string('name', 63);
                $table->integer('bank_id')->index('bank_id');
                $table->integer('owner_id')->index('owner_id');
                $table->string('bank_username', 32);
                $table->integer('tan_mode')->nullable();
                $table->string('tan_mode_name', 63)->nullable();
                $table->string('tan_medium_name', 63)->nullable();
            });
        }

        if (! Schema::hasTable('konto_type')) {
            Schema::create('konto_type', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->string('name', 32);
                $table->string('short', 2);
                $table->date('sync_from')->nullable();
                $table->date('sync_until')->nullable();
                $table->string('iban', 32)->nullable();
                $table->date('last_sync')->nullable();
            });
        }

        if (! Schema::hasTable('projekte')) {
            Schema::create('projekte', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id', true);
                $table->integer('creator_id')->index('creator_id');
                $table->dateTime('createdat');
                $table->dateTime('lastupdated');
                $table->integer('version')->default(1);
                $table->string('state', 32);
                $table->integer('stateCreator_id')->index('stateCreator_id');
                $table->string('name', 128)->nullable();
                $table->string('responsible', 128)->nullable()->comment('EMAIL');
                $table->string('org', 64)->nullable();
                $table->string('org-mail', 128)->nullable();
                $table->string('protokoll', 256)->nullable();
                $table->string('recht', 64)->nullable();
                $table->string('recht-additional', 128)->nullable();
                $table->date('date-start')->nullable();
                $table->date('date-end')->nullable();
                $table->text('beschreibung')->nullable();
            });
        }
        if (! Schema::hasTable('projektposten')) {
            Schema::create('projektposten', static function (Blueprint $table) {
                $table->comment('');
                $table->integer('id');
                $table->integer('projekt_id')->index('projekt_id');
                $table->integer('titel_id')->nullable();
                $table->float('einnahmen', 10, 0);
                $table->float('ausgaben', 10, 0);
                $table->string('name', 128);
                $table->string('bemerkung', 256);

                $table->primary(['id', 'projekt_id']);
            });
        }

        Schema::table('auslagen', static function (Blueprint $table) {
            $table->foreign(['projekt_id'], 'dev__auslagen_ibfk_1')->references(['id'])->on('projekte');
        });

        Schema::table('belege', static function (Blueprint $table) {
            $table->foreign(['auslagen_id'], 'dev__belege_ibfk_1')->references(['id'])->on('auslagen');
        });

        Schema::table('beleg_posten', static function (Blueprint $table) {
            $table->foreign(['beleg_id'], 'dev__beleg_posten_ibfk_1')->references(['id'])->on('belege');
        });

        Schema::table('booking', static function (Blueprint $table) {
            $table->foreign(['zahlung_id', 'zahlung_type'], 'dev__booking_ibfk_1')->references(['id', 'konto_id'])->on('konto');
            $table->foreign(['titel_id'], 'dev__booking_ibfk_2')->references(['id'])->on('haushaltstitel');
            $table->foreign(['user_id'], 'dev__booking_ibfk_3')->references(['id'])->on('user');
        });

        Schema::table('booking_instruction', static function (Blueprint $table) {
            $table->foreign(['by_user'], 'dev__booking_instruction_ibfk_1')->references(['id'])->on('user');
        });

        Schema::table('fileinfo', static function (Blueprint $table) {
            $table->foreign(['data'], 'dev__fileinfo_ibfk_1')->references(['id'])->on('filedata');
        });

        Schema::table('haushaltsgruppen', static function (Blueprint $table) {
            $table->foreign(['hhp_id'], 'dev__haushaltsgruppen_ibfk_1')->references(['id'])->on('haushaltsplan');
        });

        Schema::table('haushaltstitel', static function (Blueprint $table) {
            $table->foreign(['hhpgruppen_id'], 'dev__haushaltstitel_ibfk_1')->references(['id'])->on('haushaltsgruppen');
        });

        Schema::table('konto', static function (Blueprint $table) {
            $table->foreign(['konto_id'], 'dev__konto_ibfk_1')->references(['id'])->on('konto_type');
        });

        Schema::table('konto_credentials', static function (Blueprint $table) {
            $table->foreign(['owner_id'], 'dev__konto_credentials_ibfk_1')->references(['id'])->on('user');
            $table->foreign(['bank_id'], 'dev__konto_credentials_ibfk_2')->references(['id'])->on('konto_bank');
        });

        Schema::table('projekte', static function (Blueprint $table) {
            $table->foreign(['creator_id'], 'dev__projekte_ibfk_1')->references(['id'])->on('user');
            $table->foreign(['stateCreator_id'], 'dev__projekte_ibfk_2')->references(['id'])->on('user');
        });

        Schema::table('projektposten', static function (Blueprint $table) {
            $table->foreign(['projekt_id'], 'dev__projektposten_ibfk_1')->references(['id'])->on('projekte');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projektposten', static function (Blueprint $table) {
            $table->dropForeign('dev__projektposten_ibfk_1');
        });

        Schema::table('projekte', static function (Blueprint $table) {
            $table->dropForeign('dev__projekte_ibfk_1');
            $table->dropForeign('dev__projekte_ibfk_2');
        });

        Schema::table('konto_credentials', static function (Blueprint $table) {
            $table->dropForeign('dev__konto_credentials_ibfk_1');
            $table->dropForeign('dev__konto_credentials_ibfk_2');
        });

        Schema::table('konto', static function (Blueprint $table) {
            $table->dropForeign('dev__konto_ibfk_1');
        });

        Schema::table('haushaltstitel', static function (Blueprint $table) {
            $table->dropForeign('dev__haushaltstitel_ibfk_1');
        });

        Schema::table('haushaltsgruppen', static function (Blueprint $table) {
            $table->dropForeign('dev__haushaltsgruppen_ibfk_1');
        });

        Schema::table('fileinfo', static function (Blueprint $table) {
            $table->dropForeign('dev__fileinfo_ibfk_1');
        });

        Schema::table('booking_instruction', static function (Blueprint $table) {
            $table->dropForeign('dev__booking_instruction_ibfk_1');
        });

        Schema::table('booking', static function (Blueprint $table) {
            $table->dropForeign('dev__booking_ibfk_1');
            $table->dropForeign('dev__booking_ibfk_2');
            $table->dropForeign('dev__booking_ibfk_3');
        });

        Schema::table('beleg_posten', static function (Blueprint $table) {
            $table->dropForeign('dev__beleg_posten_ibfk_1');
        });

        Schema::table('belege', static function (Blueprint $table) {
            $table->dropForeign('dev__belege_ibfk_1');
        });

        Schema::table('auslagen', static function (Blueprint $table) {
            $table->dropForeign('dev__auslagen_ibfk_1');
        });

        Schema::dropIfExists('projektposten');

        Schema::dropIfExists('projekte');

        Schema::dropIfExists('personal_access_tokens');

        Schema::dropIfExists('konto_type');

        Schema::dropIfExists('konto_credentials');

        Schema::dropIfExists('konto_bank');

        Schema::dropIfExists('konto');

        Schema::dropIfExists('haushaltstitel');

        Schema::dropIfExists('haushaltsplan');

        Schema::dropIfExists('haushaltsgruppen');

        Schema::dropIfExists('fileinfo');

        Schema::dropIfExists('filedata');

        Schema::dropIfExists('comments');

        Schema::dropIfExists('booking_instruction');

        Schema::dropIfExists('booking');

        Schema::dropIfExists('beleg_posten');

        Schema::dropIfExists('belege');

        Schema::dropIfExists('auslagen');

        Schema::dropIfExists('user');

    }
};
