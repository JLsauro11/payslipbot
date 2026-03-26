<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop current foreign keys (or drop constraints if needed)
            $table->dropForeign(['position_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['area_id']);

            // Re‑add with nullOnDelete
            $table->foreign('position_id')
                ->references('id')->on('positions')
                ->nullOnDelete();

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->nullOnDelete();

            $table->foreign('area_id')
                ->references('id')->on('areas')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['area_id']);

            $table->foreign('position_id')
                ->references('id')->on('positions')
                ->cascadeOnDelete();

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->cascadeOnDelete();

            $table->foreign('area_id')
                ->references('id')->on('areas')
                ->cascadeOnDelete();
        });
    }
};
