<?php
/**
 * Character Model
 * Handles all character-related operations
 */

class Character {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new character
     */
    public function create($userId, $name, $class) {
        try {
            $this->pdo->beginTransaction();
            
            // Get class configuration
            $classConfig = CHARACTER_CLASSES[$class];
            
            // Create character
            $stmt = $this->pdo->prepare("
                INSERT INTO characters (user_id, name, class, level, current_xp, xp_to_next_level, rank)
                VALUES (:user_id, :name, :class, 1, 0, :xp_next, 'E')
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'class' => $class,
                'xp_next' => BASE_XP_REQUIREMENT
            ]);
            
            $characterId = $this->pdo->lastInsertId();
            
            // Create attributes
            $stmt = $this->pdo->prepare("
                INSERT INTO character_attributes 
                (character_id, strength, intelligence, discipline, energy, spirit)
                VALUES (:char_id, :str, :int, :dis, :ene, :spi)
            ");
            
            $stmt->execute([
                'char_id' => $characterId,
                'str' => $classConfig['strength'],
                'int' => $classConfig['intelligence'],
                'dis' => $classConfig['discipline'],
                'ene' => $classConfig['energy'],
                'spi' => $classConfig['spirit']
            ]);
            
            $this->pdo->commit();
            return $characterId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get character by ID
     */
    public function getById($characterId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, a.strength, a.intelligence, a.discipline, a.energy, a.spirit
            FROM characters c
            LEFT JOIN character_attributes a ON c.id = a.character_id
            WHERE c.id = :id
        ");
        
        $stmt->execute(['id' => $characterId]);
        return $stmt->fetch();
    }
    
    /**
     * Get character by user ID
     */
    public function getByUserId($userId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, a.strength, a.intelligence, a.discipline, a.energy, a.spirit
            FROM characters c
            LEFT JOIN character_attributes a ON c.id = a.character_id
            WHERE c.user_id = :user_id
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Add XP to character
     */
    public function addXP($characterId, $xp) {
        $character = $this->getById($characterId);
        $newXP = $character['current_xp'] + $xp;
        $level = $character['level'];
        $xpNeeded = $character['xp_to_next_level'];
        
        // Check for level up
        while ($newXP >= $xpNeeded) {
            $level++;
            $newXP -= $xpNeeded;
            $xpNeeded = $this->calculateXPForNextLevel($level);
        }
        
        // Update rank based on new level
        $rank = $this->calculateRank($level);
        
        // Update character
        $stmt = $this->pdo->prepare("
            UPDATE characters 
            SET current_xp = :xp, level = :level, xp_to_next_level = :xp_next, rank = :rank, updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'xp' => $newXP,
            'level' => $level,
            'xp_next' => $xpNeeded,
            'rank' => $rank,
            'id' => $characterId
        ]);
        
        // Check for achievements
        $this->checkLevelAchievements($characterId, $level);
        
        return [
            'leveled_up' => $level > $character['level'],
            'new_level' => $level,
            'new_rank' => $rank,
            'xp_gained' => $xp
        ];
    }
    
    /**
     * Update character attributes
     */
    public function updateAttributes($characterId, $attributeChanges) {
        $character = $this->getById($characterId);
        
        $updates = [];
        $params = ['char_id' => $characterId];
        
        foreach ($attributeChanges as $attr => $change) {
            $currentValue = $character[$attr] ?? 0;
            $newValue = max(0, $currentValue + $change); // Don't go below 0
            $updates[] = "$attr = :$attr";
            $params[$attr] = $newValue;
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE character_attributes SET " . implode(', ', $updates) . " WHERE character_id = :char_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Check for attribute achievements
            $this->checkAttributeAchievements($characterId);
        }
    }
    
    /**
     * Calculate XP needed for next level
     */
    private function calculateXPForNextLevel($currentLevel) {
        return BASE_XP_REQUIREMENT + ($currentLevel * XP_PER_LEVEL);
    }
    
    /**
     * Calculate rank based on level
     */
    private function calculateRank($level) {
        foreach (RANKS as $rank => $config) {
            if ($level >= $config['min_level'] && $level <= $config['max_level']) {
                return $rank;
            }
        }
        return 'S';
    }
    
    /**
     * Get character statistics
     */
    public function getStatistics($characterId) {
        // Get total challenges completed
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_completed
            FROM character_challenges
            WHERE character_id = :char_id AND is_completed = 1
        ");
        $stmt->execute(['char_id' => $characterId]);
        $challenges = $stmt->fetch();
        
        // Get current streak
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as streak
            FROM (
                SELECT DISTINCT DATE(answered_at) as answer_date
                FROM daily_answers
                WHERE character_id = :char_id
                AND answered_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY answer_date DESC
            ) as dates
        ");
        $stmt->execute(['char_id' => $characterId]);
        $streak = $stmt->fetch();
        
        // Get achievements count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_achievements
            FROM character_achievements
            WHERE character_id = :char_id
        ");
        $stmt->execute(['char_id' => $characterId]);
        $achievements = $stmt->fetch();
        
        return [
            'challenges_completed' => $challenges['total_completed'],
            'current_streak' => $streak['streak'],
            'achievements_unlocked' => $achievements['total_achievements']
        ];
    }
    
    /**
     * Check and award level achievements
     */
    private function checkLevelAchievements($characterId, $level) {
        $stmt = $this->pdo->prepare("
            SELECT a.id, a.xp_bonus
            FROM achievements a
            LEFT JOIN character_achievements ca ON a.id = ca.achievement_id AND ca.character_id = :char_id
            WHERE a.requirement_type = 'level'
            AND a.requirement_value = :level
            AND ca.id IS NULL
        ");
        
        $stmt->execute([
            'char_id' => $characterId,
            'level' => $level
        ]);
        
        while ($achievement = $stmt->fetch()) {
            $this->unlockAchievement($characterId, $achievement['id']);
            if ($achievement['xp_bonus'] > 0) {
                $this->addXP($characterId, $achievement['xp_bonus']);
            }
        }
    }
    
    /**
     * Check and award attribute achievements
     */
    private function checkAttributeAchievements($characterId) {
        $character = $this->getById($characterId);
        
        $stmt = $this->pdo->prepare("
            SELECT a.id, a.xp_bonus
            FROM achievements a
            LEFT JOIN character_achievements ca ON a.id = ca.achievement_id AND ca.character_id = :char_id
            WHERE a.requirement_type = 'attribute'
            AND ca.id IS NULL
        ");
        
        $stmt->execute(['char_id' => $characterId]);
        
        while ($achievement = $stmt->fetch()) {
            // Check if any attribute meets requirement (simplified)
            $maxAttr = max(
                $character['strength'],
                $character['intelligence'],
                $character['discipline'],
                $character['energy'],
                $character['spirit']
            );
            
            if ($maxAttr >= 50) { // Example threshold
                $this->unlockAchievement($characterId, $achievement['id']);
            }
        }
    }
    
    /**
     * Unlock achievement
     */
    private function unlockAchievement($characterId, $achievementId) {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO character_achievements (character_id, achievement_id)
            VALUES (:char_id, :ach_id)
        ");
        
        $stmt->execute([
            'char_id' => $characterId,
            'ach_id' => $achievementId
        ]);
    }
    
    /**
     * Log daily progress
     */
    public function logProgress($characterId) {
        $character = $this->getById($characterId);
        $stats = $this->getStatistics($characterId);
        
        $attributes = json_encode([
            'strength' => $character['strength'],
            'intelligence' => $character['intelligence'],
            'discipline' => $character['discipline'],
            'energy' => $character['energy'],
            'spirit' => $character['spirit']
        ]);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO progress_history 
            (character_id, log_date, level, total_xp, rank, attributes_snapshot, challenges_completed)
            VALUES (:char_id, CURDATE(), :level, :xp, :rank, :attrs, :challenges)
            ON DUPLICATE KEY UPDATE
            level = :level, total_xp = :xp, rank = :rank, attributes_snapshot = :attrs, challenges_completed = :challenges
        ");
        
        $stmt->execute([
            'char_id' => $characterId,
            'level' => $character['level'],
            'xp' => $character['current_xp'],
            'rank' => $character['rank'],
            'attrs' => $attributes,
            'challenges' => $stats['challenges_completed']
        ]);
    }
}
