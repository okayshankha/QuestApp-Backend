<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * 'answers' will the have the following format,
         * [
         *      {
         *          question_id: <id>, answer: <string> // Long text type answer
         *      },
         *      {
         *          question_id: <id>, answer: {index: <index>} // MCQ
         *      },
         *      {
         *          question_id: <id>, answer: {index: [<index>, <index>]} // Multiple Choice
         *      },
         * ]
         */
        Schema::create('assessment_records', function (Blueprint $table) {
            $table->id();
            $table->string('examination_id');
            $table->string('user_id');
            $table->longText('answers');
            $table->longText('score');
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
        Schema::dropIfExists('assessment_records');
    }
}
