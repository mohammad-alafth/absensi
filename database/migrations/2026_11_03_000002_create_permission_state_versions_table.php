<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_state_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('state_key', 80)->unique();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });

        DB::table('permission_state_versions')->insert([
            'state_key' => 'permission',
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_state_versions');
    }
};
