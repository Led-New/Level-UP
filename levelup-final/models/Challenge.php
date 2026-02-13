<?php
/**
 * Challenge Model
 * Handles daily challenges and completion
 */

class Challenge {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Assign daily challenges to character
     */
    public function assignDailyChallenges($characterId) {
        // Check if already assigned today
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM character_challenges
            WHERE character_id = :char_id AND assigned_date = CURDATE()
        ");
        $stmt->execute(['char_id' => $characterId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return false; // Already assigned
        }
        
        // Get active challenges (limit to 3 per day)
        $stmt = $this->pdo->prepare("
            SELECT id, xp_reward FROM challenges
            WHERE is_active = 1 AND challenge_type = 'daily'
            ORDER BY RAND()
            LIMIT 3
        ");
        $stmt->execute();
        $challenges = $stmt->fetchAll();
        
        // Assign to character
        $stmt = $this->pdo->prepare("
            INSERT INTO character_challenges (character_id, challenge_id, assigned_date)
            VALUES (:char_id, :chal_id, CURDATE())
        ");
        
        foreach ($challenges as $challenge) {
            $stmt->execute([
                'char_id' => $characterId,
                'chal_id' => $challenge['id']
            ]);
        }
        
        return true;
    }
    
    /**
     * Get today's challenges for character
     */
    public function getTodaysChallenges($characterId) {
        $stmt = $this->pdo->prepare("
            SELECT cc.id as assignment_id, cc.is_completed, cc.completed_date,
                   c.title, c.description, c.xp_reward, c.attribute_reward, c.difficulty
            FROM character_challenges cc
            JOIN challenges c ON cc.challenge_id = c.id
            WHERE cc.character_id = :char_id AND cc.assigned_date = CURDATE()
            ORDER BY c.difficulty ASC
        ");
        
        $stmt->execute(['char_id' => $characterId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Complete a challenge
     */
    public function completeChallenge($assignmentId, $characterId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get challenge details
            $stmt = $this->pdo->prepare("
                SELECT cc.*, c.xp_reward, c.attribute_reward
                FROM character_challenges cc
                JOIN challenges c ON cc.challenge_id = c.id
                WHERE cc.id = :id AND cc.character_id = :char_id AND cc.is_completed = 0
            ");
            
            $stmt->execute([
                'id' => $assignmentId,
                'char_id' => $characterId
            ]);
            
            $challenge = $stmt->fetch();
            
            if (!$challenge) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Mark as completed
            $stmt = $this->pdo->prepare("
                UPDATE character_challenges
                SET is_completed = 1, completed_date = CURDATE(), xp_earned = :xp
                WHERE id = :id
            ");
            
            $stmt->execute([
                'xp' => $challenge['xp_reward'],
                'id' => $assignmentId
            ]);
            
            // Award XP
            require_once __DIR__ . '/Character.php';
            $characterModel = new Character($this->pdo);
            $characterModel->addXP($characterId, $challenge['xp_reward']);
            
            // Award attribute bonuses
            if ($challenge['attribute_reward']) {
                $attributeReward = json_decode($challenge['attribute_reward'], true);
                $characterModel->updateAttributes($characterId, $attributeReward);
            }
            
            $this->pdo->commit();
            
            // Check for streak achievements
            $this->checkStreakAchievements($characterId);
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get challenge completion stats
     */
    public function getCompletionStats($characterId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_assigned,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN is_completed = 0 THEN 1 ELSE 0 END) as pending
            FROM character_challenges
            WHERE character_id = :char_id AND assigned_date = CURDATE()
        ");
        
        $stmt->execute(['char_id' => $characterId]);
        return $stmt->fetch();
    }
    
    /**
     * Calculate current streak
     */
    public function getCurrentStreak($characterId) {
        $stmt = $this->pdo->prepare("
            SELECT assigned_date, 
                   SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_count,
                   COUNT(*) as total_count
            FROM character_challenges
            WHERE character_id = :char_id
            GROUP BY assigned_date
            HAVING completed_count > 0
            ORDER BY assigned_date DESC
            LIMIT 30
        ");
        
        $stmt->execute(['char_id' => $characterId]);
        $days = $stmt->fetchAll();
        
        if (empty($days)) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = new DateTime();
        
        foreach ($days as $day) {
            $dayDate = new DateTime($day['assigned_date']);
            $diff = $currentDate->diff($dayDate)->days;
            
            if ($diff == $streak) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Check and award streak achievements
     */
    private function checkStreakAchievements($characterId) {
        $streak = $this->getCurrentStreak($characterId);
        
        $stmt = $this->pdo->prepare("
            SELECT a.id, a.xp_bonus
            FROM achievements a
            LEFT JOIN character_achievements ca ON a.id = ca.achievement_id AND ca.character_id = :char_id
            WHERE a.requirement_type = 'streak'
            AND a.requirement_value <= :streak
            AND ca.id IS NULL
        ");
        
        $stmt->execute([
            'char_id' => $characterId,
            'streak' => $streak
        ]);
        
        require_once __DIR__ . '/Character.php';
        $characterModel = new Character($this->pdo);
        
        while ($achievement = $stmt->fetch()) {
            // Unlock achievement
            $stmtInsert = $this->pdo->prepare("
                INSERT IGNORE INTO character_achievements (character_id, achievement_id)
                VALUES (:char_id, :ach_id)
            ");
            
            $stmtInsert->execute([
                'char_id' => $characterId,
                'ach_id' => $achievement['id']
            ]);
            
            // Award bonus XP
            if ($achievement['xp_bonus'] > 0) {
                $characterModel->addXP($characterId, $achievement['xp_bonus']);
            }
        }
    }
}
