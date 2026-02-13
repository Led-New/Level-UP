<?php
/**
 * User Model
 * Handles user authentication and management
 */

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new user
     */
    public function register($email, $password) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Validate password strength
        if (strlen($password) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres');
        }
        
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password_hash, created_at)
            VALUES (:email, :password, NOW())
        ");
        
        $stmt->execute([
            'email' => $email,
            'password' => $passwordHash
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("
            SELECT id, email, password_hash
            FROM users
            WHERE email = :email AND is_active = 1
        ");
        
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Email ou senha incorretos');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Email ou senha incorretos');
        }
        
        // Update last login
        $stmt = $this->pdo->prepare("
            UPDATE users SET last_login = NOW() WHERE id = :id
        ");
        $stmt->execute(['id' => $user['id']]);
        
        return $user;
    }
    
    /**
     * Get user by ID
     */
    public function getById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, email, created_at, last_login
            FROM users
            WHERE id = :id AND is_active = 1
        ");
        
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Check if user has character
     */
    public function hasCharacter($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM characters WHERE user_id = :user_id
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Update email
     */
    public function updateEmail($userId, $newEmail) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Check if email already exists
        $stmt = $this->pdo->prepare("
            SELECT id FROM users WHERE email = :email AND id != :user_id
        ");
        $stmt->execute([
            'email' => $newEmail,
            'user_id' => $userId
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception('Este email já está em uso');
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE users SET email = :email WHERE id = :id
        ");
        
        return $stmt->execute([
            'email' => $newEmail,
            'id' => $userId
        ]);
    }
    
    /**
     * Update password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        // Get current password hash
        $stmt = $this->pdo->prepare("
            SELECT password_hash FROM users WHERE id = :id
        ");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new Exception('Senha atual incorreta');
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            throw new Exception('A nova senha deve ter pelo menos 6 caracteres');
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update
        $stmt = $this->pdo->prepare("
            UPDATE users SET password_hash = :password WHERE id = :id
        ");
        
        return $stmt->execute([
            'password' => $newPasswordHash,
            'id' => $userId
        ]);
    }
    
    /**
     * Deactivate account
     */
    public function deactivate($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET is_active = 0 WHERE id = :id
        ");
        
        return $stmt->execute(['id' => $userId]);
    }
}
