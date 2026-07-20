<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('business_shift_joker_assignments');
    }

    public function down(): void
    {
        // Intentionally empty: joker assignments feature has been removed.
    }
};
