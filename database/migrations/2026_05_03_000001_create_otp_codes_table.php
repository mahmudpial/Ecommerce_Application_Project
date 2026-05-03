<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('purpose');
            $table->string('identifier');
            $table->json('payload')->nullable();
            $table->string('otp_hash');
            $table->string('verification_token')->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['purpose', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
