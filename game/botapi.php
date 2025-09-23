<?php

require_once "id.php";
require_once "prod.php";


// Interface between bots and the engine.
// This is where all the built-in functions are located.

//------------------------------------------------------------------------------------
// Auxiliary functions

// Do nothing
function BotIdle ()
{
}

// Check that there is a strategy with the specified name.
function BotStrategyExists ($name)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    return ($result && dbrows($result) != 0);
}

// In parallel, start a new bot strategy. Return 1 if OK or 0 if the strategy could not be started.
function BotExec ($name)
{
    global $db_prefix, $BotID, $BotNow;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if ($result && dbrows($result) != 0) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['category'] === "Start" ) {
                AddBotQueue ( $BotID, $strat_id, $arr['key'], $BotNow, 0 );
                return 1;
            }
        }
        return 0;
    }
    else return 0;
}





// Bot variables.

function BotGetVar ( $var, $def_value=null )
{
    global $BotID, $BotNow;
    return GetVar ( $BotID, $var, $def_value);
}

function BotSetVar ( $var, $value )
{
    global $BotID, $BotNow;
    SetVar ( $BotID, $var, $value );
}

//------------------------------------------------------------------------------------
// Construction/demolition of buildings, management of Resouce settings

// Check if we can build the specified building on the active planet (1-yes, 0-no).
function BotCanBuild ($obj_id)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    ProdResources ( $aktplanet, $aktplanet['lastpeek'], $BotNow );
    $level = $aktplanet['b'.$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, 0 );
    return ( $text === '' );
}

// Start building on an active planet.
// Return 0 if there are not enough conditions or resources to start building. Return the number of seconds to wait until the construction is completed.
function BotBuild ($obj_id)
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $level = $aktplanet['b'.$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, 0 );
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $duration = floor (BuildDuration ( $obj_id, $level, $aktplanet['b14'], $aktplanet['b15'], $speed ));
        BuildEnque ( $user, $user['aktplanet'], $obj_id, 0, $BotNow);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $duration;
    }
    else return 0;
}

// Get a building level
function BotGetBuild ($n)
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $bot['aktplanet'] );
    return $aktplanet['b'.$n];
}

// Set the resource settings of the active planet (numbers in percentages 0-100)
function BotResourceSettings ( $last1=100, $last2=100, $last3=100, $last4=100, $last12=100, $last212=100 )
{
    global $db_prefix, $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );

    if ( $last1 < 0 ) $last1 = 0;        // Should not be < 0.
    if ( $last2 < 0 ) $last2 = 0;
    if ( $last3 < 0 ) $last3 = 0;
    if ( $last4 < 0 ) $last4 = 0;
    if ( $last12 < 0 ) $last12 = 0;
    if ( $last212 < 0 ) $last212 = 0;

    if ( $last1 > 100 ) $last1 = 100;        // Should not be > 100.
    if ( $last2 > 100 ) $last2 = 100;
    if ( $last3 > 100 ) $last3 = 100;
    if ( $last4 > 100 ) $last4 = 100;
    if ( $last12 > 100 ) $last12 = 100;
    if ( $last212 > 100 ) $last212 = 100;

    // Make multiples of 10.
    $last1 = round ($last1 / 10) * 10 / 100;
    $last2 = round ($last2 / 10) * 10 / 100;
    $last3 = round ($last3 / 10) * 10 / 100;
    $last4 = round ($last4 / 10) * 10 / 100;
    $last12 = round ($last12 / 10) * 10 / 100;
    $last212 = round ($last212 / 10) * 10 / 100;

    $planet_id = $aktplanet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET ";
    $query .= "mprod = $last1, ";
    $query .= "kprod = $last2, ";
    $query .= "dprod = $last3, ";
    $query .= "sprod = $last4, ";
    $query .= "fprod = $last12, ";
    $query .= "ssprod = $last212 ";
    $query .= " WHERE planet_id = $planet_id";
    dbquery ($query);

    UpdatePlanetActivity ( $planet_id, $BotNow );
}

// Check if energy is at or above value
function BotEnergyAbove ($energy)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $currentenergy = $aktplanet['e'];
    if ($currentenergy >= $energy){
      return true;
    } else {
      return false;
    }
}

//------------------------------------------------------------------------------------
// Fleet/defense construction (Shipyard)

function BotBuildFleet ($obj_id, $n)
{
    global $db_prefix, $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $text = AddShipyard ($user['player_id'], $user['aktplanet'], $obj_id, $n, 0 );
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $now = ShipyardLatestTime ($aktplanet, $BotNow);
        $shipyard = $aktplanet["b21"];
        $nanits = $aktplanet["b15"];
        $seconds = ShipyardDuration ( $obj_id, $shipyard, $nanits, $speed );
        AddQueue ($user['player_id'], "Shipyard", $aktplanet['planet_id'], $obj_id, $n, $now, $seconds);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

//------------------------------------------------------------------------------------
// Research

// Get the research level
function BotGetResearch ($n)
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    return $bot['r'.$n];
}

// Check - can we start research on the active planet (1-yes, 0-no)
function BotCanResearch ($obj_id)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    ProdResources ( $aktplanet, $aktplanet['lastpeek'], $BotNow );
    $level = $aktplanet['r'.$obj_id] + 1;
    $text = CanResearch ($user, $aktplanet, $obj_id, $level);
    return ($text === '' );
}

// Begin research on the active planet.
// Return 0 if there are not enough conditions or resources to start the research. Return the number of seconds to wait until the research is completed.
function BotResearch ($obj_id)
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $level = $aktplanet['r'.$obj_id] + 1;
    $text = StartResearch ($user['player_id'], $user['aktplanet'], $obj_id, 0);
    if ( $text === '' ) {
        $speed = $uni['speed'];
        if ($now == 0) $now = time ();
        $reslab = ResearchNetwork ( $user['planet_id'], $obj_id );
        $prem = PremiumStatus ($user);
        if ( $prem['technocrat'] ) $r_factor = 1.1;
        else $r_factor = 1.0;
        $seconds = ResearchDuration ( $obj_id, $level, $reslab, $speed * $r_factor);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

function BotGetShipCount()
{
    global $BotID;
    global $fleetmap;
    $ship_counts = array();

    foreach ($fleetmap as $gid) {
        $ship_counts[$gid] = 0;
    }

    $player_planets = EnumPlanets($BotID);
    foreach ($player_planets as $planet) {
        foreach ($fleetmap as $gid) {
            $ship_counts[$gid] += (int)($planet["f$gid"] ?? 0);
        }
    }

    $query = "SELECT " . implode(", ", array_map(function($gid) {
        return "ship$gid";
    }, $fleetmap)) . " FROM fleet WHERE owner_id = " . (int)$BotID;
    $result = dbquery($query);
    if ($result) {
        while ($fleet = dbarray($result)) {
            foreach ($fleetmap as $gid) {
                $ship_counts[$gid] += (int)($fleet["ship$gid"] ?? 0);
            }
        }
    }

    return $ship_counts;
}

function BotGetFleetCount($shipTypeId)
{
    $all_ship_counts = BotGetShipCount();
    return $all_ship_counts[$shipTypeId] ?? 0;
}

function BotGetBuildingEnergyCost($buildingId, $current_level)
{
    $consumption_current_level = CalculateBuildingBaseConsumption($buildingId, $current_level);
    $consumption_next_level = CalculateBuildingBaseConsumption($buildingId, $current_level + 1);

    $increase = $consumption_next_level - $consumption_current_level;

    $increase = max(0, $increase);


    return $increase;
}

function CalculateBuildingBaseConsumption($buildingId, $level) {

    $consumption = 0;

    switch ($buildingId) {
        case GID_B_METAL_MINE:
            $consumption = cons_metal($level);
            break;
        case GID_B_CRYS_MINE:
            $consumption = cons_crys($level);
            break;
        case GID_B_DEUT_SYNTH:
            $consumption = cons_deut($level);
            break;
        default:
            $consumption = 0;
            break;
    }

    return max(0, (int)round($consumption));
}

function calculateShipCosts($shipTypeId, $amount, $initial) {
    return [
        'metal'      => ($initial[$shipTypeId][0] ?? 0) * $amount,
        'crystal'    => ($initial[$shipTypeId][1] ?? 0) * $amount,
        'deuterium'  => ($initial[$shipTypeId][2] ?? 0) * $amount,
    ];
}

const FLIGHT_TIME_BUFFER = 36000;
function calculateDeuteriumBuffer($shipCount, $shipTypeId, $user, $flightTime = FLIGHT_TIME_BUFFER) {
    return FlightCons(
        $shipCount, 1, $flightTime, 10, $shipTypeId,
        $user['r115'], $user['r117'], $user['r118']
    );
}

function validateResources($planet, $costs, $deuteriumBuffer) {
    if ($planet['m'] < $costs['metal']) {
        return ['success' => false, 'error' => 'metal'];
    }
    if ($planet['k'] < $costs['crystal']) {
        return ['success' => false, 'error' => 'crystal'];
    }
    if (($planet['d'] ?? 0) < ($costs['deuterium'] + $deuteriumBuffer)) {
        return ['success' => false, 'error' => 'deuterium', 'needed' => $costs['deuterium'] + $deuteriumBuffer - $planet['d']];
    }
    return ['success' => true];
}

function calculateMaxShips($planet, $shipTypeId, $costs, $user, $amount, $currentShipCount) {
    $maxByMetal   = floor($planet['m'] / ($costs['metal'] / $amount));
    $maxByCrystal = floor($planet['k'] / ($costs['crystal'] / $amount));
    $fuelPerShip  = calculateDeuteriumBuffer(1, $shipTypeId, $user);

    $deuteriumAvailable = $planet['d'] - ($currentShipCount * $fuelPerShip);
    $deuteriumPerShip   = ($costs['deuterium'] / $amount) + $fuelPerShip;
    $maxByDeuterium     = $deuteriumPerShip > 0 ? floor($deuteriumAvailable / $deuteriumPerShip) : PHP_INT_MAX;

    return max(0, min($amount, $maxByMetal, $maxByCrystal, $maxByDeuterium));
}

function logResourceError($user, $planet, $shipTypeId, $error, $needed = 0) {
    $msg = [
        'metal'     => loca_t($shipTypeId, 'prod') . ': ' . loca('BOT_NOT_ENOUGH_METAL'),
        'crystal'   => loca_t($shipTypeId, 'prod') . ': ' . loca('BOT_NOT_ENOUGH_CRYSTAL'),
        'deuterium' => loca_t($shipTypeId, 'prod') . ': ' . loca('BOT_NOT_ENOUGH_DEUTERIUM') . ($needed > 0 ? " (Need $needed more)" : ''),
        'generic'   => loca_t($shipTypeId, 'prod') . ': ' . loca('BOT_BUILD_FLEET_NO_RESOURCES'),
    ];
    AddItem($user['player_id'], $planet['planet_id'], ITEM_MSG, '', $msg[$error] ?? $msg['generic']);
}

function BotBuildFleetAction($params) {
    global $db_prefix, $BotID, $BotNow, $GlobalUni, $initial;

    $shipTypeId = $params[0] ?? -1;
    $amount     = $params[1] ?? 0;

    if ($shipTypeId === -1 || $amount <= 0) {
        Debug("BotBuildFleetAction: Invalid shipTypeId or amount.");
        return 0;
    }

    $user   = LoadUser($BotID);
    $planet = GetPlanet($user['aktplanet']);
    $currentShipCount = $planet['f' . $shipTypeId] ?? 0;

    $costs = calculateShipCosts($shipTypeId, $amount, $initial);
    $deuteriumBuffer = calculateDeuteriumBuffer($currentShipCount + $amount, $shipTypeId, $user);

    $validation = validateResources($planet, $costs, $deuteriumBuffer);
    if (!$validation['success']) {
        logResourceError($user, $planet, $shipTypeId, $validation['error'], $validation['needed'] ?? 0);
        return 0;
    }

    $maxShips = calculateMaxShips($planet, $shipTypeId, $costs, $user, $amount, $currentShipCount);

    if ($maxShips <= 0) {
        logResourceError($user, $planet, $shipTypeId, 'generic');
        return 0;
    }

    $shipyardResult = AddShipyard($user['player_id'], $planet['planet_id'], $shipTypeId, $maxShips, 0);

    if ($shipyardResult === '') {
        $speed = $GlobalUni['speed'];
        $shipyardLevel = $planet["b21"];
        $naniteLevel   = $planet["b15"];
        $secondsToWait = ShipyardDuration($shipTypeId, $shipyardLevel, $naniteLevel, $speed);

        AddItem($user['player_id'], $planet['planet_id'], ITEM_MSG, '', loca_t($shipTypeId, 'prod') . ': ' . va(loca('BOT_BUILD_FLEET'), $maxShips));

        if ($maxShips < $amount) {
            AddItem($user['player_id'], $planet['planet_id'], ITEM_MSG, '', loca_t($shipTypeId, 'prod') . ': ' . va(loca('BOT_BUILD_FLEET_LIMITED'), $maxShips, ($validation['needed'] ?? 0)));
        }

        UpdatePlanetActivity($planet['planet_id'], $BotNow);
        return $secondsToWait;
    } else {
        Debug("BotBuildFleetAction: AddShipyard failed with message: " . $shipyardResult);
        AddItem($user['player_id'], $planet['planet_id'], ITEM_MSG, '', loca_t($shipTypeId, 'prod') . ': Shipyard Error: ' . $shipyardResult);
        return 0;
    }
}

function BotGetLastBuilt() {
    global $BotID;

    // 1. Load user data
    $user = LoadUser($BotID);
    if (!$user || !isset($user['aktplanet'])) {
        Debug("Failed to load user or aktplanet missing");
        return 0;
    }

    // 2. Get planet data
    $aktplanet = GetPlanet($user['aktplanet']);
    if (!is_array($aktplanet) || !isset($aktplanet['planet_id'])) {
        Debug("Invalid planet data for ID: " . $user['aktplanet']);
        return 0;
    }

    // 3. Check last_built in planet data first
    if (isset($aktplanet['last_built'])) {
        return $aktplanet['last_built'];
    }

    // 4. Fallback to build queue inspection
    $result = GetBuildQueue($aktplanet['planet_id']);
    if (!$result) {
        Debug("No build queue found for planet ID: " . $aktplanet['planet_id']);
        return 0;
    }
    $last_building = null;
    
    while ($row = dbarray($result)) {
        $last_building = $row;
    }

    return $last_building['tech_id'] ?? 0;
}

//------------------------------------------------------------------------------------
// Scouting functionality

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
