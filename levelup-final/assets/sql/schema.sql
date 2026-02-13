-- ============================================
-- DATABASE SCHEMA - LEVEL UP YOUR LIFE
-- Sistema RPG de Gamificação de Vida Real
-- ============================================

CREATE DATABASE IF NOT EXISTS levelup_life CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE levelup_life;

-- ============================================
-- TABELA: users
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: characters
-- ============================================
CREATE TABLE characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    class ENUM('Guerreiro', 'Assassino', 'Mago', 'Estrategista') NOT NULL,
    level INT DEFAULT 1,
    current_xp INT DEFAULT 0,
    xp_to_next_level INT DEFAULT 100,
    rank ENUM('E', 'D', 'C', 'B', 'A', 'S') DEFAULT 'E',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: character_attributes
-- ============================================
CREATE TABLE character_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    strength INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    discipline INT DEFAULT 10,
    energy INT DEFAULT 10,
    spirit INT DEFAULT 10,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    INDEX idx_character (character_id)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: questions
-- ============================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    question_type ENUM('numeric', 'boolean', 'choice', 'time') NOT NULL,
    category ENUM('sleep', 'exercise', 'study', 'productivity', 'health') NOT NULL,
    impact_attribute VARCHAR(50),
    impact_value INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABELA: daily_answers
-- ============================================
CREATE TABLE daily_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_value VARCHAR(255) NOT NULL,
    xp_gained INT DEFAULT 0,
    attribute_changes JSON,
    answered_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_answer (character_id, question_id, answered_at),
    INDEX idx_character_date (character_id, answered_at)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: challenges
-- ============================================
CREATE TABLE challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    challenge_type ENUM('daily', 'weekly', 'special') DEFAULT 'daily',
    xp_reward INT DEFAULT 50,
    attribute_reward JSON,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABELA: character_challenges
-- ============================================
CREATE TABLE character_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    challenge_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    completed_date DATE NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    xp_earned INT DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    INDEX idx_character_assigned (character_id, assigned_date)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: achievements
-- ============================================
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    requirement_type ENUM('level', 'streak', 'attribute', 'challenge_count') NOT NULL,
    requirement_value INT NOT NULL,
    xp_bonus INT DEFAULT 0,
    is_hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABELA: character_achievements
-- ============================================
CREATE TABLE character_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_achievement (character_id, achievement_id)
) ENGINE=InnoDB;

-- ============================================
-- TABELA: progress_history
-- ============================================
CREATE TABLE progress_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    log_date DATE NOT NULL,
    level INT NOT NULL,
    total_xp INT NOT NULL,
    rank VARCHAR(10),
    attributes_snapshot JSON,
    challenges_completed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_log (character_id, log_date),
    INDEX idx_character_date (character_id, log_date)
) ENGINE=InnoDB;

-- ============================================
-- DADOS INICIAIS: questions
-- ============================================
INSERT INTO questions (question_text, question_type, category, impact_attribute, impact_value) VALUES
('Quantas horas você dormiu hoje?', 'numeric', 'sleep', 'energy', 2),
('Você dormiu depois das 23h?', 'boolean', 'sleep', 'energy', -1),
('Se dormiu tarde, foi por trabalho ou estudos?', 'boolean', 'sleep', 'discipline', 1),
('Você treinou hoje?', 'boolean', 'exercise', 'strength', 3),
('Quantos minutos de exercício físico?', 'numeric', 'exercise', 'strength', 1),
('Você estudou hoje?', 'boolean', 'study', 'intelligence', 3),
('Quantos minutos de estudo?', 'numeric', 'study', 'intelligence', 1),
('Completou seus objetivos do dia?', 'boolean', 'productivity', 'discipline', 2),
('Como você avalia seu foco hoje? (1-10)', 'numeric', 'productivity', 'discipline', 1),
('Você meditou ou praticou mindfulness?', 'boolean', 'health', 'spirit', 3);

-- ============================================
-- DADOS INICIAIS: challenges
-- ============================================
INSERT INTO challenges (title, description, xp_reward, attribute_reward, difficulty) VALUES
('Sono Regular', 'Dormir antes das 23h', 50, '{"energy": 2}', 'easy'),
('Treino Diário', 'Treinar por pelo menos 20 minutos', 100, '{"strength": 3, "discipline": 1}', 'medium'),
('Sessão de Estudos', 'Estudar por 30 minutos ou mais', 100, '{"intelligence": 3, "discipline": 1}', 'medium'),
('Dia Completo', 'Completar todos os objetivos do dia', 200, '{"discipline": 5, "spirit": 2}', 'hard'),
('Meditação Matinal', 'Meditar por 10 minutos pela manhã', 75, '{"spirit": 3, "energy": 1}', 'easy');

-- ============================================
-- DADOS INICIAIS: achievements
-- ============================================
INSERT INTO achievements (name, description, requirement_type, requirement_value, xp_bonus) VALUES
('Primeiro Passo', 'Alcance o nível 2', 'level', 2, 50),
('Ascensão', 'Alcance o nível 5', 'level', 5, 150),
('Veterano', 'Alcance o nível 10', 'level', 10, 500),
('Sequência de Fogo', 'Complete desafios por 7 dias seguidos', 'streak', 7, 300),
('Imparável', 'Complete desafios por 30 dias seguidos', 'streak', 30, 1000),
('Mente Afiada', 'Alcance 50 de Inteligência', 'attribute', 50, 200),
('Corpo de Aço', 'Alcance 50 de Força', 'attribute', 50, 200),
('Mestre da Disciplina', 'Alcance 50 de Disciplina', 'attribute', 50, 200);
