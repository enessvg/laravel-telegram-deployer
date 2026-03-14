<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_deployer_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->string('status')->index();
            $table->string('chat_id')->nullable()->index();
            $table->string('user_id')->nullable()->index();
            $table->string('username')->nullable();
            $table->text('request_text')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('steps')->nullable();
            $table->text('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_deployer_runs');
    }
};
