<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('agent')->index()->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('job_title')->nullable()->after('is_active');
            $table->string('locale', 5)->default('ar')->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('locale');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'job_title', 'locale', 'last_login_at', 'deleted_at']);
        });
    }
};
