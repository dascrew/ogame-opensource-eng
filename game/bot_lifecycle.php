<?php

function GetBotPersonalityConfig() {
    global $BotID, $PERSONALITIES;
    
    $personality = BotGetVar('personality', 'miner');
    $subtype = BotGetVar('subtype', 'balanced');
    $base_config = $PERSONALITIES[$personality] ?? $PERSONALITIES['miner'];
    $config = $base_config['defaults'] ?? [];
    if (isset($base_config['subtypes'][$subtype])) {
        $subtype_config = $base_config['subtypes'][$subtype];
        $config = array_merge_recursive($config, $subtype_config);
    }
    $config['personality'] = $personality;
    $config['subtype'] = $subtype;
    $config['building_caps'] = $base_config['building_caps'] ?? [];
    
    return $config;
}

/**
 * (Re)initialize or randomize a bot's sleep/wake cycle, for "human-like" scheduling.
 */
function BotInitializeActivityPattern() {
    global $BotID;
    $sleep_duration_hours = rand(6, 8);
    $sleep_start_hour = rand(0, 23);
    $sleep_end_hour = ($sleep_start_hour + $sleep_duration_hours) % 24;
    BotSetVar('sleep_window_start', $sleep_start_hour);
    BotSetVar('sleep_window_end', $sleep_end_hour);
    BotSetVar('active_session_until', 0);

    Debug("BotInitializeActivityPattern: Bot {$BotID} sleep window {$sleep_start_hour}:00-{$sleep_end_hour}:00");
}

/**
 * Returns true if the bot is currently in its scheduled "asleep" state.
 */
function BotIsAsleep() {
    $start_hour = BotGetVar('sleep_window_start', 22);
    $end_hour = BotGetVar('sleep_window_end', 6);
    $current_hour = (int)date('G');
    if ($start_hour > $end_hour) {
        return $current_hour >= $start_hour || $current_hour < $end_hour;
    }
    return $current_hour >= $start_hour && $current_hour < $end_hour;
}

function BotHasResearchActions() {
    $all_research = GetResearchIds();
    foreach ($all_research as $research_id) {
        if (BotCanResearch($research_id)) {
            return true;
        }
    }
    return false;
}

function BotHasBuildActions($aktplanet) {
    $all_buildings = GetBuildingIds();
    $capped_buildings = BotGetBuildingsAtCap($aktplanet);

    if (count($capped_buildings) >= count($all_buildings)) {
        return false;
    }

    $buildable_buildings = array_diff($all_buildings, $capped_buildings);
    foreach ($buildable_buildings as $building_id) {
        if (BotCanBuild($building_id)) {
            return true;
        }
    }
    return false;
}

function BotHasFleetActions() {
    $config = GetBotPersonalityConfig();
    $ships = $config['ship_ratio'] ?? [];
    $ship_ids = array_keys($ships);
    foreach ($ship_ids as $ship_id) {
        if (BotCanBuildFleet($ship_id)) {
            return true;
        }
    }
    return false;
}
/**
 * Check if bot has meaningful actions available based on personality
 */
function BotHasMeaningfulActions() {
    global $BotID;
    $personality = BotGetVar('personality', 'miner');
    $user = LoadUser($BotID);
    $aktplanet = GetPlanet($user['aktplanet']);
    
    switch ($personality) {
        case 'miner':
            if (BotHasBuildActions($aktplanet)) {
                return true;
            }
            if (BotHasResearchActions()) {
                return true;
            }
            if (BotHasFleetActions()) {
                return true;
           }            
            return false;

        case 'fleeter':
            // Fleeters care about combat, scouting, fleet management
            if (BotHasBuildActions($aktplanet)) {
                return true;
            }
            if (BotHasResearchActions()) {
                return true;
            }
            if (BotHasFleetActions()) {
                return true;
            }
            return false;

        case 'turtle':
            // Turtles focus on defense and steady development
            if (BotHasBuildActions($aktplanet)) {
                return true;
            }
            if (BotHasResearchActions()) {
                return true;
            }
            if (BotHasFleetActions()) {
                return true;
            }
            return false;

        case 'trader':
            // Traders focus on resource optimization and trade
            if (BotHasBuildActions($aktplanet)) {
                return true;
            }
            if (BotHasResearchActions()) {
                return true;
            }
            if (BotHasFleetActions()) {
                return true;
            }
            return false;

        default:
            return false;
    }
}

/**
 * Start an active session (no fixed end time)
 */
function BotStartActiveSession() {
    global $BotID;
    $personality = BotGetVar('personality', 'miner');
    
    // Set session active flag (1 = active, 0 = inactive)
    BotSetVar('active_session_until', 1);
    BotSetVar('session_start_time', time());
    
    Debug("BotStartActiveSession: {$personality} bot started active session.");
}

/**
 * End active session when no meaningful actions remain
 */
function BotEndActiveSession($reason = 'no actions') {
    global $BotID;
    
    BotSetVar('active_session_until', 0);
    BotSetVar('last_session_end', time());
    
    $session_start = BotGetVar('session_start_time', time());
    $session_duration = time() - $session_start;
    
    Debug("BotEndActiveSession: Session ended ({$reason}) after {$session_duration}s.");
}

/**
 * Check if bot is currently in an active session
 */
function BotIsInActiveSession() {
    $session_active = BotGetVar('active_session_until', 0);
    
    if ($session_active == 1) {
        // Check if bot still has meaningful actions
        if (!BotHasMeaningfulActions()) {
            BotEndActiveSession('no meaningful actions');
            return false;
        }
        
        // Check personality-based session limits to prevent infinite sessions
        $personality = BotGetVar('personality', 'miner');
        $session_start = BotGetVar('session_start_time', time());
        $session_duration = time() - $session_start;
        
        switch ($personality) {
            case 'miner':  $max_duration = 1800; break;
            case 'fleeter': $max_duration = 7200; break;
            case 'turtle': $max_duration = 3600; break;
            case 'trader': $max_duration = 4800; break;
            default: $max_duration = 3600; break;
        }
        
        if ($session_duration > $max_duration) {
            BotEndActiveSession('max duration reached');
            return false;
        }
        
        return true;
    }
    
    return false;
}

/**
 * Wake bot from idle state due to external events (NOT from sleep)
 */
function BotWakeFromEvent($event_type, $event_data = []) {
    global $BotID;
    
    // NEVER wake from sleep - sleep is absolute
    if (BotIsAsleep()) {
        Debug("BotWakeFromEvent: Bot asleep, ignoring {$event_type} event.");
        return false;
    }
    
    // Only wake if not already in session
    if (BotIsInActiveSession()) {
        Debug("BotWakeFromEvent: Bot already active, ignoring {$event_type} event.");
        return false;
    }
    
    $should_wake = false;
    
    switch ($event_type) {
        case 'incoming_attack':
            $should_wake = BotShouldRespondToAttack($event_data);
            break;
            
        case 'fleet_return':
            $should_wake = BotShouldProcessFleetReturn($event_data);
            break;
            
        case 'resources_full':
            $should_wake = BotShouldManageResources($event_data);
            break;
            
        case 'alliance_request':
            $should_wake = BotShouldRespondToAlliance($event_data);
            break;
            
        case 'construction_complete':
            $should_wake = BotShouldContinueBuilding($event_data);
            break;
    }
    
    if ($should_wake) {
        BotStartActiveSession();
        Debug("BotWakeFromEvent: Woke from {$event_type} event.");
        return true;
    }
    
    Debug("BotWakeFromEvent: Ignored {$event_type} event.");
    return false;
}

/**
 * Calculate next action time with event-driven and action-driven logic
 */
function BotGetNextActionTime() {
    if (BotIsAsleep()) {
        $sleep_center = BotGetVar('sleep_center_hour', 2);
        $wake_hour = ($sleep_center + 4) % 24;
        $current_hour = (int)date('G');
        $current_minute = (int)date('i');
        $current_second = (int)date('s');
        
        if ($wake_hour <= $current_hour) {
            $hours_to_wait = (24 - $current_hour) + $wake_hour;
        } else {
            $hours_to_wait = $wake_hour - $current_hour;
        }
        
        $seconds_to_wait = ($hours_to_wait * 3600) - ($current_minute * 60) - $current_second;
        BotSetVar('active_session_until', 0);
        
        Debug("BotGetNextActionTime: Bot asleep. Wake at {$wake_hour}:00 in {$seconds_to_wait}s.");
        return max(60, $seconds_to_wait);
    }

    if (BotIsInActiveSession()) {
        $delay = rand(60, 300);  // 1-5 minutes between actions during session
        Debug("BotGetNextActionTime: In active session. Next action in {$delay}s.");
        return $delay;
    }

    // Bot is awake but idle - check if should start session
    if (BotHasMeaningfulActions()) {
        $personality = BotGetVar('personality', 'miner');
        
        // Personality-based session start probability
        switch ($personality) {
            case 'fleeter': $session_chance = 70; break;
            case 'miner':   $session_chance = 40; break;
            case 'turtle':  $session_chance = 50; break;
            case 'trader':  $session_chance = 60; break;
            default:        $session_chance = 45; break;
        }
        
        if (rand(1, 100) <= $session_chance) {
            BotStartActiveSession();
            $delay = rand(60, 300);
            Debug("BotGetNextActionTime: Starting session, next action {$delay}s.");
            return $delay;
        }
    }
    
    // Idle time based on personality
    $personality = BotGetVar('personality', 'miner');
    switch ($personality) {
        case 'miner':   $idle_time = rand(1800, 3600); break;  // 30-60 min idle
        case 'fleeter': $idle_time = rand(600, 1200); break;   // 10-20 min idle  
        case 'turtle':  $idle_time = rand(1200, 2400); break;  // 20-40 min idle
        case 'trader':  $idle_time = rand(900, 1800); break;   // 15-30 min idle
        default:        $idle_time = rand(900, 1800); break;
    }
    
    Debug("BotGetNextActionTime: {$personality} idle for {$idle_time}s.");
    return $idle_time;
}

function BotIsOnline() {
    return !BotIsAsleep();
}

// Event response helper functions
function BotShouldRespondToAttack($event_data) {
    $personality = BotGetVar('personality', 'miner');
    return in_array($personality, ['fleeter', 'turtle']); // Combat-oriented personalities
}

function BotShouldProcessFleetReturn($event_data) {
    return true; // Always process returning fleets
}

function BotShouldManageResources($event_data) {
    $personality = BotGetVar('personality', 'miner');
    return in_array($personality, ['miner', 'trader']); // Resource-focused personalities
}

function BotShouldRespondToAlliance($event_data) {
    return rand(1, 100) <= 60; // 60% chance to respond to alliance events
}

function BotShouldContinueBuilding($event_data) {
    return BotCanBuild() || BotCanResearch(); // Continue if more building/research available
}
