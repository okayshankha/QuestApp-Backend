<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_id')->nullable()->unique();
            $table->string('question');
            $table->string('question_image_url')->nullable();
            $table->string('question_type')
                ->default(config('QuestApp.System.question_types')[config('QuestApp.System.default_question_types_index')]);
            $table->string('options');
            $table->string('points')->default(1);
            $table->boolean('active')->default(true);
            $table->string('created_by_user_id');
            $table->string('modified_by_user_id')->nullable();
            $table->string('deleted_by_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questions');
    }
}
