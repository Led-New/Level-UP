<?php
// Iniciar sessão
session_start();

// Incluir arquivos necessários
require_once 'config/database.php';
require_once 'config/constants.php';

// Variável para mensagens de erro
$erro = '';

// Se já estiver logado, redirecionar
if (isset($_SESSION['user_id']) && isset($_SESSION['character_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        try {
            // Buscar usuário
            $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['password_hash'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $usuario['id'];
                
                // Atualizar último login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                // Verificar se tem personagem
                $stmt = $pdo->prepare("SELECT id FROM characters WHERE user_id = ?");
                $stmt->execute([$usuario['id']]);
                $personagem = $stmt->fetch();
                
                if ($personagem) {
                    $_SESSION['character_id'] = $personagem['id'];
                    header('Location: dashboard.php');
                } else {
                    header('Location: criar-personagem.php');
                }
                exit;
            } else {
                $erro = 'Email ou senha incorretos';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao fazer login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Level Up Your Life</title>
    <link rel="stylesheet" href="public/css/main.css">
</head>
<body>
    <div class="auth-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div class="auth-box" style="max-width: 450px; width: 100%; background: var(--bg-card); padding: 3rem; border-radius: 16px;">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚔️ Level Up</h1>
                <p style="color: var(--text-secondary);">Transforme sua vida em uma jornada épica</p>
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
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Senha</label>
                    <input type="password" 
                           name="senha" 
                           class="form-input"
                           placeholder="••••••••"
                           required
                           style="width: 100%; padding: 0.75rem; background: var(--bg-secondary); border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                </div>

                <button type="submit" 
                        class="btn btn-primary"
                        style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #6c5ce7, #5f4dd1); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    🚀 Entrar
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; color: var(--text-secondary);">
                Não tem uma conta? 
                <a href="registro.php" style="color: #6c5ce7; text-decoration: none; font-weight: 600;">Registre-se aqui</a>
            </div>
        </div>
    </div>
</body>
</html>
