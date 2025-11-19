<?php
$senha = 'AnalistaTI.2025';
$hash  = '$2y$10$wH4Plb8RA9CwIY8oZcF7WOUaA2PZg8vnGuQ8lJZBcnmU/cCHaV3hK';

if (password_verify($senha, $hash)) {
    echo "✅ Hash confere!";
} else {
    echo "❌ Hash não corresponde!";
}
