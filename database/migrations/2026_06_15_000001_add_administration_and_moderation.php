<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 24)->default('user')->after('student_id');
            $table->string('status', 24)->default('active')->after('role');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('moderation_status', 24)->default('active')->after('status');
            $table->text('moderation_reason')->nullable()->after('moderation_status');
        });

        Schema::table('item_claims', function (Blueprint $table) {
            $table->string('dispute_status', 24)->default('none')->after('status');
            $table->text('dispute_reason')->nullable()->after('dispute_status');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor', 100);
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('item_claims', fn (Blueprint $table) => $table->dropColumn(['dispute_status', 'dispute_reason']));
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn(['moderation_status', 'moderation_reason']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'status']));
    }
};
