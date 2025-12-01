<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->nullOnDelete();
            $table->text('question');
            $table->string('question_type')->default('multiple_choice_single');
            $table->float('points')->default(1);
            $table->text('option_1')->nullable();
            $table->text('option_2')->nullable();
            $table->text('option_3')->nullable();
            $table->text('option_4')->nullable();
            $table->text('option_5')->nullable();
            $table->text('answer')->nullable();
            $table->json('correct_answers')->nullable();
            $table->json('matching_pairs')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
