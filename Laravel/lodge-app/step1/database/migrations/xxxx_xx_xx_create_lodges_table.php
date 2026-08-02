<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLodgesTable extends Migration
{
    public function up()
    {
        Schema::create('lodges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->integer('founded_year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lodges');
    }
}
