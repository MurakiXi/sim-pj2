<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStampCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('attendance_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('status')->default('awaiting_approval');

            $table->timestamp('requested_clock_in_at')->nullable();
            $table->timestamp('requested_clock_out_at')->nullable();
            $table->string('requested_note')->default('');


            $table->json('requested_breaks')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('admins')
                ->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
}
