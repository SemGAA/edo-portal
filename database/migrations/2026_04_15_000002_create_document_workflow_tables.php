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
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('workflow_round')->default(0)->after('rejection_reason');
        });

        Schema::create('document_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round')->default(1);
            $table->unsignedInteger('sequence');
            $table->string('title');
            $table->string('role_code');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('document_workflow_steps')->nullOnDelete();
            $table->string('action');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_action_logs');
        Schema::dropIfExists('document_workflow_steps');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('workflow_round');
        });
    }
};
