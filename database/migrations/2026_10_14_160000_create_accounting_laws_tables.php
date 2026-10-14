<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_laws', function (Blueprint $table) {
            $table->id();
            $table->string('article_number', 50);
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('introduction')->nullable();
            $table->longText('article_text')->nullable();
            $table->longText('use_cases')->nullable();
            $table->longText('plain_language')->nullable();
            $table->longText('example')->nullable();
            $table->longText('conclusion')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 300)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('law_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_law_id')->constrained('accounting_laws')->cascadeOnDelete();
            $table->string('event_date', 32);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['accounting_law_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_evolutions');
        Schema::dropIfExists('accounting_laws');
    }
};
