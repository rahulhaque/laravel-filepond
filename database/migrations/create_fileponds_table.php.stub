<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fileponds', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('filepath');
            $table->string('extension', 100);
            $table->string('mimetype', 100);
            $table->json('metadata')->nullable();
            $table->string('disk', 100);
            $table->text('upload_id')->nullable();
            $table->json('upload_tags')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('deleted_at')->nullable()->index();
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
        Schema::dropIfExists('fileponds');
    }
};
