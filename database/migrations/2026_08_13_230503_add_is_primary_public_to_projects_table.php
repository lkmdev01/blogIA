<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_primary_public')->default(false)->after('hero_image_url');
        });

        $primaryProjectId = DB::table('projects')->orderBy('id')->value('id');

        if ($primaryProjectId) {
            DB::table('projects')
                ->where('id', $primaryProjectId)
                ->update(['is_primary_public' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_primary_public');
        });
    }
};
