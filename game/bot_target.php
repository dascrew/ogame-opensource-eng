<?php


function BotSimulateBattle($attacker_fleet, $defender_fleet, $defender_defense) {
    $attacker_power = BotCalculateFleetResourceCost($attacker_fleet);
    $defender_power = BotCalculateFleetResourceCost($defender_fleet) + BotCalculateFleetResourceCost($defender_defense);
    
    $result = ['attacker_losses' => [], 'defender_losses' => [], 'winner' => 'defender'];
    if ($attacker_power > $defender_power * 1.2) {
        $result['winner'] = 'attacker';
        $loss_percent = min(1.0, ($defender_power / max(1, $attacker_power)) * 0.8);
        foreach ($attacker_fleet as $id => $count) { $result['attacker_losses'][$id] = floor($count * $loss_percent); }
        foreach ($defender_fleet as $id => $count) { $result['defender_losses'][$id] = $count; }
        foreach ($defender_defense as $id => $count) { $result['defender_losses'][$id] = $count; }
    } else {
        foreach ($attacker_fleet as $id => $count) { $result['attacker_losses'][$id] = $count; }
        $loss_percent = min(1.0, ($attacker_power / max(1, $defender_power)) * 0.8);
        foreach ($defender_fleet as $id => $count) { $result['defender_losses'][$id] = floor($count * $loss_percent); }
        foreach ($defender_defense as $id => $count) { $result['defender_losses'][$id] = floor($count * $loss_percent); }
    }
    return $result;
}

function BotFindPotentialTarget(){

}

function BotCreateCoordinatedAttack() {
    global $BotID;

    // Check if this bot is already part of an attacking ACS.
    $unions = EnumUnion($BotID);
    foreach($unions as $union) {
        $fleet = LoadFleet($union['fleet_id']);
        if ($fleet['owner_id'] === $BotID) {
            Debug("BotCreateCoordinatedAttack: Bot is already leading an active ACS. Waiting.");
            return 1800;
        }
    }

    // Step 1: Find a target and ensure we have intelligence on it
    $target = BotFindPotentialTarget();
    if (!$target) {
        Debug("BotCreateCoordinatedAttack: Could not find a suitable target.");
        return rand(3600, 7200);
    }

    // Step 2: Check if we have a recent spy report for this target
    $spy_data = BotGetStructuredSpyReport($target['planet_id']);
    if (!$spy_data || !$spy_data['success']) {
        // No spy report available, need to scout first using the attack sequence
        Debug("BotCreateCoordinatedAttack: No spy report available. Initiating scouting sequence.");
        BotSetVar('attack_target', serialize($target));
        BotSetVar('attack_phase', 'scouting');
        return BotExecuteAttackSequence(); // Use the existing scouting logic
    }

    // Step 3: Evaluate profitability using the spy report
    $fleet_to_send = BotEvaluateAttackProfitability($spy_data, $target);
    if (!$fleet_to_send) {
        Debug("BotCreateCoordinatedAttack: Target not profitable for coordinated attack.");
        return rand(1800, 3600);
    }

    // Step 4: Dispatch the lead fleet
    $user = LoadUser($BotID);
    $start_planet = GetPlanet($user['aktplanet']);
    $lead_fleet_id = DispatchFleet($fleet_to_send, $start_planet, $target, FTYP_ATTACK, 0, 0, 0, 0, 0, time());

    if (!$lead_fleet_id) {
        Debug("BotCreateCoordinatedAttack: Failed to dispatch the lead fleet.");
        return 900;
    }

    // Step 5: Create the ACS union
    $union_name = "KV-" . rand(100, 999);
    $union_id = CreateUnion($lead_fleet_id, $union_name);

    if ($union_id > 0) {
        // Step 6: Create attack plan with SHARED spy report
        $lead_fleet = LoadFleet($lead_fleet_id);
        $attack_plan = array(
            'plan_id'           => 'acs-' . $union_id,
            'union_id'          => $union_id,
            'target_info'       => $target,
            'arrival_time'      => $lead_fleet['end_time'],
            'shared_spy_report' => $spy_data  // SHARE the spy report with the plan
        );
        
        // Store in alliance leader's botvars
        $alliance_data = LoadAlly($user['ally_id']);
        if ($alliance_data) {
            BotSetVar($alliance_data['owner_id'], 'coord_attack_plan', json_encode($attack_plan));
            Debug("BotCreateCoordinatedAttack: Stored attack plan with shared spy report.");
        }

        BotInviteAllianceToACS($union_id);
        
        // Clear attack state since we've created the coordinated attack
        BotDeleteVar($BotID, 'attack_phase');
        BotDeleteVar($BotID, 'attack_target');
    } else {
        RecallFleet($lead_fleet_id);
    }

    return rand(3600, 7200);
}

function BotAssessTargetRisk($target_planet) {
    $target_user = LoadUser($target_planet['owner_id']);
    $bot_user = LoadUser($GLOBALS['BotID']);
    
    // Don't attack alliance members
    if ($target_user['ally_id'] == $bot_user['ally_id'] && $bot_user['ally_id'] > 0) {
        return false;
    }
    
    // Don't attack targets that are too strong
    $bot_points = BotGetVar('total_points', 1000);
    if ($target_user['score1'] > $bot_points * 2) {
        return false;
    }
    
    return true;
}

function BotCheckAndJoinCoordinatedAttack() {
    global $BotID;
    $user = LoadUser($BotID);
    
    // First check for coordinated attack plans with shared intelligence
    $alliance_data = LoadAlly($user['ally_id']);
    if ($alliance_data) {
        $plan_s = BotGetVar($alliance_data['owner_id'], 'coord_attack_plan', '{}');
        $plan = json_decode($plan_s, true);
        
        // Check if we have a valid plan with shared spy report
        if (!empty($plan) && isset($plan['shared_spy_report']) && $plan['arrival_time'] >= time()) {
            // Check if we're invited to this specific ACS
            $unions = EnumUnion($BotID);
            foreach ($unions as $union) {
                if ($union['union_id'] == $plan['union_id']) {
                    // Use the shared spy report for evaluation
                    $shared_spy_report = $plan['shared_spy_report'];
                    $target_info = $plan['target_info'];
                    
                    Debug("BotCheckAndJoinCoordinatedAttack: Using shared spy report for target {$target_info['oname']}.");
                    
                    // Evaluate using shared intelligence
                    $fleet_to_send = BotEvaluateAttackProfitability($shared_spy_report, $target_info);
                    if ($fleet_to_send) {
                        $start_planet = GetPlanet($user['aktplanet']);
                        $fleet_id = DispatchFleet($fleet_to_send, $start_planet, $target_info, FTYP_ACS_ATTACK, 0, 0, 0, 0, 0, time(), $plan['union_id']);

                        if ($fleet_id) {
                            Debug("BotCheckAndJoinCoordinatedAttack: Joined coordinated attack using shared intelligence.");
                            return rand(1800, 3600);
                        }
                    }
                    break;
                }
            }
        }
    }
    
    // Fall back to regular ACS invitations without shared intelligence
    $unions = EnumUnion($BotID);
    if (empty($unions)) {
        Debug("BotCheckAndJoinCoordinatedAttack: No ACS invitations found.");
        return rand(600, 1800);
    }

    foreach ($unions as $union) {
        $head_fleet = LoadFleet($union['fleet_id']);
        $target_planet = GetPlanet($head_fleet['target_planet']);

        if (!BotAssessTargetRisk($target_planet)) { 
            continue; 
        }

        $fleet_to_send = BotPlanAttackFleet();
        if (!$fleet_to_send) {
            continue;
        }

        $start_planet = GetPlanet($user['aktplanet']);
        $fleet_id = DispatchFleet($fleet_to_send, $start_planet, $target_planet, FTYP_ACS_ATTACK, 0, 0, 0, 0, 0, time(), $union['union_id']);

        if ($fleet_id) {
            Debug("BotCheckAndJoinCoordinatedAttack: Joined regular ACS without shared intel.");
            return rand(1800, 3600); 
        }
    }
    
    return rand(600, 1800);
}

function BotInviteAllianceToACS($union_id) {
    global $db_prefix, $BotID;
    $user = LoadUser($BotID);
    if ($user['ally_id'] == 0) return;
    $members_result = EnumerateAlly($user['ally_id']);
    while ($member = dbarray($members_result)) {
        if ($member['player_id'] != $BotID && IsBot($member['player_id'])) {
            AddUnionMember($union_id, $member['oname']);
            Debug("BotInviteAllianceToACS: Invited bot {$member['oname']} to Union ID {$union_id}.");
        }
    }
}

function BotExecuteAttackSequence() {
    global $BotID;
    
    $attack_phase = BotGetVar('attack_phase', 'idle');
    
    switch ($attack_phase) {
        case 'idle':
            $target = BotFindAttackTarget();
            if (!$target) {
                Debug("BotExecuteAttackSequence: No suitable target found.");
                return rand(1800, 3600);
            }
            
            BotSetVar('attack_target', serialize($target));
            BotSetVar('attack_phase', 'scouting');
            return rand(30, 120);

        case 'scouting':
            $target = unserialize(BotGetVar('attack_target', ''));
            if (empty($target)) { return BotResetAttackState("No target in botvars."); }

            $probes_sent = BotSendEspionageMission($target);
            if ($probes_sent) {
                BotSetVar('attack_phase', 'evaluating');
                $flight_time = BotCalculateFleetTravelTime(array(210 => 1), $target);
                $wait_time = ($flight_time * 2) + rand(30, 90);
                Debug("BotExecuteAttackSequence: Probes sent to {$target['oname']}. Moving to 'evaluating' phase. Waiting {$wait_time}s for report.");
                return $wait_time;
            }
            return BotResetAttackState("Failed to send probes.");

        case 'evaluating':
            $target = unserialize(BotGetVar('attack_target', ''));
            if (empty($target)) { return BotResetAttackState("No target in botvars."); }

            $spy_data = BotGetStructuredSpyReport($target['planet_id']);
            if ($spy_data && $spy_data['success']) {
                $fleet_to_send = BotEvaluateAttackProfitability($spy_data, $target);

                if ($fleet_to_send) {
                    BotLaunchAttackMission($target, $fleet_to_send);
                    return BotResetAttackState("Attack launched successfully!", rand(1800, 5400));
                }
                return BotResetAttackState("Target was not profitable.");
            }
            return BotResetAttackState("Structured spy report not found or was outdated.");
    }
    return 3600;
}

/**
 * Resets the attack state machine
 */
function BotResetAttackState($reason, $wait_time = 900) {
    global $BotID;
    Debug("BotResetAttackState: $reason. Waiting {$wait_time}s.");
    BotDeleteVar($BotID, 'attack_phase');
    BotDeleteVar($BotID, 'attack_target');
    return $wait_time;
}

/**
 * Retrieves and parses a structured spy report for a target planet
 */
function BotGetStructuredSpyReport($target_planet_id) {
    global $BotID;
    $var_name = "spy_report_" . $target_planet_id;
    $report_s = BotGetVar($var_name, null);
    if ($report_s) {
        $report = unserialize($report_s);
        BotDeleteVar($BotID, $var_name); // consume report
        if (isset($report['time']) && (time() - $report['time']) < 3600) {
            return $report;
        }
    }
    return null;
}

/**
 * Profit calculation logic. Is an attack worth it?
 */
function BotEvaluateAttackProfitability($spy_data, $target) {
    $config = GetBotPersonalityConfig();
    $attack_fleet = BotPlanAttackFleet();
    if (empty($attack_fleet)) return false;
    $simulation = BotSimulateBattle($attack_fleet, $spy_data['fleet'], $spy_data['defense']);
    if ($simulation['winner'] !== 'attacker') return false;

    $loot_value = ($spy_data['resources']['m'] + $spy_data['resources']['k']) * 0.5;
    $debris_value = BotCalculateDebrisValue($simulation['attacker_losses'], $simulation['defender_losses']);
    $total_gains = $loot_value + $debris_value;

    $fuel_cost = BotCalculateFleetTravelCost($attack_fleet, $target);
    $fleet_loss_cost = BotCalculateFleetResourceCost($simulation['attacker_losses']);
    $total_cost = $fuel_cost + $fleet_loss_cost;

    if ($total_cost == 0) return $attack_fleet;
    $profit_ratio = $total_gains / $total_cost;
    $required_ratio = $config['attack_preferences']['min_profit_ratio'] ?? 2.0;

    if ($profit_ratio >= $required_ratio) {
        Debug("BotEvaluateAttackProfitability: PROFITABLE. Ratio: " . round($profit_ratio, 2));
        return $attack_fleet;
    }
    Debug("BotEvaluateAttackProfitability: NOT profitable. Ratio: " . round($profit_ratio, 2));
    return false;
}

function BotCalculateDebrisValue($attacker_losses, $defender_losses) {
    $attacker_cost = BotCalculateFleetResourceCost($attacker_losses);
    $defender_cost = BotCalculateFleetResourceCost($defender_losses);
    return ($attacker_cost + $defender_cost) * 0.70;
}
// Bot scouting action - sends probes to scout targets within range
// Returns time to wait (0 = no delay, positive = seconds to wait)
function BotScout($range = 100, $filters = array())
{
    global $BotID, $BotNow;
    
    require_once "user.php";
    require_once "planet.php";
    require_once "fleet.php";
    
    $user = LoadUser($BotID);
    $aktplanet = GetPlanet($user['aktplanet']);
    
    if (!$user || !$aktplanet) {
        Debug("BotScout: Failed to load user or planet data");
        return 0;
    }
    
    // Check if we have probes
    $probe_count = $aktplanet['f' . GID_F_PROBE];
    if ($probe_count < 1) {
        Debug("BotScout: No probes available");
        return 60; // Wait 1 minute and try again
    }
    
    // Check fleet slots
    $result = EnumOwnFleetQueue($user['player_id']);
    $nowfleet = dbrows($result);
    $maxfleet = $user['r' . GID_R_COMPUTER] + 1;
    
    if ($nowfleet >= $maxfleet) {
        Debug("BotScout: No fleet slots available");
        return 300; // Wait 5 minutes and try again
    }
    
    // Find suitable targets
    $targets = BotFindScoutTargets($aktplanet, $range, $filters);
    
    if (empty($targets)) {
        Debug("BotScout: No suitable targets found");
        return 600; // Wait 10 minutes and try again
    }
    
    // Select a random target from the list
    $target = $targets[array_rand($targets)];
    
    // Send scout mission
    $result = BotSendScoutMission($aktplanet, $target);
    
    if ($result > 0) {
        Debug("BotScout: Scout mission sent to [" . $target['g'] . ":" . $target['s'] . ":" . $target['p'] . "]");
        return 10; // Small delay before next action
    } else {
        Debug("BotScout: Failed to send scout mission");
        return 300; // Wait 5 minutes and try again
    }
}

// Find suitable scout targets within range
function BotFindScoutTargets($origin, $range, $filters)
{
    global $GlobalUni;
    
    // Validate and constrain range
    $range = max(1, min(500, intval($range))); // Range between 1 and 500 systems
    
    $targets = array();
    $current_galaxy = $origin['g'];
    $current_system = $origin['s'];
    
    // Calculate system range
    $min_system = max(1, $current_system - $range);
    $max_system = min($GlobalUni['systems'], $current_system + $range);
    
    // Scan systems within range
    for ($system = $min_system; $system <= $max_system; $system++) {
        $system_targets = BotScanSystemForTargets($current_galaxy, $system, $filters);
        $targets = array_merge($targets, $system_targets);
        
        // Limit total targets to prevent excessive processing
        if (count($targets) > 100) {
            break;
        }
    }
    
    return $targets;
}

// Scan a specific system for suitable targets
function BotScanSystemForTargets($galaxy, $system, $filters)
{
    $targets = array();
    
    // Scan all planet positions in the system
    for ($position = 1; $position <= 15; $position++) {
        $planet = LoadPlanet($galaxy, $system, $position, 1); // Load planet
        
        if ($planet && $planet['owner_id'] > 0) {
            $user = LoadUser($planet['owner_id']);
            
            if ($user && BotIsValidScoutTarget($user, $planet, $filters)) {
                $targets[] = $planet;
            }
        }
    }
    
    return $targets;
}

// Check if a player/planet is a valid scout target based on filters
function BotIsValidScoutTarget($user, $planet, $filters)
{
    global $BotID;
    
    // Default filters if none provided
    $default_filters = array(
        'avoid_newbie' => true,
        'avoid_strong' => true,
        'target_inactive' => true,
        'min_inactive_days' => 7
    );
    
    $filters = array_merge($default_filters, $filters);
    
    // Skip own planets
    if ($user['player_id'] == $BotID) {
        return false;
    }
    
    // Skip newbie players if filter is set
    if ($filters['avoid_newbie'] && IsPlayerNewbie($user['player_id'])) {
        return false;
    }
    
    // Skip strong players if filter is set
    if ($filters['avoid_strong'] && IsPlayerStrong($user['player_id'])) {
        return false;
    }
    
    // Check for inactive players if filter is set
    if ($filters['target_inactive']) {
        $inactive_threshold = time() - ($filters['min_inactive_days'] * 24 * 60 * 60);
        if ($user['lastclick'] > $inactive_threshold) {
            return false; // Player is not inactive enough
        }
    }
    
    // Skip players in vacation mode
    if ($user['vacation']) {
        return false;
    }
    
    // Skip banned players
    if ($user['banned']) {
        return false;
    }
    
    return true;
}

// Send a scout mission to the target
function BotSendScoutMission($origin, $target)
{
    global $BotNow, $fleetmap;
    
    // Prepare fleet array (1 probe)
    $fleet = array();
    foreach ($fleetmap as $gid) {
        $fleet[$gid] = 0;
    }
    $fleet[GID_F_PROBE] = 1;
    
    // Calculate flight time and fuel consumption
    $dist = BotCalculateDistance($origin, $target);
    $seconds = BotCalculateFlightTime($dist, 1); // Speed factor 1
    $fuel = BotCalculateFuelConsumption($fleet, $dist, $seconds);
    
    // Check if we have enough deuterium
    if ($origin['d'] < $fuel) {
        Debug("BotScout: Not enough deuterium for mission (need: $fuel, have: " . $origin['d'] . ")");
        return 0;
    }
    
    // Remove probe and fuel from origin planet
    $ship_array = array();
    foreach ($fleetmap as $gid) {
        $ship_array[$gid] = 0;
    }
    $ship_array[GID_F_PROBE] = 1;
    
    AdjustShips($ship_array, $origin['planet_id'], '-');
    AdjustResources(0, 0, $fuel, $origin['planet_id'], '-');
    
    // Dispatch the fleet
    $fleet_id = DispatchFleet($fleet, $origin, $target, FTYP_SPY, $seconds, 0, 0, 0, $fuel, $BotNow);
    
    return $fleet_id;
}

// Calculate distance between two planets (simplified)
function BotCalculateDistance($origin, $target)
{
    if ($origin['g'] != $target['g']) {
        return abs($origin['g'] - $target['g']) * 20000;
    } else if ($origin['s'] != $target['s']) {
        return abs($origin['s'] - $target['s']) * 5 * 19 + 2700;
    } else if ($origin['p'] != $target['p']) {
        return abs($origin['p'] - $target['p']) * 5 + 1000;
    } else {
        return 5;
    }
}

// Calculate flight time based on distance and speed
function BotCalculateFlightTime($distance, $speed_factor)
{
    // Simplified calculation - probe speed is typically fast
    $probe_speed = 100000000; // Base probe speed
    $actual_speed = $probe_speed * $speed_factor;
    
    return max(1, round($distance / $actual_speed * 3600)); // Convert to seconds
}

// Calculate fuel consumption for the mission
function BotCalculateFuelConsumption($fleet, $distance, $flight_time)
{
    // Simplified fuel calculation for probes
    $base_consumption = 1; // Base deuterium per probe
    $distance_factor = max(1, $distance / 1000);
    
    return ceil($base_consumption * $distance_factor);
}
