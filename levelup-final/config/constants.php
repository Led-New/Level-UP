<?php
/**
 * System Constants
 * Level Up Your Life - RPG System
 */

// Session Configuration
define('SESSION_NAME', 'levelup_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// XP and Progression
define('BASE_XP_REQUIREMENT', 100);
define('XP_PER_LEVEL', 50);

// Ranks
define('RANKS', [
    'E' => ['min_level' => 1, 'max_level' => 4, 'color' => '#7f8c8d'],
    'D' => ['min_level' => 5, 'max_level' => 9, 'color' => '#95a5a6'],
    'C' => ['min_level' => 10, 'max_level' => 14, 'color' => '#3498db'],
    'B' => ['min_level' => 15, 'max_level' => 19, 'color' => '#9b59b6'],
    'A' => ['min_level' => 20, 'max_level' => 29, 'color' => '#e74c3c'],
    'S' => ['min_level' => 30, 'max_level' => 999, 'color' => '#f1c40f'],
]);

// Character Classes
define('CHARACTER_CLASSES', [
    'Guerreiro' => [
        'strength' => 12,
        'intelligence' => 8,
        'discipline' => 10,
        'energy' => 10,
        'spirit' => 10,
        'bonus_attribute' => 'strength',
        'description' => 'Foco em força física e resistência'
    ],
    'Assassino' => [
        'strength' => 10,
        'intelligence' => 10,
        'discipline' => 12,
        'energy' => 10,
        'spirit' => 8,
        'bonus_attribute' => 'discipline',
        'description' => 'Mestre em foco e execução de objetivos'
    ],
    'Mago' => [
        'strength' => 8,
        'intelligence' => 12,
        'discipline' => 10,
        'energy' => 10,
        'spirit' => 10,
        'bonus_attribute' => 'intelligence',
        'description' => 'Especialista em conhecimento e aprendizado'
    ],
    'Estrategista' => [
        'strength' => 11,
        'intelligence' => 11,
        'discipline' => 11,
        'energy' => 11,
        'spirit' => 11,
        'bonus_attribute' => 'all',
        'description' => 'Equilíbrio perfeito entre todos os atributos'
    ],
]);

// Base Attributes
define('BASE_ATTRIBUTES', [
    'strength' => 10,
    'intelligence' => 10,
    'discipline' => 10,
    'energy' => 10,
    'spirit' => 10,
]);

// Attribute Icons (using emojis for simplicity)
define('ATTRIBUTE_ICONS', [
    'strength' => '💪',
    'intelligence' => '🧠',
    'discipline' => '🎯',
    'energy' => '⚡',
    'spirit' => '✨',
]);

// App Configuration
define('APP_NAME', 'Level Up Your Life');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/Sao_Paulo');

// Set timezone
date_default_timezone_set(TIMEZONE);
