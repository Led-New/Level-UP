<?php
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

// Verificar login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['character_id'])) {
    header('Location: login.php');
    exit;
}

$erro = '';
$sucesso = '';

// Verificar se já respondeu hoje
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM daily_answers
    WHERE character_id = ? AND answered_at = CURDATE()
");
$stmt->execute([$_SESSION['character_id']]);
$jaRespondeu = $stmt->fetch()['count'] > 0;

// Processar respostas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$jaRespondeu) {
    try {
        $pdo->beginTransaction();
        
        $totalXP = 0;
        $attrChanges = [
            'strength' => 0,
            'intelligence' => 0,
            'discipline' => 0,
            'energy' => 0,
            'spirit' => 0
        ];
        
        // Processar cada resposta
        foreach ($_POST as $questionId => $answer) {
            if (strpos($questionId, 'q_') !== 0) continue;
            
            $qId = str_replace('q_', '', $questionId);
            
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$qId]);
            $question = $stmt->fetch();
            
            if (!$question) continue;
            
            $xp = 10;
            
            switch ($question['category']) {
                case 'sleep':
                    if (strpos($question['question_text'], 'Quantas horas') !== false) {
                        $hours = floatval($answer);
                        if ($hours >= 7 && $hours <= 8) {
                            $attrChanges['energy'] += 3;
                            $xp = 30;
                        } elseif ($hours >= 6) {
                            $attrChanges['energy'] += 1;
                            $xp = 20;
                        } else {
                            $attrChanges['energy'] -= 2;
                        }
                    } elseif ($answer === 'sim') {
                        $attrChanges['energy'] -= 1;
                    }
                    break;
                    
                case 'exercise':
                    if (strpos($question['question_text'], 'treinou') !== false) {
                        if ($answer === 'sim') {
                            $attrChanges['strength'] += 3;
                            $attrChanges['discipline'] += 1;
                            $xp = 50;
                        }
                    } elseif (strpos($question['question_text'], 'minutos') !== false) {
                        $minutes = intval($answer);
                        if ($minutes >= 20) {
                            $attrChanges['strength'] += 3;
                            $xp = 50;
                        } elseif ($minutes > 0) {
                            $attrChanges['strength'] += 1;
                            $xp = 20;
                        }
                    }
                    break;
                    
                case 'study':
                    if (strpos($question['question_text'], 'estudou') !== false) {
                        if ($answer === 'sim') {
                            $attrChanges['intelligence'] += 3;
                            $attrChanges['discipline'] += 1;
                            $xp = 50;
                        }
                    } elseif (strpos($question['question_text'], 'minutos') !== false) {
                        $minutes = intval($answer);
                        if ($minutes >= 30) {
                            $attrChanges['intelligence'] += 3;
                            $xp = 50;
                        } elseif ($minutes > 0) {
                            $attrChanges['intelligence'] += 1;
                            $xp = 20;
                        }
                    }
                    break;
                    
                case 'productivity':
                    if ($answer === 'sim') {
                        $attrChanges['discipline'] += 2;
                        $attrChanges['spirit'] += 1;
                        $xp = 100;
                    }
                    break;
                    
                case 'health':
                    if ($answer === 'sim') {
                        $attrChanges['spirit'] += 3;
                        $attrChanges['energy'] += 1;
                        $xp = 75;
                    }
                    break;
            }
            
            $totalXP += $xp;
            
            $stmt = $pdo->prepare("
                INSERT INTO daily_answers (character_id, question_id, answer_value, xp_gained, attribute_changes, answered_at)
                VALUES (?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([
                $_SESSION['character_id'],
                $qId,
                $answer,
                $xp,
                json_encode($attrChanges)
            ]);
        }
        
        // Atualizar XP do personagem (NOMES CORRIGIDOS)
        $stmt = $pdo->prepare("
            SELECT current_xp, xp_to_next_level, char_level 
            FROM characters 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['character_id']]);
        $char = $stmt->fetch();
        
        $newXP = $char['current_xp'] + $totalXP;
        $newLevel = $char['char_level'];
        
        while ($newXP >= $char['xp_to_next_level']) {
            $newLevel++;
            $newXP -= $char['xp_to_next_level'];
            $char['xp_to_next_level'] = 100 + ($newLevel * 50);
        }
        
        $stmt = $pdo->prepare("
            UPDATE characters 
            SET current_xp = ?, char_level = ?, xp_to_next_level = ?
            WHERE id = ?
        ");
        $stmt->execute([$newXP, $newLevel, $char['xp_to_next_level'], $_SESSION['character_id']]);
        
        // Atualizar atributos
        foreach ($attrChanges as $attr => $change) {
            if ($change != 0) {
                $stmt = $pdo->prepare("
                    UPDATE character_attributes 
                    SET $attr = GREATEST(0, $attr + ?)
                    WHERE character_id = ?
                ");
                $stmt->execute([$change, $_SESSION['character_id']]);
            }
        }
        
        $pdo->commit();
        $sucesso = "Respostas enviadas! Você ganhou $totalXP XP!";
        $jaRespondeu = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = 'Erro ao processar respostas: ' . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM questions WHERE is_active = 1 ORDER BY id");
$perguntas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas Diárias - Level Up Your Life</title>
    <link rel="stylesheet" href="public/css/main.css">
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; padding: 2rem;">
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1>📝 Perguntas Diárias</h1>
            <p style="color: var(--text-secondary);">Como foi seu dia?</p>
        </div>

        <?php if ($erro): ?>
            <div style="background: #d63031; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                ⚠️ <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div style="background: #00b894; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                ✅ <?= htmlspecialchars($sucesso) ?>
            </div>
        <?php endif; ?>

        <?php if ($jaRespondeu): ?>
            <div class="card" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                <h2>Você já respondeu hoje!</h2>
                <p style="color: var(--text-secondary); margin: 1rem 0;">
                    Volte amanhã para continuar sua jornada.
                </p>
                <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem; text-decoration: none; display: inline-block;">
                    ← Voltar ao Dashboard
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <?php foreach ($perguntas as $i => $pergunta): ?>
                    <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #6c5ce7;">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <span style="display: inline-block; background: #6c5ce7; color: white; width: 32px; height: 32px; border-radius: 50%; text-align: center; line-height: 32px; font-weight: 700; margin-right: 1rem;">
                                <?= $i + 1 ?>
                            </span>
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; background: #3498db; color: white;">
                                <?= ucfirst($pergunta['category']) ?>
                            </span>
                        </div>
                        
                        <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">
                            <?= htmlspecialchars($pergunta['question_text']) ?>
                        </div>
                        
                        <?php if ($pergunta['question_type'] === 'boolean'): ?>
                            <div style="display: flex; gap: 1rem;">
                                <label style="flex: 1;">
                                    <input type="radio" name="q_<?= $pergunta['id'] ?>" value="sim" required style="display: none;">
                                    <div style="padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer;">
                                        ✅ Sim
                                    </div>
                                </label>
                                <label style="flex: 1;">
                                    <input type="radio" name="q_<?= $pergunta['id'] ?>" value="nao" style="display: none;">
                                    <div style="padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer;">
                                        ❌ Não
                                    </div>
                                </label>
                            </div>
                        <?php else: ?>
                            <input type="number" 
                                   name="q_<?= $pergunta['id'] ?>" 
                                   min="0" 
                                   max="24" 
                                   step="0.5"
                                   placeholder="Digite um número"
                                   required
                                   style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; text-align: center; font-size: 1.1rem; font-weight: 600;">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #6c5ce7, #00b894); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1.1rem; cursor: pointer; margin-top: 1rem;">
                    🚀 Enviar Respostas
                </button>
            </form>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="dashboard.php" style="color: #6c5ce7; text-decoration: none;">← Voltar ao Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const parent = this.closest('div[style*="display: flex"]');
                parent.querySelectorAll('div[style*="padding"]').forEach(div => {
                    div.style.borderColor = 'rgba(255,255,255,0.1)';
                    div.style.background = 'var(--bg-secondary)';
                });
                this.nextElementSibling.style.borderColor = '#6c5ce7';
                this.nextElementSibling.style.background = 'rgba(108,92,231,0.2)';
            });
        });
    </script>
</body>
</html>
