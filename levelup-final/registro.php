<?php
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';

$erro = '';
$sucesso = '';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    
    if (empty($email) || empty($senha) || empty($confirmar)) {
        $erro = 'Preencha todos os campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não coincidem';
    } else {
        try {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erro = 'Este email já está cadastrado';
            } else {
                // Criar usuário
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
                $stmt->execute([$email, $senhaHash]);
                
                $userId = $pdo->lastInsertId();
                
                // Fazer login automático
                $_SESSION['user_id'] = $userId;
                
                header('Location: criar-personagem.php');
                exit;
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao criar conta: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Level Up Your Life</title>
    <link rel="stylesheet" href="public/css/main.css">
</head>
<body>
    <div class="auth-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div class="auth-box" style="max-width: 450px; width: 100%; background: var(--bg-card); padding: 3rem; border-radius: 16px;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚔️ Criar Conta</h1>
                <p style="color: var(--text-secondary);">Comece sua jornada épica</p>
            </div>

            <?php if ($erro): ?>
                <div style="background: #d63031; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    ⚠️ <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Email</label>
                    <input type="email" 
                           name="email" 
                           class="form-input"
                           placeholder="seu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required
                           style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Senha (mínimo 6 caracteres)</label>
                    <input type="password" 
                           name="senha" 
                           class="form-input"
                           placeholder="••••••••"
                           required
                           style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Confirmar Senha</label>
                    <input type="password" 
                           name="confirmar" 
                           class="form-input"
                           placeholder="••••••••"
                           required
                           style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                </div>

                <button type="submit" 
                        class="btn btn-primary"
                        style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #6c5ce7, #5f4dd1); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    🎮 Criar Conta
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; color: var(--text-secondary);">
                Já tem uma conta? 
                <a href="login.php" style="color: #6c5ce7; text-decoration: none; font-weight: 600;">Faça login</a>
            </div>
        </div>
    </div>
</body>
</html>
