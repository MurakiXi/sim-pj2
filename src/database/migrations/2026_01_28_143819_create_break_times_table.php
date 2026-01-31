<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('attendance_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamp('break_in_at');
            $table->timestamp('break_out_at')->nullable();
            $table->index(['attendance_id', 'break_out_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_times');
    }
}
