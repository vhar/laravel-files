<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fileables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('file_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->morphs('fileable');

            $table->string('collection')->default('default');
            $table->unsignedInteger('sort')->default(0);

            $table->timestamps();

            $table->unique([
                'file_id',
                'fileable_type',
                'fileable_id',
                'collection',
            ], 'fileables_unique');

            $table->index([
                'fileable_type',
                'fileable_id',
                'collection',
                'sort',
            ], 'fileables_entity_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fileables');
    }
};