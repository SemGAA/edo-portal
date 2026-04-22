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
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('email');
            $table->string('phone')->nullable()->after('position');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->after('id');
            $table->string('document_type')->default('internal')->after('name');
            $table->string('status')->default('draft')->after('document_type');
            $table->string('priority')->default('normal')->after('status');
            $table->text('summary')->nullable()->after('file');
            $table->string('external_partner')->nullable()->after('summary');
            $table->foreignId('author_id')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            $table->date('due_at')->nullable()->after('visibility');
            $table->timestamp('submitted_at')->nullable()->after('due_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->timestamp('archived_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approver_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn([
                'registration_number',
                'document_type',
                'status',
                'priority',
                'summary',
                'external_partner',
                'due_at',
                'submitted_at',
                'approved_at',
                'archived_at',
                'rejection_reason',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['position', 'phone']);
        });
    }
};
