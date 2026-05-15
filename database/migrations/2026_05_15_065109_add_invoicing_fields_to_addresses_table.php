<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('vat_number')->nullable()->after('phone');
            $table->string('fiscal_code')->nullable()->after('vat_number');
            $table->string('sdi_code')->nullable()->after('fiscal_code');
            $table->string('pec_email')->nullable()->after('sdi_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'fiscal_code', 'sdi_code', 'pec_email']);
        });
    }
};
