<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 10, 2);
            $table->unsignedBigInteger('payer');
            $table->unsignedBigInteger('payee');
            $table->string('status', 20);
            $table->timestamps();

            $table->foreign('payer')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('payee')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
