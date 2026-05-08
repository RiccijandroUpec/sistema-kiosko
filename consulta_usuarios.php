<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::all();

echo "Usuarios en el sistema:\n";
foreach ($users as $user) {
    echo "- Nombre: " . $user->name . 
         ", Email: " . $user->email . 
         ", Rol: " . $user->role . 
         ", Verificado: " . ($user->email_verified_at ? 'Sí' : 'No') . "\n";
}