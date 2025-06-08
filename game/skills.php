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

/**
 * Enhanced building choice function with skill integration
 * Uses existing function patterns
 *
 * @param array $config Personality configuration
 * @return int|false Building ID or false
 */
function GetSkillModifiedBuildingChoice($config) {
    // Get original personality weights
    $weights = $config['building_weights'] ?? array();
    
    // Apply skill modifications
    $modified_weights = BotModifyDecisionWeights($weights, 'building_management');
    
    // Use existing GetWeightedBuildingChoice logic with modified weights
    $available_buildings = array();
    $total_weight = 0;
    
    foreach ($modified_weights as $building_id => $weight) {
        $current_level = BotGetBuild($building_id);
        $cap = $config['building_caps'][$building_id] ?? 999;
        
        if ($current_level < $cap && BotCanBuild($building_id)) {
            $cap_factor = 1.0 - ($current_level / $cap);
            $adjusted_weight = $weight * $cap_factor;
            
            $available_buildings[$building_id] = $adjusted_weight;
            $total_weight += $adjusted_weight;
        }
    }
    
    if ($total_weight == 0) {
        Debug("GetSkillModifiedBuildingChoice: No buildable buildings available");
        return false;
    }
    
    // Weighted random selection
    $random = rand(1, $total_weight * 100) / 100;
    $current_weight = 0;
    
    foreach ($available_buildings as $building_id => $weight) {
        $current_weight += $weight;
        if ($random <= $current_weight) {
            Debug("GetSkillModifiedBuildingChoice: Selected building $building_id (weight: $weight)");
            return $building_id;
        }
    }
    
    $building_id = array_keys($available_buildings)[0];
    Debug("GetSkillModifiedBuildingChoice: Fallback to building $building_id");
    return $building_id;
}

/**
 * Enhanced research choice function with skill integration
 *
 * @param array $config Personality configuration
 * @return int|false Research ID or false
 */
function GetSkillModifiedResearchChoice($config) {
    // Get original personality weights
    $weights = $config['research_weights'] ?? array();
    
    // Apply skill modifications
    $modified_weights = BotModifyDecisionWeights($weights, 'research_planning');
    
    $available_research = array();
    $total_weight = 0;
    
    foreach ($modified_weights as $research_id => $weight) {
        if (BotCanResearch($research_id)) {
            $available_research[$research_id] = $weight;
            $total_weight += $weight;
        }
    }
    
    if ($total_weight == 0) {
        Debug("GetSkillModifiedResearchChoice: No researchable technologies available");
        return false;
    }
    
    $random = rand(1, $total_weight * 100) / 100;
    $current_weight = 0;
    
    foreach ($available_research as $research_id => $weight) {
        $current_weight += $weight;
        if ($random <= $current_weight) {
            Debug("GetSkillModifiedResearchChoice: Selected research $research_id (weight: $weight)");
            return $research_id;
        }
    }
    
    $research_id = array_keys($available_research)[0];
    Debug("GetSkillModifiedResearchChoice: Fallback to research $research_id");
    return $research_id;
}
