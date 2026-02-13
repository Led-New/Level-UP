<?php
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

// Verificar login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header('Location: login.php');
    exit;
}

// Buscar dados do personagem (NOMES CORRIGIDOS)
$stmt = $pdo->prepare("
    SELECT c.*, a.strength, a.intelligence, a.discipline, a.energy, a.spirit
    FROM characters c
    LEFT JOIN character_attributes a ON c.id = a.character_id
    WHERE c.id = ?
");
$stmt->execute([$_SESSION['character_id']]);
$char = $stmt->fetch();

if (!$char) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Calcular XP percentual
$xpPercent = ($char['current_xp'] / $char['xp_to_next_level']) * 100;

// Buscar estatísticas
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM character_challenges
    WHERE character_id = ? AND is_completed = 1
");
$stmt->execute([$_SESSION['character_id']]);
$stats = $stmt->fetch();

// Buscar desafios de hoje (NOMES CORRIGIDOS)
$stmt = $pdo->prepare("
    SELECT cc.*, c.challenge_title, c.challenge_description, c.xp_reward, c.difficulty
    FROM character_challenges cc
    JOIN challenges c ON cc.challenge_id = c.id
    WHERE cc.character_id = ? AND cc.assigned_date = CURDATE()
    ORDER BY c.difficulty ASC
");
$stmt->execute([$_SESSION['character_id']]);
$desafios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Level Up Your Life</title>
    <link rel="stylesheet" href="public/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">⚔️ Level Up Your Life</h1>
            <p style="color: var(--text-secondary);">Transforme sua vida em uma jornada épica</p>
        </div>

        <!-- Character Card -->
        <div class="card" style="background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%); border: 2px solid #6c5ce7; box-shadow: 0 0 20px rgba(108,92,231,0.4); margin-bottom: 2rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div>
                    <div style="font-size: 1.75rem; font-weight: 800; background: linear-gradient(135deg, #6c5ce7, #00b894); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <?= htmlspecialchars($char['char_name']) ?>
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">
                        <?= htmlspecialchars($char['char_class']) ?>
                    </div>
                </div>
                <div>
                    <span style="display: inline-block; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 800; font-size: 1.25rem; background: #6c5ce7;">
                        RANK <?= $char['char_rank'] ?>
                    </span>
                </div>
            </div>

            <!-- XP Bar -->
            <div style="margin: 1.5rem 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                    <span style="background: #6c5ce7; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 700;">
                        Nível <?= $char['char_level'] ?>
                    </span>
                    <span><?= $char['current_xp'] ?> / <?= $char['xp_to_next_level'] ?> XP</span>
                </div>
                <div style="width: 100%; height: 24px; background: var(--bg-secondary); border-radius: 12px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);">
                    <div style="height: 100%; background: linear-gradient(90deg, #6c5ce7, #00b894); border-radius: 12px; width: <?= $xpPercent ?>%; transition: width 0.5s;">
                        <span style="display: block; text-align: center; line-height: 24px; font-size: 0.85rem; font-weight: 700; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.8);">
                            <?= round($xpPercent) ?>%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attributes -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                <?php
                $attrs = [
                    'strength' => ['Força', '💪', '#e74c3c'],
                    'intelligence' => ['Inteligência', '🧠', '#3498db'],
                    'discipline' => ['Disciplina', '🎯', '#9b59b6'],
                    'energy' => ['Energia', '⚡', '#f1c40f'],
                    'spirit' => ['Espírito', '✨', '#1abc9c']
                ];
                foreach ($attrs as $key => $data):
                ?>
                    <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 12px; border-left: 3px solid <?= $data[2] ?>; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;"><?= $data[1] ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.25rem;">
                            <?= $data[0] ?>
                        </div>
                        <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary);">
                            <?= $char[$key] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Estatísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            
            <!-- Gráfico -->
            <div class="card">
                <h3 style="margin-bottom: 1rem;">📊 Perfil de Atributos</h3>
                <canvas id="radarChart" width="400" height="400"></canvas>
            </div>

            <!-- Stats -->
            <div class="card">
                <h3 style="margin-bottom: 1rem;">🏆 Estatísticas</h3>
                <div style="display: grid; gap: 1rem;">
                    <div style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 12px; text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #6c5ce7, #00b894); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            <?= $stats['total'] ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase;">
                            Desafios Completos
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="perguntas-diarias.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; text-decoration: none;">
                📝 Responder Perguntas Diárias
            </a>
            <a href="logout.php" class="btn" style="padding: 1rem 2rem; background: transparent; border: 2px solid #6c5ce7; color: #6c5ce7; text-decoration: none;">
                🚪 Sair
            </a>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Força', 'Inteligência', 'Disciplina', 'Energia', 'Espírito'],
                datasets: [{
                    label: 'Atributos',
                    data: [<?= $char['strength'] ?>, <?= $char['intelligence'] ?>, <?= $char['discipline'] ?>, <?= $char['energy'] ?>, <?= $char['spirit'] ?>],
                    backgroundColor: 'rgba(108, 92, 231, 0.2)',
                    borderColor: 'rgba(108, 92, 231, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: '#a0aec0' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        pointLabels: { color: '#e8ecf1', font: { size: 14, weight: '600' } }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
