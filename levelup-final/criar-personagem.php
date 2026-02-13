<?php
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se já tem personagem (NOME CORRIGIDO)
$stmt = $pdo->prepare("SELECT id FROM characters WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($personagem = $stmt->fetch()) {
    $_SESSION['character_id'] = $personagem['id'];
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $classe = $_POST['classe'] ?? '';
    
    if (empty($nome)) {
        $erro = 'Digite um nome para seu personagem';
    } elseif (!isset(CHARACTER_CLASSES[$classe])) {
        $erro = 'Selecione uma classe válida';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Criar personagem (NOMES CORRIGIDOS)
            $stmt = $pdo->prepare("
                INSERT INTO characters (user_id, char_name, char_class, char_level, current_xp, xp_to_next_level, char_rank)
                VALUES (?, ?, ?, 1, 0, 100, 'E')
            ");
            $stmt->execute([$_SESSION['user_id'], $nome, $classe]);
            $characterId = $pdo->lastInsertId();
            
            // Criar atributos baseados na classe
            $classConfig = CHARACTER_CLASSES[$classe];
            $stmt = $pdo->prepare("
                INSERT INTO character_attributes (character_id, strength, intelligence, discipline, energy, spirit)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $characterId,
                $classConfig['strength'],
                $classConfig['intelligence'],
                $classConfig['discipline'],
                $classConfig['energy'],
                $classConfig['spirit']
            ]);
            
            $pdo->commit();
            
            $_SESSION['character_id'] = $characterId;
            header('Location: dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro ao criar personagem: ' . $e->getMessage();
        }
    }
}

$classIcons = [
    'Guerreiro' => '⚔️',
    'Assassino' => '🗡️',
    'Mago' => '🔮',
    'Estrategista' => '🎯'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Personagem - Level Up Your Life</title>
    <link rel="stylesheet" href="public/css/main.css">
    <style>
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .class-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.1);
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .class-card:hover {
            border-color: #6c5ce7;
            transform: translateY(-4px);
        }
        input[type="radio"]:checked + .class-card {
            border-color: #6c5ce7;
            background: linear-gradient(135deg, rgba(108,92,231,0.2), rgba(0,184,148,0.1));
        }
        .class-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div style="min-height: 100vh; padding: 2rem;">
        <div style="max-width: 800px; margin: 0 auto;">
            
            <div style="text-align: center; margin-bottom: 3rem;">
                <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">⚔️ Criar Personagem</h1>
                <p style="color: var(--text-secondary); font-size: 1.1rem;">
                    Escolha seu nome e classe para iniciar sua jornada
                </p>
            </div>

            <div style="background: var(--bg-card); padding: 3rem; border-radius: 16px; border: 2px solid #6c5ce7;">
                
                <?php if ($erro): ?>
                    <div style="background: #d63031; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                        ⚠️ <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Nome -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 1.1rem;">
                            Nome do Personagem
                        </label>
                        <input type="text" 
                               name="nome" 
                               placeholder="Digite o nome do seu herói"
                               value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                               maxlength="50"
                               required
                               style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; font-size: 1rem;">
                    </div>

                    <!-- Classes -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-size: 1.1rem;">
                            Escolha sua Classe
                        </label>
                        
                        <div class="class-grid">
                            <?php foreach (CHARACTER_CLASSES as $className => $config): ?>
                                <label>
                                    <input type="radio" 
                                           name="classe" 
                                           value="<?= $className ?>" 
                                           required
                                           style="display: none;">
                                    <div class="class-card">
                                        <div class="class-icon"><?= $classIcons[$className] ?></div>
                                        <div style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                                            <?= $className ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?= $config['description'] ?>
                                        </div>
                                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                            <?php foreach ($config as $attr => $value): ?>
                                                <?php if (is_numeric($value) && $value > 10): ?>
                                                    <span style="background: #6c5ce7; padding: 0.25rem 0.5rem; border-radius: 8px; font-size: 0.75rem;">
                                                        <?= ATTRIBUTE_ICONS[$attr] ?? '' ?> +<?= $value - 10 ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" 
                            style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #6c5ce7, #5f4dd1); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1.1rem; cursor: pointer;">
                        🚀 Criar Personagem e Começar
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
