<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->date('work_date');
            $table->timestamp('clock_in_at');
            $table->timestamp('clock_out_at')->nullable();
            $table->string('note')->default('');
            $table->unique(['user_id', 'work_date']);
            $table->index(['user_id', 'clock_out_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
