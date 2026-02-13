<?php
/**
 * Session Manager
 * Handles secure session management
 */

class Session {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
            
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function delete($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        self::start();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return self::has('user_id') && self::has('character_id');
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public static function getUserId() {
        return self::get('user_id');
    }
    
    public static function getCharacterId() {
        return self::get('character_id');
    }
    
    public static function setFlash($type, $message) {
        self::set('flash', [
            'type' => $type,
            'message' => $message
        ]);
    }
    
    public static function getFlash() {
        $flash = self::get('flash');
        self::delete('flash');
        return $flash;
    }
}
