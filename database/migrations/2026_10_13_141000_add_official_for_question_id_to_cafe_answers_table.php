<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('official_for_question_id')->nullable()->after('is_official');
            $table->unique('official_for_question_id');
        });

        DB::table('cafe_answers')
            ->where('is_official', true)
            ->update([
                'official_for_question_id' => DB::raw('cafe_question_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cafe_answers', function (Blueprint $table) {
            $table->dropUnique(['official_for_question_id']);
            $table->dropColumn('official_for_question_id');
        });
    }
};
