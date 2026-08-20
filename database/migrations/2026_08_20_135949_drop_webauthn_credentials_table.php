<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Package laragear/webauthn dihapus, ganti ke laravel/passkeys (tabel: passkeys).
        Schema::dropIfExists('webauthn_credentials');
    }

    public function down(): void
    {
        // Rollback tidak dibuat: rekonstruksi tabel butuh package abandoned.
    }
};
