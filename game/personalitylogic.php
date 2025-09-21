<?php


/**
 * Get personality configuration for weighted decision making
 * 
 * @param string $personality The personality name
 * @param string $subtype The subtype name (optional)
 * @return array|null Configuration array or null if not found
 */
function GetPersonalityConfig($personality, $subtype = null) {
    global $PERSONALITIES;
    
    if (!isset($PERSONALITIES[$personality])) {
        Debug("GetPersonalityConfig: Unknown personality '$personality'");
        return null;
    }
    
    $personality_config = $PERSONALITIES[$personality];
    
    if ($subtype === null) {
        $subtype = $personality_config['default_subtype'];
    }
    
    if (!isset($personality_config['subtypes'][$subtype])) {
        Debug("GetPersonalityConfig: Unknown subtype '$subtype' for personality '$personality', using default");
        $subtype = $personality_config['default_subtype'];
        if (!isset($personality_config['subtypes'][$subtype])) {
            Debug("GetPersonalityConfig: Default subtype also not found");
            return null;
        }
    }
    
    return array_merge(
        array(
            'personality' => $personality,
            'subtype' => $subtype,
            'name' => $personality_config['name'],
            'description' => $personality_config['description']
        ),
        $personality_config['subtypes'][$subtype]
    );
}

function GetWeightedBuildingChoice($config){
    $building_weights = $config['weights']['building_priority'];
    
    if (empty($building_weights)) {
        Debug("GetWeightedBuildingChoice: No building weights in config");
        return false;
    }
    
    $available_buildings = array();
    $total_weight = 0;
    
    // Check each building and calculate available weight
    foreach ($building_weights as $building_id => $weight) {
        $current_level = BotGetBuild($building_id);
        $cap = $config['building_caps'][$building_id] ?? 999;
        
        // Skip if at or above cap
        if ($current_level >= $cap) {
            continue;
        }
        
        // Check if building is available to build
        if (!BotCanBuild($building_id)) {
            continue;
        }
        
        if ($cap == 0) {
            // If cap is 0, building should never be built
            $cap_factor = 0;
        } else {
            // Calculate diminishing returns based on current level vs cap
            $cap_factor = 1.0 - ($current_level / $cap);
        }
        
        // Apply cap factor to weight (buildings closer to cap get lower priority)
        $adjusted_weight = $weight * max(0.1, $cap_factor); // Minimum 10% weight
        
        if ($adjusted_weight > 0) {
            $available_buildings[$building_id] = $adjusted_weight;
            $total_weight += $adjusted_weight;
        }
    }
    
    if ($total_weight == 0) {
        Debug("GetWeightedBuildingChoice: No buildable buildings available");
        return false;
    }
    
    // Weighted random selection
    $random = rand(1, $total_weight * 100) / 100;
    $current_weight = 0;
    
    foreach ($available_buildings as $building_id => $weight) {
        $current_weight += $weight;
        if ($random <= $current_weight) {
            Debug("GetWeightedBuildingChoice: Selected building $building_id with weight $weight");
            return $building_id;
        }
    }
    
    return false;
}
