<?php
/**
 * Answer Model
 * Handles daily question answers and attribute/XP calculations
 */

class Answer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get today's questions
     */
    public function getTodaysQuestions() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM questions
            WHERE is_active = 1
            ORDER BY id ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user answered today
     */
    public function hasAnsweredToday($characterId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM daily_answers
            WHERE character_id = :char_id AND answered_at = CURDATE()
        ");
        
        $stmt->execute(['char_id' => $characterId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Submit answers and calculate rewards
     */
    public function submitAnswers($characterId, $answers) {
        try {
            $this->pdo->beginTransaction();
            
            require_once __DIR__ . '/Character.php';
            $characterModel = new Character($this->pdo);
            $character = $characterModel->getById($characterId);
            
            $totalXP = 0;
            $attributeChanges = [
                'strength' => 0,
                'intelligence' => 0,
                'discipline' => 0,
                'energy' => 0,
                'spirit' => 0
            ];
            
            foreach ($answers as $questionId => $answerValue) {
                // Get question details
                $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE id = :id");
                $stmt->execute(['id' => $questionId]);
                $question = $stmt->fetch();
                
                if (!$question) continue;
                
                // Calculate impact
                $impact = $this->calculateImpact($question, $answerValue, $character);
                
                // Store answer
                $stmt = $this->pdo->prepare("
                    INSERT INTO daily_answers 
                    (character_id, question_id, answer_value, xp_gained, attribute_changes, answered_at)
                    VALUES (:char_id, :q_id, :answer, :xp, :attrs, CURDATE())
                    ON DUPLICATE KEY UPDATE
                    answer_value = :answer, xp_gained = :xp, attribute_changes = :attrs
                ");
                
                $stmt->execute([
                    'char_id' => $characterId,
                    'q_id' => $questionId,
                    'answer' => $answerValue,
                    'xp' => $impact['xp'],
                    'attrs' => json_encode($impact['attributes'])
                ]);
                
                // Accumulate rewards
                $totalXP += $impact['xp'];
                foreach ($impact['attributes'] as $attr => $value) {
                    $attributeChanges[$attr] += $value;
                }
            }
            
            // Apply all rewards
            if ($totalXP > 0) {
                $characterModel->addXP($characterId, $totalXP);
            }
            
            if (array_sum($attributeChanges) != 0) {
                $characterModel->updateAttributes($characterId, $attributeChanges);
            }
            
            // Log progress
            $characterModel->logProgress($characterId);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'xp_gained' => $totalXP,
                'attribute_changes' => $attributeChanges
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Calculate impact based on question and answer
     */
    private function calculateImpact($question, $answerValue, $character) {
        $xp = 0;
        $attributes = [
            'strength' => 0,
            'intelligence' => 0,
            'discipline' => 0,
            'energy' => 0,
            'spirit' => 0
        ];
        
        // Apply class bonus
        $classBonus = 1;
        $bonusAttr = CHARACTER_CLASSES[$character['class']]['bonus_attribute'];
        
        switch ($question['category']) {
            case 'sleep':
                $impact = $this->calculateSleepImpact($answerValue, $question);
                $attributes['energy'] += $impact['energy'];
                $attributes['discipline'] += $impact['discipline'];
                $xp += $impact['xp'];
                break;
                
            case 'exercise':
                $impact = $this->calculateExerciseImpact($answerValue, $question);
                $attributes['strength'] += $impact['strength'] * ($bonusAttr == 'strength' ? 2 : 1);
                $attributes['discipline'] += $impact['discipline'];
                $xp += $impact['xp'];
                break;
                
            case 'study':
                $impact = $this->calculateStudyImpact($answerValue, $question);
                $attributes['intelligence'] += $impact['intelligence'] * ($bonusAttr == 'intelligence' ? 2 : 1);
                $attributes['discipline'] += $impact['discipline'];
                $xp += $impact['xp'];
                break;
                
            case 'productivity':
                $impact = $this->calculateProductivityImpact($answerValue, $question);
                $attributes['discipline'] += $impact['discipline'] * ($bonusAttr == 'discipline' ? 2 : 1);
                $attributes['spirit'] += $impact['spirit'];
                $xp += $impact['xp'];
                break;
                
            case 'health':
                $impact = $this->calculateHealthImpact($answerValue, $question);
                $attributes['spirit'] += $impact['spirit'];
                $attributes['energy'] += $impact['energy'];
                $xp += $impact['xp'];
                break;
        }
        
        // Base XP for answering
        $xp += 10;
        
        return [
            'xp' => $xp,
            'attributes' => $attributes
        ];
    }
    
    /**
     * Calculate sleep impact
     */
    private function calculateSleepImpact($answer, $question) {
        $impact = ['energy' => 0, 'discipline' => 0, 'xp' => 0];
        
        if (strpos($question['question_text'], 'Quantas horas') !== false) {
            $hours = floatval($answer);
            if ($hours < 6) {
                $impact['energy'] = -3;
            } elseif ($hours >= 6 && $hours < 7) {
                $impact['energy'] = 1;
                $impact['xp'] = 20;
            } elseif ($hours >= 7 && $hours <= 8) {
                $impact['energy'] = 3;
                $impact['xp'] = 30;
            } else {
                $impact['energy'] = 2;
                $impact['xp'] = 25;
            }
        } elseif (strpos($question['question_text'], 'dormiu depois') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['energy'] = -2;
                $impact['discipline'] = -1;
            } else {
                $impact['discipline'] = 1;
                $impact['xp'] = 20;
            }
        } elseif (strpos($question['question_text'], 'trabalho ou estudos') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['discipline'] = 1;
                $impact['energy'] = -1; // Still tired but disciplined
            }
        }
        
        return $impact;
    }
    
    /**
     * Calculate exercise impact
     */
    private function calculateExerciseImpact($answer, $question) {
        $impact = ['strength' => 0, 'discipline' => 0, 'xp' => 0];
        
        if (strpos($question['question_text'], 'Você treinou') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['strength'] = 3;
                $impact['discipline'] = 1;
                $impact['xp'] = 50;
            }
        } elseif (strpos($question['question_text'], 'minutos de exercício') !== false) {
            $minutes = intval($answer);
            if ($minutes > 0 && $minutes < 20) {
                $impact['strength'] = 1;
                $impact['xp'] = 20;
            } elseif ($minutes >= 20 && $minutes <= 40) {
                $impact['strength'] = 3;
                $impact['discipline'] = 1;
                $impact['xp'] = 50;
            } elseif ($minutes > 40) {
                $impact['strength'] = 5;
                $impact['discipline'] = 2;
                $impact['xp'] = 100;
            }
        }
        
        return $impact;
    }
    
    /**
     * Calculate study impact
     */
    private function calculateStudyImpact($answer, $question) {
        $impact = ['intelligence' => 0, 'discipline' => 0, 'xp' => 0];
        
        if (strpos($question['question_text'], 'Você estudou') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['intelligence'] = 3;
                $impact['discipline'] = 1;
                $impact['xp'] = 50;
            }
        } elseif (strpos($question['question_text'], 'minutos de estudo') !== false) {
            $minutes = intval($answer);
            if ($minutes > 0 && $minutes < 30) {
                $impact['intelligence'] = 1;
                $impact['xp'] = 20;
            } elseif ($minutes >= 30 && $minutes <= 60) {
                $impact['intelligence'] = 3;
                $impact['discipline'] = 1;
                $impact['xp'] = 50;
            } elseif ($minutes > 60) {
                $impact['intelligence'] = 5;
                $impact['discipline'] = 2;
                $impact['xp'] = 100;
            }
        }
        
        return $impact;
    }
    
    /**
     * Calculate productivity impact
     */
    private function calculateProductivityImpact($answer, $question) {
        $impact = ['discipline' => 0, 'spirit' => 0, 'xp' => 0];
        
        if (strpos($question['question_text'], 'objetivos do dia') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['discipline'] = 2;
                $impact['spirit'] = 2;
                $impact['xp'] = 100;
            }
        } elseif (strpos($question['question_text'], 'foco hoje') !== false) {
            $rating = intval($answer);
            if ($rating >= 8) {
                $impact['discipline'] = 3;
                $impact['xp'] = 50;
            } elseif ($rating >= 5) {
                $impact['discipline'] = 1;
                $impact['xp'] = 25;
            }
        }
        
        return $impact;
    }
    
    /**
     * Calculate health impact
     */
    private function calculateHealthImpact($answer, $question) {
        $impact = ['spirit' => 0, 'energy' => 0, 'xp' => 0];
        
        if (strpos($question['question_text'], 'meditou') !== false || strpos($question['question_text'], 'mindfulness') !== false) {
            if ($answer === 'sim' || $answer === '1') {
                $impact['spirit'] = 3;
                $impact['energy'] = 1;
                $impact['xp'] = 75;
            }
        }
        
        return $impact;
    }
    
    /**
     * Get answers history
     */
    public function getAnswersHistory($characterId, $days = 7) {
        $stmt = $this->pdo->prepare("
            SELECT da.*, q.question_text, q.category
            FROM daily_answers da
            JOIN questions q ON da.question_id = q.id
            WHERE da.character_id = :char_id
            AND da.answered_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            ORDER BY da.answered_at DESC, da.id ASC
        ");
        
        $stmt->execute([
            'char_id' => $characterId,
            'days' => $days
        ]);
        
        return $stmt->fetchAll();
    }
}
