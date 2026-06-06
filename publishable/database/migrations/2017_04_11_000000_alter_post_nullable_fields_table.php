<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use TCG\Voyager\Database\Schema\SchemaManager;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(): void
    {
        $platform = SchemaManager::getDatabaseConnection()
                                 ->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('posts', function (Blueprint $table) {
            $table->text('excerpt')
                  ->nullable()
                  ->change();
            $table->text('meta_description')
                  ->nullable()
                  ->change();
            $table->text('meta_keywords')
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->text('excerpt')
                  ->change();
            $table->text('meta_description')
                  ->change();
            $table->text('meta_keywords')
                  ->change();
        });
    }
};
