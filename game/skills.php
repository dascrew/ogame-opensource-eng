<?php

function BotGetSkill($skill_type, $default_value = 50) {
    global $BotID;
    return intval(BotGetVar("skill_$skill_type", $default_value));
}


 //Set bot skill level for a specific area
function BotSetSkill($skill_type, $level) {
    $level = max(0, min(100, intval($level))); // Clamp to 0-100
    BotSetVar("skill_$skill_type", $level);
    Debug("BotSetSkill: Set {$skill_type} to {$level}");
}

function BotGetMistakeFrequency($skill_type) {
    $skill = BotGetSkill($skill_type);
    
    if ($skill <= 30) {
        return 0.4 + (rand(0, 20) / 100); // 40-60% mistake chance
    } elseif ($skill <= 70) {
        return 0.15 + (rand(0, 15) / 100); // 15-30% mistake chance
    } else {
        return 0.02 + (rand(0, 8) / 100); // 2-10% mistake chance
    }
}

function BotShouldMakeMistake($skill_type) {
    $mistake_chance = BotGetMistakeFrequency($skill_type);
    return (rand(1, 100) / 100) <= $mistake_chance;
}

function BotModifyDecisionWeights($weights, $skill_type) {
    $skill = BotGetSkill($skill_type);
    
    if (BotShouldMakeMistake($skill_type)) {
        return BotIntroduceMistake($weights, $skill_type, $skill);
    }
    
    return BotOptimizeWeights($weights, $skill);
}

function BotIntroduceMistake($weights, $skill_type, $skill) {
    $mistake_type = BotSelectMistakeType($skill);
    
    Debug("BotIntroduceMistake: {$mistake_type} mistake for {$skill_type} (skill: {$skill})");
    
    switch ($mistake_type) {
        case 'suboptimal_choice':
            return BotMakeSuboptimalChoice($weights);
            
        case 'random_choice':
            return BotMakeRandomChoice($weights);
            
        case 'priority_confusion':
            return BotConfusePriorities($weights);
            
        case 'resource_miscalculation':
            return BotMiscalculateResources($weights);
            
        default:
            return $weights;
    }
}

function BotSelectMistakeType($skill) {
    if ($skill <= 30) {
        // Low skill: Major mistakes
        $mistakes = array('random_choice', 'priority_confusion', 'resource_miscalculation');
        return $mistakes[array_rand($mistakes)];
    } elseif ($skill <= 70) {
        // Medium skill: Minor mistakes
        $mistakes = array('suboptimal_choice', 'priority_confusion');
        return $mistakes[array_rand($mistakes)];
    } else {
        // High skill: Rare minor mistakes
        return 'suboptimal_choice';
    }
}

function BotMakeSuboptimalChoice($weights) {
    // Reduce weights of optimal choices by 20-40%
    $reduction = 0.2 + (rand(0, 20) / 100);
    
    foreach ($weights as $key => $weight) {
        if ($weight > 20) { // High priority items
            $weights[$key] = $weight * (1 - $reduction);
        }
    }
    
    return $weights;
}

function BotMakeRandomChoice($weights) {
    // Heavily randomize all weights
    foreach ($weights as $key => $weight) {
        $weights[$key] = rand(1, 30);
    }
    
    return $weights;
}

function BotConfusePriorities($weights) {
    $keys = array_keys($weights);
    $values = array_values($weights);
    
    // Reverse some of the weights
    $reverse_count = rand(2, min(4, count($values)));
    for ($i = 0; $i < $reverse_count; $i++) {
        $idx1 = rand(0, count($values) - 1);
        $idx2 = rand(0, count($values) - 1);
        
        $temp = $values[$idx1];
        $values[$idx1] = $values[$idx2];
        $values[$idx2] = $temp;
    }
    
    return array_combine($keys, $values);
}

function BotMiscalculateResources($weights) {
    // Favor expensive items incorrectly
    foreach ($weights as $key => $weight) {
        if ($weight < 10) { // Low priority items
            $weights[$key] = $weight * 2; // Double their weight incorrectly
        }
    }
    
    return $weights;
}

function BotOptimizeWeights($weights, $skill) {
    if ($skill >= 80) {
        // High skill: Enhance optimal choices
        $enhancement = 1.1 + (($skill - 80) / 100); // 1.1x to 1.2x boost
        
        foreach ($weights as $key => $weight) {
            if ($weight > 20) { // High priority items
                $weights[$key] = $weight * $enhancement;
            }
        }
    }
    
    return $weights;
}


function BotModifyActivityPattern($base_pattern) {
    $timing_skill = BotGetSkill('timing_coordination');
    
    // Low skill = more predictable, high skill = more random
    if ($timing_skill <= 30) {
        // Very predictable patterns
        $base_pattern['variance'] = max(30, $base_pattern['variance'] * 0.5);
        $base_pattern['idle_probability'] = min(50, $base_pattern['idle_probability'] * 1.5);
    } elseif ($timing_skill >= 80) {
        // Highly unpredictable patterns
        $base_pattern['variance'] = $base_pattern['variance'] * 1.8;
        $base_pattern['idle_probability'] = max(5, $base_pattern['idle_probability'] * 0.7);
    }
    
    Debug("BotModifyActivityPattern: Modified for timing skill {$timing_skill}");
    return $base_pattern;
}

function BotModifyFleetsaveTiming($optimal_time) {
    $fleet_skill = BotGetSkill('fleet_operations');
    
    if (BotShouldMakeMistake('fleet_operations')) {
        if ($fleet_skill <= 30) {
            // Major timing errors: ±30-60 minutes
            $error = rand(1800, 3600) * (rand(0, 1) ? 1 : -1);
        } elseif ($fleet_skill <= 70) {
            // Minor timing errors: ±5-15 minutes
            $error = rand(300, 900) * (rand(0, 1) ? 1 : -1);
        } else {
            // Small timing errors: ±1-5 minutes
            $error = rand(60, 300) * (rand(0, 1) ? 1 : -1);
        }
        
        $modified_time = max(60, $optimal_time + $error); // Minimum 1 minute
        Debug("BotModifyFleetsaveTiming: Timing error: {$error}s (skill: {$fleet_skill})");
        return $modified_time;
    }
    
    return $optimal_time;
}

function BotModifyEspionageAccuracy($spy_report) {
    $esp_skill = BotGetSkill('espionage_accuracy');
    
    if (BotShouldMakeMistake('espionage_accuracy')) {
        if ($esp_skill <= 30) {
            // Major inaccuracies: ±20-50% error
            $error_factor = 1 + (rand(20, 50) / 100) * (rand(0, 1) ? 1 : -1);
        } elseif ($esp_skill <= 70) {
            // Minor inaccuracies: ±5-15% error
            $error_factor = 1 + (rand(5, 15) / 100) * (rand(0, 1) ? 1 : -1);
        } else {
            // Small inaccuracies: ±1-5% error
            $error_factor = 1 + (rand(1, 5) / 100) * (rand(0, 1) ? 1 : -1);
        }
        
        foreach ($spy_report as $key => $value) {
            if (is_numeric($value)) {
                $spy_report[$key] = max(0, intval($value * $error_factor));
            }
        }
        
        Debug("BotModifyEspionageAccuracy: Inaccuracy applied: {$error_factor}x (skill: {$esp_skill})");
    }
    
    return $spy_report;
}

/**
 * Initialize skills for a new bot
 * Uses existing BotSetVar pattern
 * 
 * @param string $personality Bot's personality type
 */
function BotInitializeSkills($personality = null) {
    if ($personality === null) {
        $personality = BotGetVar('personality', 'miner');
    }
    
    // Base skill ranges by personality
    $skill_ranges = array(
        'fleeter' => array(
            'building_management' => array(40, 70),
            'research_planning' => array(30, 60),
            'fleet_operations' => array(60, 90),
            'resource_management' => array(40, 70),
            'combat_assessment' => array(70, 95),
            'timing_coordination' => array(50, 80),
            'espionage_accuracy' => array(60, 85)
        ),
        'miner' => array(
            'building_management' => array(70, 95),
            'research_planning' => array(60, 85),
            'fleet_operations' => array(20, 50),
            'resource_management' => array(80, 95),
            'combat_assessment' => array(10, 40),
            'timing_coordination' => array(60, 85),
            'espionage_accuracy' => array(40, 70)
        ),
        'turtle' => array(
            'building_management' => array(60, 85),
            'research_planning' => array(80, 95),
            'fleet_operations' => array(20, 45),
            'resource_management' => array(70, 90),
            'combat_assessment' => array(30, 60),
            'timing_coordination' => array(70, 90),
            'espionage_accuracy' => array(70, 90)
        ),
        'trader' => array(
            'building_management' => array(60, 80),
            'research_planning' => array(50, 75),
            'fleet_operations' => array(40, 70),
            'resource_management' => array(80, 95),
            'combat_assessment' => array(30, 60),
            'timing_coordination' => array(60, 85),
            'espionage_accuracy' => array(60, 85)
        ),
        'raider' => array(
            'building_management' => array(40, 65),
            'research_planning' => array(30, 55),
            'fleet_operations' => array(70, 90),
            'resource_management' => array(50, 75),
            'combat_assessment' => array(70, 90),
            'timing_coordination' => array(60, 85),
            'espionage_accuracy' => array(70, 90)
        )
    );
    
    $ranges = $skill_ranges[$personality] ?? $skill_ranges['miner'];
    
    foreach ($ranges as $skill_type => $range) {
        $skill_level = rand($range[0], $range[1]);
        BotSetSkill($skill_type, $skill_level);
    }
    
    Debug("BotInitializeSkills: Initialized skills with personality {$personality}");
}

function GetSkillModifiedResearchChoice($config) {
    $weights = $config['weights']['research_priority'];
    
    $available_research = array();
    $total_weight = 0;
    
    foreach ($weights as $research_id => $weight) {
        if (BotCanResearch($research_id)) {
            $available_research[$research_id] = $weight;
            $total_weight += $weight;
        }
    }
    
    if ($total_weight == 0) {
        Debug("GetSkillModifiedResearchChoice: No researchable technologies available");
        return false;
    }
    
    // Weighted random selection using base personality weights
    $random = rand(1, $total_weight * 100) / 100;
    $current_weight = 0;
    
    foreach ($available_research as $research_id => $weight) {
        $current_weight += $weight;
        if ($random <= $current_weight) {
            Debug("GetSkillModifiedResearchChoice: Selected research $research_id with weight $weight");
            return $research_id;
        }
    }
    
    return false;
}

function BotIncreaseSkillOverTime($skill_type, $increment = 0.1) {
    $current_skill = BotGetSkill($skill_type, 50);
    
    // Apply diminishing returns - harder to improve at higher levels
    if ($current_skill >= 90) {
        $increment *= 0.1; // 10x slower at 90+ skill
    } elseif ($current_skill >= 80) {
        $increment *= 0.3; // 3x slower at 80+ skill
    } elseif ($current_skill >= 70) {
        $increment *= 0.5; // 2x slower at 70+ skill
    }
    
    $new_skill = min(100, $current_skill + $increment);
    
    // Only update if there's actual change (avoid spam)
    if (floor($new_skill) > floor($current_skill)) {
        BotSetSkill($skill_type, $new_skill);
        Debug("BotIncreaseSkillOverTime: Increased {$skill_type} from " . floor($current_skill) . " to " . floor($new_skill));
    } else {
        BotSetSkill($skill_type, $new_skill); // Update fractional progress
    }
}

/**
 * Increase skill based on combat events (attacking or being attacked)
 * 
 * @param string $skill_type The skill to increase
 * @param float $increment Amount to increase (default 2.0 for meaningful combat learning)
 * @param bool $won_battle Whether the bot won the battle (affects learning rate)
 */
function BotIncreaseSkillOnCombat($skill_type, $increment = 2.0, $won_battle = true) {
    $current_skill = BotGetSkill($skill_type, 50);
    
    // Learn more from losses than victories (realistic learning)
    if (!$won_battle) {
        $increment *= 1.5; // 50% more learning from defeats
    }
    
    // Apply diminishing returns for combat learning too
    if ($current_skill >= 90) {
        $increment *= 0.2; // Much slower at high levels
    } elseif ($current_skill >= 80) {
        $increment *= 0.4;
    } elseif ($current_skill >= 70) {
        $increment *= 0.6;
    }
    
    $new_skill = min(100, $current_skill + $increment);
    BotSetSkill($skill_type, $new_skill);
    
    $result_text = $won_battle ? "victory" : "defeat";
    Debug("BotIncreaseSkillOnCombat: Increased {$skill_type} from " . floor($current_skill) . " to " . floor($new_skill) . " from {$result_text}");
}

/**
 * Function to be called periodically to increase skills over time
 * Call this daily or weekly for slow, steady progression
 */
function BotPeriodicSkillIncrease() {
    $last_skill_update = BotGetVar('last_skill_time_update', 0);
    $current_time = time();
    
    // Only update once per day to keep progression slow
    if (($current_time - $last_skill_update) < 86400) {
        return;
    }
    
    $skill_types = array(
        'building_management', 
        'research_planning', 
        'fleet_operations', 
        'resource_management', 
        'combat_assessment', 
        'timing_coordination', 
        'espionage_accuracy'
    );
    
    // Very slow daily skill increases (0.1 per day = 36.5 skill points per year)
    foreach ($skill_types as $skill) {
        BotIncreaseSkillOverTime($skill, 0.1);
    }
    
    BotSetVar('last_skill_time_update', $current_time);
    Debug("BotPeriodicSkillIncrease: Daily skill progression applied");
}

/**
 * Function to be called on combat events only (attack or defense)
 *
 * @param bool $was_attacker Whether bot was the attacker
 * @param bool $won_battle Whether bot won the battle
 * @param array $battle_data Additional battle information
 */
function BotCombatEventSkillIncrease($was_attacker = true, $won_battle = true, $battle_data = array()) {
    // Primary combat skills that improve through battle
    $base_increment = 1.5;
    
    // Attackers learn more about fleet operations and timing
    if ($was_attacker) {
        BotIncreaseSkillOnCombat('fleet_operations', $base_increment * 1.2, $won_battle);
        BotIncreaseSkillOnCombat('timing_coordination', $base_increment, $won_battle);
        BotIncreaseSkillOnCombat('combat_assessment', $base_increment * 0.8, $won_battle);
    } else {
        // Defenders learn more about combat assessment and timing
        BotIncreaseSkillOnCombat('combat_assessment', $base_increment * 1.2, $won_battle);
        BotIncreaseSkillOnCombat('timing_coordination', $base_increment, $won_battle);
        BotIncreaseSkillOnCombat('fleet_operations', $base_increment * 0.8, $won_battle);
    }
    
    // Bonus learning for significant battles
    if (isset($battle_data['ships_involved']) && $battle_data['ships_involved'] > 100) {
        $combat_skills = array('combat_assessment', 'fleet_operations', 'timing_coordination');
        foreach ($combat_skills as $skill) {
            BotIncreaseSkillOnCombat($skill, 0.5, $won_battle); // Bonus learning
        }
        Debug("BotCombatEventSkillIncrease: Bonus learning from major battle");
    }
    
    Debug("BotCombatEventSkillIncrease: Combat learning applied - Attacker: " . ($was_attacker ? "Yes" : "No") . ", Won: " . ($won_battle ? "Yes" : "No"));
}

/**
 * Calculate skill-based difficulty scaling for opponents
 * Higher skill bots face tougher challenges
 *
 * @return float Difficulty multiplier (1.0 = normal, 1.5 = 50% harder)
 */
function BotCalculateDifficultyScaling() {
    // Calculate overall skill level
    $skills = array(
        'building_management' => BotGetSkill('building_management'),
        'research_planning' => BotGetSkill('research_planning'),
        'fleet_operations' => BotGetSkill('fleet_operations'),
        'resource_management' => BotGetSkill('resource_management'),
        'combat_assessment' => BotGetSkill('combat_assessment'),
        'timing_coordination' => BotGetSkill('timing_coordination'),
        'espionage_accuracy' => BotGetSkill('espionage_accuracy')
    );
    
    $average_skill = array_sum($skills) / count($skills);
    
    // Scale difficulty based on skill level
    if ($average_skill >= 90) {
        $multiplier = 1.8; // Expert level - much harder opponents
    } elseif ($average_skill >= 80) {
        $multiplier = 1.5; // Advanced level - harder opponents
    } elseif ($average_skill >= 70) {
        $multiplier = 1.3; // Intermediate level - moderately harder
    } elseif ($average_skill >= 60) {
        $multiplier = 1.1; // Competent level - slightly harder
    } else {
        $multiplier = 1.0; // Novice level - normal difficulty
    }
    
    Debug("BotCalculateDifficultyScaling: Average skill {$average_skill}, difficulty multiplier {$multiplier}");
    return $multiplier;
}

function BotApplyAgeBasedSkillProgression() {
    $bot_creation_time = BotGetVar('bot_creation_time', time());
    $bot_age_days = (time() - $bot_creation_time) / 86400;
    $age_bonus = min(10, $bot_age_days * 0.1);
    
    if ($age_bonus > 0) {
        $skill_types = array('building_management', 'research_planning', 'resource_management');
        
        foreach ($skill_types as $skill) {
            $current_skill = BotGetSkill($skill);
            $target_skill = min(100, 50 + $age_bonus);
            
            if ($current_skill < $target_skill) {
                BotSetSkill($skill, $target_skill);
            }
        }
        
        Debug("BotApplyAgeBasedSkillProgression: Applied age bonus of {$age_bonus} skill points");
    }
}
