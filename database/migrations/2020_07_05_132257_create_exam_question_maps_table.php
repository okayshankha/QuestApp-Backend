<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamQuestionMapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_question_maps', function (Blueprint $table) {
            $table->id();
            $table->string('exam_question_map_id')->nullable()->unique();
            $table->string('question_id');
            $table->string('examination_id');
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
        Schema::dropIfExists('exam_question_maps');
    }
}
