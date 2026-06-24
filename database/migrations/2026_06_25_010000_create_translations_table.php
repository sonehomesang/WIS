<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            // 'replace' = global find→target over rendered HTML (middleware)
            // 'term'    = key→value override read by @term() / Translation::term()
            $table->enum('type', ['replace', 'term'])->default('replace');
            $table->string('group', 64)->default('custom');
            $table->string('source', 512);            // wrong text  OR  term key
            $table->text('target')->nullable();       // correct text OR term value
            $table->string('note', 256)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->unique(['type', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
