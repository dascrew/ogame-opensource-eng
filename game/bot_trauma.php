<?php

require_once "id.php";

function BotDetectFleetLoss($loss_data, $personality, $total_points) {
    $ships_lost = $loss_data['ships_lost'] ?? 0;
    $fleet_value = $loss_data['fleet_value'] ?? 0;
    $total_fleet_before = $loss_data['total_fleet_before'] ?? 1;
    
    $fleet_loss_percentage = ($ships_lost / $total_fleet_before) * 100;
    $value_loss_percentage = ($fleet_value / $total_points) * 100;
    
    $thresholds = array(
        'fleeter' => array('fleet_percent' => 80, 'value_percent' => 30),
        'raider' => array('fleet_percent' => 70, 'value_percent' => 25),
        'miner' => array('fleet_percent' => 50, 'value_percent' => 15),
        'turtle' => array('fleet_percent' => 60, 'value_percent' => 20),
        'trader' => array('fleet_percent' => 65, 'value_percent' => 20)
    );
    
    $threshold = $thresholds[$personality] ?? $thresholds['miner'];
    
    $is_extreme = ($fleet_loss_percentage >= $threshold['fleet_percent']) || 
                  ($value_loss_percentage >= $threshold['value_percent']);
    
    if ($is_extreme) {
        Debug("BotDetectFleetLoss: Extreme loss - {$fleet_loss_percentage}% fleet, {$value_loss_percentage}% value");
    }
    
    return $is_extreme;
}

function BotDetectResourceLoss($loss_data, $personality, $total_points) {
    $total_stolen = $loss_data['total_resources_lost'] ?? 0;
    $production_days_lost = $loss_data['production_days_lost'] ?? 0;
    
    $base_thresholds = array(
        'miner' => array('production_days' => 14, 'resource_percent' => 60, 'repeated_raids' => 8),
        'trader' => array('production_days' => 10, 'resource_percent' => 45, 'repeated_raids' => 6),
        'turtle' => array('production_days' => 21, 'resource_percent' => 70, 'repeated_raids' => 12),
        'fleeter' => array('production_days' => 7, 'resource_percent' => 35, 'repeated_raids' => 4),
        'raider' => array('production_days' => 8, 'resource_percent' => 40, 'repeated_raids' => 5)
    );
    
    $thresholds = $base_thresholds[$personality] ?? $base_thresholds['miner'];
    $resource_loss_percentage = ($total_stolen / $total_points) * 100;
    
    $triggers = array();
    
    if ($production_days_lost >= $thresholds['production_days']) {
        $triggers[] = "massive_production_loss";
    }
    
    if ($resource_loss_percentage >= $thresholds['resource_percent']) {
        $triggers[] = "wealth_devastation";
    }
    
    $recent_raids = BotGetVar('raid_count_30days', 0);
    if ($recent_raids >= $thresholds['repeated_raids']) {
        $triggers[] = "repeated_harassment";
    }
    
    $is_extreme = !empty($triggers);
    
    if ($is_extreme) {
        Debug("BotDetectResourceLoss: Extreme loss - Triggers: " . implode(", ", $triggers));
        BotSetVar('last_trauma_triggers', serialize($triggers));
    }
    
    return $is_extreme;
}

/**
 * Main trauma detection function
 */
function BotDetectExtremeLoss($loss_type, $loss_data) {
    $personality = BotGetVar('personality', 'miner');
    $total_points = BotGetVar('total_points', 1000);
    
    switch ($loss_type) {
        case 'fleet':
            return BotDetectFleetLoss($loss_data, $personality, $total_points);
        case 'resources':
            return BotDetectResourceLoss($loss_data, $personality, $total_points);
        default:
            return false;
    }
}


/**
 * Decide how bot responds to trauma
 */
function BotDecideTraumaResponse($loss_type, $loss_data) {
    $personality = BotGetVar('personality', 'miner');
    $mental_resilience = BotGetSkill('timing_coordination');
    $previous_traumas = BotGetVar('trauma_count', 0);
    
    $base_responses = array(
        'fleeter' => array('continue' => 60, 'switch' => 25, 'quit' => 15),
        'raider' => array('continue' => 55, 'switch' => 30, 'quit' => 15),
        'miner' => array('continue' => 70, 'switch' => 20, 'quit' => 10),
        'turtle' => array('continue' => 75, 'switch' => 15, 'quit' => 10),
        'trader' => array('continue' => 65, 'switch' => 25, 'quit' => 10)
    );
    
    $responses = $base_responses[$personality] ?? $base_responses['miner'];
    
    // Modify based on mental resilience
    if ($mental_resilience >= 80) {
        $responses['continue'] += 20;
        $responses['quit'] -= 10;
        $responses['switch'] -= 10;
    } elseif ($mental_resilience <= 30) {
        $responses['continue'] -= 20;
        $responses['quit'] += 10;
        $responses['switch'] += 10;
    }
    
    // Multiple traumas increase quit probability
    if ($previous_traumas >= 2) {
        $responses['quit'] += 15 * $previous_traumas;
        $responses['continue'] -= 10 * $previous_traumas;
    }
    
    // Ensure valid probabilities
    $responses['continue'] = max(5, min(85, $responses['continue']));
    $responses['switch'] = max(5, min(40, $responses['switch']));
    $responses['quit'] = max(5, min(50, $responses['quit']));
    
    // Make weighted random decision
    $random = rand(1, 100);
    if ($random <= $responses['continue']) {
        return 'continue';
    } elseif ($random <= $responses['continue'] + $responses['switch']) {
        return 'switch_personality';
    } else {
        return 'quit';
    }
}

/**
 * Execute trauma response
 */
function BotExecuteTraumaResponse($response, $loss_type) {
    global $BotID;
    
    $trauma_count = BotGetVar('trauma_count', 0) + 1;
    BotSetVar('trauma_count', $trauma_count);
    BotSetVar('last_trauma_time', time());
    BotSetVar('last_trauma_type', $loss_type);
    BotSetVar('last_trauma_response', $response);
    
    Debug("BotExecuteTraumaResponse: Executing {$response} response to {$loss_type} loss (trauma #{$trauma_count})");
    
    switch ($response) {
        case 'continue':
            $recovery_time = rand(3600, 10800);
            BotSetVar('trauma_recovery_until', time() + $recovery_time);
            $current_aggression = BotGetVar('aggression_level', 50);
            BotSetVar('aggression_level', max(20, $current_aggression - 10));
            Debug("BotExecuteTraumaResponse: Continuing with {$recovery_time}s recovery period");
            break;
            
        case 'switch_personality':
            global $PERSONALITIES;
            $current_personality = BotGetVar('personality', 'miner');
            $available_personalities = array_keys($PERSONALITIES);
            $new_personalities = array_filter($available_personalities, function($p) use ($current_personality) {
                return $p !== $current_personality;
            });
            
            $new_personality = $new_personalities[array_rand($new_personalities)];
            $available_subtypes = array_keys($PERSONALITIES[$new_personality]['subtypes']);
            $new_subtype = $available_subtypes[array_rand($available_subtypes)];
            
            BotSetVar('personality', $new_personality);
            BotSetVar('subtype', $new_subtype);
            BotSetVar('personality_switch_time', time());
            BotSetVar('personality_switch_reason', 'trauma_response');
            
            BotInitializeSkills($new_personality);
            StopBot($BotID);
            StartBot($BotID);
            
            Debug("BotExecuteTraumaResponse: Switched to {$new_personality}/{$new_subtype}");
            break;
            
        case 'quit':
            BotSetVar('bot_status', 'inactive');
            BotSetVar('quit_time', time());
            BotSetVar('quit_reason', 'trauma_response');
            
            StopBot($BotID);
            
            global $db_prefix;
            $query = "DELETE FROM " . $db_prefix . "queue WHERE owner_id = " . $BotID . " AND type = 'AI'";
            dbquery($query);
            
            Debug("BotExecuteTraumaResponse: Bot has quit due to trauma");
            break;

        default:
            Debug("BotExecuteTraumaResponse: Unknown response type '{$response}'");
            break;
    }
}

/**
 * Main trauma handling function
 */
function BotHandleExtremeLoss($loss_type, $loss_data) {
    if (!BotDetectExtremeLoss($loss_type, $loss_data)) {
        Debug("BotHandleExtremeLoss: Loss not considered extreme");
        return;
    }
    
    $response = BotDecideTraumaResponse($loss_type, $loss_data);
    BotExecuteTraumaResponse($response, $loss_type);
    
    Debug("BotHandleExtremeLoss: Handled {$loss_type} loss with {$response} response");
}

/**
 * Extract fleet loss data from battle results
 */
function ExtractFleetLossData($participant, $res, $is_attacker) {
    global $fleetmap;
    
    $rounds = count($res['rounds']);
    if ($rounds == 0) return array();
    
    $last_round = $res['rounds'][$rounds - 1];
    $final_participants = $is_attacker ? $last_round['attackers'] : $last_round['defenders'];
    
    $participant_final = null;
    foreach ($final_participants as $final_participant) {
        if ($final_participant['id'] == $participant['id']) {
            $participant_final = $final_participant;
            break;
        }
    }
    
    if (!$participant_final) return array();
    
    $ships_lost = 0;
    foreach ($fleetmap as $i => $gid) {
        $before = $participant['fleet'][$gid] ?? 0;
        $after = $participant_final[$gid] ?? 0;
        $lost = max(0, $before - $after);
        $ships_lost += $lost;
    }
    
    if ($ships_lost == 0) return array();
    
    // Get bot's TOTAL fleet size, not just what was in battle
    $old_bot_id = $GLOBALS['BotID'] ?? null;
    $GLOBALS['BotID'] = $participant['player_id'];
    $total_fleet_size = BotCalculateTotalFleetSize();
    $GLOBALS['BotID'] = $old_bot_id;
    
    return array(
        'ships_lost' => $ships_lost,
        'fleet_value' => $participant['points'],
        'total_fleet_before' => $total_fleet_size
    );
}

/**
 * Extract resource loss data
 */
function ExtractResourceLossData($cm, $ck, $cd) {
    $total_stolen = $cm + $ck + $cd;
    if ($total_stolen == 0) return array();
    
    $hourly_production = BotCalculateHourlyProduction();
    $daily_production = $hourly_production * 24;
    $production_days_lost = $daily_production > 0 ? ($total_stolen / $daily_production) : 0;
    
    $total_points = BotGetVar('total_points', 1000);
    $resource_loss_percentage = ($total_stolen / $total_points) * 100;
    
    $previous_raids = BotGetVar('raid_count_30days', 0);
    $raid_frequency_factor = min(3.0, $previous_raids / 5);
    
    return array(
        'total_resources_lost' => $total_stolen,
        'production_days_lost' => $production_days_lost,
        'resource_loss_percentage' => $resource_loss_percentage,
        'raid_frequency_factor' => $raid_frequency_factor,
        'detailed_losses' => array('metal' => $cm, 'crystal' => $ck, 'deuterium' => $cd)
    );
}

function BotCalculateTotalFleetSize() {
    $ships_no_sat = GetFleetIds(false);
    $total = 0;
    foreach ($ships_no_sat as $ship_id) {
        $total += BotGetFleetCount($ship_id);
    }
    return $total;
}


function BotCalculateHourlyProduction() {
    $metal_mine = BotGetBuild(1);
    $crystal_mine = BotGetBuild(2);
    $deut_synth = BotGetBuild(3);
    
    $metal_production = $metal_mine * 30;
    $crystal_production = $crystal_mine * 20;
    $deut_production = $deut_synth * 10;
    
    return $metal_production + $crystal_production + $deut_production;
}

function BotTrackRaidFrequency() {
    $current_count = BotGetVar('raid_count_30days', 0);
    $last_reset = BotGetVar('raid_count_reset', 0);
    
    if ((time() - $last_reset) > (30 * 24 * 3600)) {
        $current_count = 0;
        BotSetVar('raid_count_reset', time());
    }
    
    $current_count++;
    BotSetVar('raid_count_30days', $current_count);
    
    Debug("BotTrackRaidFrequency: Raid #{$current_count} in last 30 days");
}
