<?php

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
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('type'); // project or application
            $table->uuid('name');
            $table->uuid('version');
            $table->string('title');
            $table->string('description');
            $table->boolean('active');

            $table->timestamps();

            $table->unique(['name', 'version']);
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_definition_id');
            $table->string('name'); // var name
            $table->string('label'); // menschenlesbar
            $table->string('type'); // siehe w3 referenz zzgl. eigene Types (money, iban, ...)
            $table->string('default_value')->nullable();
            $table->integer('position'); // internal render order
            $table->string('view_key'); // where to render

            $table->timestamps();
            $table->unique(['form_definition_id', 'name']);
        });

        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_field_id');
            $table->string('text');
            $table->string('subtext');
            $table->integer('position'); // internal render order

            $table->foreign('form_field_id')->references('id')->on('form_fields');
        });

        Schema::create('form_field_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_field_id');
            $table->string('validation_rule');
            $table->string('validation_parameter');
        });

        Schema::create('student_body_duties', function (Blueprint $table) {
            $table->id();
            $table->string('short_key'); // ex. "th-2004.internationals"
            $table->string('long_key'); // ex. "th-2004.internationals"
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->integer('version');
            $table->string('state'); // id?
            $table->integer('user_id'); // shall we? alt name: creator_id // creator_user_id

            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description');
            // $table->string('target_group'); -> zu veranstaltungspezifisch?
            // $table->string('participants_total');
            // $table->string('participants_students');

            $table->json('extra_fields');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user');
        });

        Schema::create('project_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects');
        });

        Schema::create('projects_to_student_body_duties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_body_duties_id');
            $table->unsignedBigInteger('project_id');
            $table->timestamps();

            $table->foreign('student_body_duties_id', 'p2sbd_student_body_duties_id')->references('id')->on('student_body_duties');
            $table->foreign('project_id')->references('id')->on('projects');
        });

        Schema::create('legal_bases', function (Blueprint $table) {
            $table->uuid()->primary();
            // matching translation keys:
            // tui.legal_basis.stura_short
            // tui.legal_basis.stura_long
            // tui.legal_basis.stura_details_label
            $table->boolean('has_details'); // z.B. Beschluss Nr.
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // eigentlich akteure
            // fördergebende Instituion?
            $table->unsignedBigInteger('project_id');
            $table->string('state'); // who? from -> to ? when? => logging somewhere else // maybe int id foreign key instead

            $table->uuid('form_name');
            $table->uuid('form_version');

            $table->integer('version');
            $table->uuid('legal_basis');
            $table->string('legal_basis_details');
            $table->text('constraints'); // auflagen

            $table->decimal('funding_total');

            $table->json('extra_fields');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user'); // eigentlich akteure
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('legal_basis')->references('uuid')->on('legal_bases');
            $table->foreign(['form_name', 'form_version'])->references(['name', 'version'])->on('form_definitions');
            // änderungsanträge auf Anträge -> out of scope, wir speichern nur das letzte
        });

        Schema::create('application_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications');
        });

        Schema::create('finance_plan_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('name');
            $table->boolean('is_expense'); // 1 = Ausgabe 0 = Einnahme
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications');
        });

        Schema::create('finance_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('topic_id');
            $table->string('name');
            $table->decimal('value'); // lieber integer und durch 100 teilen?
            $table->integer('amount');
            $table->decimal('total');
            $table->string('description');
            $table->timestamps();

            $table->foreign('topic_id')->references('id')->on('finance_plan_topics');
        });

        Schema::create('actors', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_organisation');

            $table->string('name');
            $table->string('street');
            $table->string('city');
            $table->string('zip_code');

            $table->string('iban');
            $table->string('bic');

            $table->string('website');
            $table->string('register_number');
            $table->boolean('vat_deduction');
            $table->boolean('charity');
            $table->timestamps();
        });

        Schema::create('actor_mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('actor_phones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('actor_socials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('provider');
            $table->string('url');
            $table->timestamps();
        });

        // TODO später: status definiton / transitions definitons
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_definitions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_field_options');
        Schema::dropIfExists('form_field_validations');
        Schema::dropIfExists('form_field_data');
        Schema::dropIfExists('form_field_id');

        Schema::dropIfExists('student_body_duties');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_attachments');
        Schema::dropIfExists('project_to_student_body_duties');

        Schema::dropIfExists('legal_bases');

        Schema::dropIfExists('applications');
        Schema::dropIfExists('applications_attachments');

        Schema::dropIfExists('finance_plan_topics');
        Schema::dropIfExists('finance_plan_items');

        Schema::dropIfExists('actors');
        Schema::dropIfExists('actor_mails');
        Schema::dropIfExists('actor_phones');
        Schema::dropIfExists('actor_socials');
    }
};
