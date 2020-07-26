<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntityUserMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entity_user_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_user_mapping_id')->nullable();
            $table->string('user_id');

            // Entity can be a space or class
            $table->string('entity_id');

            // Type, to define where the user has been invited, Space or Class
            $table->string('type')->default('class');

            $table->string('activation_token')->nullable();
            $table->boolean('active')->default(false);
            $table->string('created_by_user_id');
            $table->string('modified_by_user_id')->nullable();
            $table->string('deleted_by_user_id')->nullable();
            $table->string('joined_at')->nullable();
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
        Schema::dropIfExists('entity_user_mappings');
    }
}
