<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('identity_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['superadmin', 'dosen', 'mahasiswa'])->default('mahasiswa');
            $table->enum('class', ['A', 'B', 'C', 'D'])->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id('id_course');
            $table->string('course_title');
            $table->string('docker_image');
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id('id_module');
            $table->foreignId('id_course')
                ->constrained('courses', 'id_course')
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('material_pdf_path')->nullable();
            $table->string('file_exe')->nullable();
            $table->unsignedInteger('time_limit');
            $table->timestamps();
        });

        Schema::create('quiz_question', function (Blueprint $table) {
            $table->id('id_question');
            $table->foreignId('id_module')
                ->constrained('modules', 'id_module')
                ->cascadeOnDelete();
            $table->text('question');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->enum('correct_option', ['a', 'b', 'c', 'd']);
            $table->timestamps();
        });

        Schema::create('lab_questions', function (Blueprint $table) {
            $table->id('id_question');
            $table->foreignId('id_module')
                ->constrained('modules', 'id_module')
                ->cascadeOnDelete();
            $table->text('question');
            $table->text('output');
            $table->timestamps();
        });

        Schema::create('module_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained('modules', 'id_module')
                ->cascadeOnDelete();
            $table->string('status')->default('in_progress');
            $table->unsignedInteger('current_question_index')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'module_id']);
        });

        Schema::create('quiz_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('quiz_question', 'id_question')
                ->cascadeOnDelete();
            $table->enum('selected_option', ['a', 'b', 'c', 'd'])->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
        });

        Schema::create('lab_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_question_id')
                ->constrained('lab_questions', 'id_question')
                ->cascadeOnDelete();
            $table->longText('submitted_code')->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'lab_question_id']);
        });

        DB::table('users')->insert([
            'identity_id' => 'superadmin',
            'name' => 'Superadmin',
            'email' => 'superadmin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('superadmin1234'),
            'role' => 'superadmin',
            'class' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_progresses');
        Schema::dropIfExists('quiz_progresses');
        Schema::dropIfExists('module_progresses');
        Schema::dropIfExists('lab_questions');
        Schema::dropIfExists('quiz_question');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
