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

function BotGetVar() {
    $args = func_get_args();
    $num_args = func_num_args();
    
    if ($num_args >= 2 && is_numeric($args[0]) && $args[0] > 0) {
        $owner_id = $args[0];
        $var_name = $args[1];
        $default_value = $args[2] ?? null;
        return BotGetVarNew($owner_id, $var_name, $default_value);
    } else {
        //use legacy
        global $BotID;
        $var = $args[0];
        $def_value = $args[1] ?? null;
        
        if (!isset($BotID)) {
            return $def_value;
        }
        
        return BotGetVarNew($BotID, $var, $def_value);
    }
}

function BotSetVar() {
    $args = func_get_args();
    $num_args = func_num_args();
    
    if ($num_args >= 2 && is_numeric($args[0]) && $args[0] > 0) {
        $owner_id = $args[0];
        $var_name = $args[1];
        $value = $args[2];
        return BotSetVarNew($owner_id, $var_name, $value);
    } else { 
        //use legacy
        global $BotID;
        $var = $args[0];
        $value = $args[1];
        
        if (!isset($BotID)) {
            return false;
        }
        
        return BotSetVarNew($BotID, $var, $value);
    }
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
        UpdateLastClick($BotID);
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
        UpdateLastClick($BotID);
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
        UpdateLastClick($BotID);
        return $seconds;
    }
    else return 0;
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

/**
 * @param int $owner_id The bot's owner ID
 * @param string $var_name The variable name to delete
 * @return bool True on success, false on failure
 */
function BotDeleteVar($owner_id, $var_name) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotDeleteVar: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotDeleteVar: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    $var_escaped = mysqli_real_escape_string($GLOBALS['db_connect'], $var_name);
    $query = "DELETE FROM " . $db_prefix . "botvars WHERE var = '" . $var_escaped . "' AND owner_id = " . intval($owner_id);
    
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotDeleteVar: Failed to delete variable '$var_name' for owner_id: $owner_id");
        return false;
    }
    Debug("BotDeleteVar: Successfully deleted variable '$var_name' for owner_id: $owner_id");
    return true;
}


 // Get all variables for a specific bot as an associative array
function BotGetAllVars($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetAllVars: Invalid owner_id provided: " . $owner_id);
        return array();
    }
    $query = "SELECT var, value FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotGetAllVars: Failed to query variables for owner_id: $owner_id");
        return array();
    }
    $variables = array();
    
    if (dbrows($result) > 0) {
        while ($row = dbarray($result)) {
            if ($row !== false) {
                $variables[$row['var']] = $row['value'];
            }
        }
    }
    
    Debug("BotGetAllVars: Retrieved " . count($variables) . " variables for owner_id: $owner_id");
    return $variables;
}

/**
 * Enhanced wrapper for BotGetVar that adds validation while using existing function
 * 
 * @param int $owner_id The bot's owner ID  
 * @param string $var_name The variable name to retrieve
 * @param mixed $default_value Default value if variable doesn't exist
 * @return mixed The variable value or default value
 */
function BotGetVarNew($owner_id, $var_name, $default_value = null) {
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetVar: Invalid owner_id provided: " . $owner_id);
        return $default_value;
    }
    
    if (empty($var_name)) {
        Debug("BotGetVar: Empty var_name provided for owner_id: " . $owner_id);
        return $default_value;
    }
    
    // Use existing GetVar function
    return GetVar($owner_id, $var_name, $default_value);
}

/**
 * Enhanced wrapper for BotSetVar that adds validation while using existing function
 * 
 * @param int $owner_id The bot's owner ID
 * @param string $var_name The variable name to set  
 * @param mixed $value The value to store
 * @return bool True on success, false on failure
 */
function BotSetVarNew($owner_id, $var_name, $value) {
    // Input validation
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotSetVar: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotSetVar: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    
    if (is_array($value) || is_object($value)) {
        $value = serialize($value);
    } elseif (is_bool($value)) {
        $value = $value ? '1' : '0';
    }
    SetVar($owner_id, $var_name, $value);
    
    Debug("BotSetVar: Set variable '$var_name' for owner_id: $owner_id");
    return true;
}

/**
 * Check if a bot variable exists
 * Uses existing database functions
 * 
 * @param int $owner_id The bot's owner ID
 * @param string $var_name The variable name to check
 * @return bool True if variable exists, false otherwise
 */
function BotVarExists($owner_id, $var_name) {
    global $db_prefix;
    
    // Input validation
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotVarExists: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotVarExists: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    
    // Use existing dbquery function with proper escaping
    $var_escaped = mysqli_real_escape_string($GLOBALS['db_connect'], $var_name);
    $query = "SELECT id FROM " . $db_prefix . "botvars WHERE var = '" . $var_escaped . "' AND owner_id = " . intval($owner_id) . " LIMIT 1";
    
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotVarExists: Failed to check variable '$var_name' for owner_id: $owner_id");
        return false;
    }
    
    return dbrows($result) > 0;
}

/**
 * Get count of variables for a specific bot
 * Uses existing database functions
 * 
 * @param int $owner_id The bot's owner ID
 * @return int Number of variables for the bot, -1 on error
 */
function BotGetVarCount($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetVarCount: Invalid owner_id provided: " . $owner_id);
        return -1;
    }
    
    $query = "SELECT COUNT(*) as var_count FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotGetVarCount: Failed to count variables for owner_id: $owner_id");
        return -1;
    }
    
    if (dbrows($result) > 0) {
        $row = dbarray($result);
        if ($row !== false) {
            return intval($row['var_count']);
        }
    }
    
    return 0;
}

/**
 * Delete all variables for a specific bot
 * 
 * @param int $owner_id The bot's owner ID
 * @return bool True on success, false on failure
 */
function BotDeleteAllVars($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotDeleteAllVars: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    $query = "DELETE FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotDeleteAllVars: Failed to delete all variables for owner_id: $owner_id");
        return false;
    }
    
    Debug("BotDeleteAllVars: Successfully deleted all variables for owner_id: $owner_id");
    return true;
}

/**
 * Copy all variables from one bot to another
 * Uses existing functions for consistency
 * 
 * @param int $source_owner_id Source bot's owner ID
 * @param int $target_owner_id Target bot's owner ID
 * @param bool $overwrite Whether to overwrite existing variables in target
 * @return bool True on success, false on failure
 */
function BotCopyVars($source_owner_id, $target_owner_id, $overwrite = false) {
    // Input validation
    if (!is_numeric($source_owner_id) || $source_owner_id <= 0) {
        Debug("BotCopyVars: Invalid source_owner_id provided: " . $source_owner_id);
        return false;
    }
    
    if (!is_numeric($target_owner_id) || $target_owner_id <= 0) {
        Debug("BotCopyVars: Invalid target_owner_id provided: " . $target_owner_id);
        return false;
    }
    
    // Get all variables from source bot
    $source_vars = BotGetAllVars($source_owner_id);
    
    if (empty($source_vars)) {
        Debug("BotCopyVars: No variables found for source owner_id: $source_owner_id");
        return true; // Not an error, just nothing to copy
    }
    
    $copied_count = 0;
    
    // Copy each variable to target bot
    foreach ($source_vars as $var_name => $value) {
        // Check if variable exists in target if not overwriting
        if (!$overwrite && BotVarExists($target_owner_id, $var_name)) {
            Debug("BotCopyVars: Skipping existing variable '$var_name' for target owner_id: $target_owner_id");
            continue;
        }
        
        // Use existing SetVar function
        SetVar($target_owner_id, $var_name, $value);
        $copied_count++;
    }
    
    Debug("BotCopyVars: Copied $copied_count variables from owner_id $source_owner_id to $target_owner_id");
    return true;
}

/**
 * Validate that required bot variables exist
 * 
 * @param int $owner_id The bot's owner ID
 * @param array $required_vars Array of required variable names
 * @return array Array of missing variable names, empty if all exist
 */
function BotValidateRequiredVars($owner_id, $required_vars) {
    if (!is_array($required_vars)) {
        Debug("BotValidateRequiredVars: required_vars must be an array");
        return array();
    }
    
    $missing_vars = array();
    
    foreach ($required_vars as $var_name) {
        if (!BotVarExists($owner_id, $var_name)) {
            $missing_vars[] = $var_name;
        }
    }
    
    if (!empty($missing_vars)) {
        Debug("BotValidateRequiredVars: Missing variables for owner_id $owner_id: " . implode(', ', $missing_vars));
    }
    
    return $missing_vars;
}

/**
 * Calculate how many ships to build based on resources, personality, and game state
 *
 * @param int $ship_id Ship type ID
 * @param array $config Personality configuration
 * @return int Number of ships to build
 */
function CalculateShipBuildAmount($ship_id, $config) {
    global $BotID, $initial;
    
    // Load current game state
    $user = LoadUser($BotID);
    $planet = GetPlanet($user['aktplanet']);
    
    // Get current ship count for this type
    $current_ships = BotGetFleetCount($ship_id);
    
    // Calculate base amount from personality weights
    $ship_weight = $config['ship_weights'][$ship_id] ?? 1;
    
    // Convert weight to base build amount using personality-specific scaling
    $base_amount = calculateBaseAmountFromWeight($ship_weight, $config['personality'], $ship_id, $current_ships);
    
    // Apply subtype modifiers from your existing system
    $subtype_modifiers = array(
        'speed' => 1.5, 'smasher' => 0.8, 'swarm' => 2.0, 'balanced' => 1.0,
        'pure' => 0.6, 'trader' => 1.2, 'research' => 0.4, 'fortress' => 0.3,
        'merchant' => 1.3, 'opportunist' => 1.4, 'pirate' => 1.6
    );
    
    $subtype = $config['subtype'];
    $modifier = $subtype_modifiers[$subtype] ?? 1.0;
    $adjusted_amount = (int)($base_amount * $modifier);
    
    // Calculate maximum affordable based on resources
    if (isset($initial[$ship_id])) {
        $costs = calculateShipCosts($ship_id, $adjusted_amount, $initial);
        $deuterium_buffer = calculateDeuteriumBuffer($current_ships + $adjusted_amount, $ship_id, $user);
        
        $max_by_resources = calculateMaxShips($planet, $ship_id, $costs, $user, $adjusted_amount, $current_ships);
        $adjusted_amount = min($adjusted_amount, $max_by_resources);
    }
    
    // Apply minimum build thresholds
    $adjusted_amount = applyMinimumBuildThresholds($adjusted_amount, $ship_id);
    
    // Final validation
    if ($adjusted_amount > 0 && !BotCanBuildShip($ship_id)) {
        return 0;
    }
    
    return max(0, $adjusted_amount);
}

/**
 * Convert personality weight to actual build amount with dynamic scaling
 * Uses personality-specific scaling factors and diminishing returns
 */
function calculateBaseAmountFromWeight($weight, $personality, $ship_id, $current_ships) {
    // Personality-specific scaling factors
    $personality_scales = array(
        'fleeter' => array(
            'combat_ships' => array(204, 205, 206, 207, 213, 215),
            'scale' => 0.8
        ),
        'miner' => array(
            'cargo_ships' => array(202, 203, 209),
            'scale' => 1.2,
            'production_scaling' => true  // Miners scale with production needs
        ),
        'turtle' => array(
            'utility_ships' => array(210, 212),
            'scale' => 0.4
        ),
        'trader' => array(
            'cargo_ships' => array(202, 203),
            'scale' => 1.0,
            'production_scaling' => true  // Traders scale with economic activity
        ),
        'raider' => array(
            'raid_ships' => array(202, 204, 205),
            'scale' => 0.9
        )
    );
    
    $scale = 0.5; // Default scale
    $use_production_scaling = false;
    
    if (isset($personality_scales[$personality])) {
        $personality_data = $personality_scales[$personality];
        
        // Check if this ship type gets special scaling for this personality
        foreach ($personality_data as $ship_category => $ship_ids) {
            if (is_array($ship_ids) && in_array($ship_id, $ship_ids)) {
                $scale = $personality_data['scale'];
                $use_production_scaling = $personality_data['production_scaling'] ?? false;
                break;
            }
        }
    }
    
    // Apply diminishing returns based on current fleet size
    $diminishing_factor = calculateDiminishingReturns($current_ships, $ship_id, $config);
    
    // For miners and traders, scale cargo ships with production capacity
    if ($use_production_scaling && in_array($ship_id, [202, 203])) {
        $production_factor = calculateProductionScaling($personality);
        $scale *= $production_factor;
    }
    
    // Convert weight (0-45 range) to build amount with diminishing returns
    $base_amount = max(1, (int)($weight * $scale * $diminishing_factor));
    
    return $base_amount;
}

/**
 * Calculate diminishing returns based on current fleet size
 */
function calculateDiminishingReturns($current_ships, $ship_id, $config) {
    $ship_weight = $config['ship_weights'][$ship_id] ?? 1;
    
    // Define weight categories for different diminishing curves
    if ($ship_weight >= 25) {
        // High priority ships - very slow diminishing
        $threshold = $ship_weight * 2;
        $rate = 0.008;
    } elseif ($ship_weight >= 15) {
        // Medium priority ships - moderate diminishing
        $threshold = $ship_weight * 1.5;
        $rate = 0.015;
    } elseif ($ship_weight >= 8) {
        // Low priority ships - faster diminishing
        $threshold = $ship_weight;
        $rate = 0.025;
    } else {
        // Very low priority ships - rapid diminishing
        $threshold = max(3, $ship_weight * 0.5);
        $rate = 0.04;
    }
    
    if ($current_ships > $threshold) {
        $excess = $current_ships - $threshold;
        return max(0.1, 1.0 - ($excess * $rate));
    }
    
    return 1.0;
}

/**
 * Calculate production scaling factor
 */
function calculateProductionScaling($personality) {
    global $BotID;
    
    if (!in_array($personality, ['miner', 'trader'])) {
        return 1.0;
    }
    
    $user = LoadUser($BotID);
    $planet = GetPlanet($user['aktplanet']);
    $total_production = ($planet['b1'] ?? 0) + ($planet['b2'] ?? 0) + ($planet['b3'] ?? 0);
    return max(0.8, min(1.5, 0.8 + ($total_production - 20) * 0.0175));
}

/**
 * Apply minimum build thresholds to avoid inefficient single-ship builds
 */
function applyMinimumBuildThresholds($amount, $ship_id) {
    $min_amounts = array(
        202 => 5,   // Small Cargo - build in batches
        203 => 3,   // Large Cargo
        204 => 10,  // Light Fighter - swarm units
        205 => 5,   // Heavy Fighter
        210 => 5,   // Probes - build several
        212 => 10   // Satellites - build many
    );
    
    $min_amount = $min_amounts[$ship_id] ?? 1;
    
    if ($amount < $min_amount && $amount > 0) {
        return 0; // Don't build if we can't meet minimum
    }
    
    return $amount;
}

function BotCanBuildShip($ship_id) {
    global $BotID, $initial;
    
    $user = LoadUser($BotID);
    if (!$user) {
        return false;
    }
    
    $planet = GetPlanet($user['aktplanet']);
    if (!$planet) {
        return false;
    }
    
    // Check if shipyard exists
    if (($planet['b21'] ?? 0) < 1) {
        return false;
    }
    
    // Check if we have the ship definition
    if (!isset($initial[$ship_id])) {
        return false;
    }
    
    // Check basic resource requirements for 1 ship
    $costs = calculateShipCosts($ship_id, 1, $initial);
    $validation = validateResources($planet, $costs, 0);
    
    if (!$validation['success']) {
        return false;
    }
    
    // Add technology requirement checks here if needed
    // Example: if ($ship_id == 215 && $user['r118'] < 7) return false; // Battlecruiser needs Hyperspace Drive 7
    
    return true;
}

/**
 * Get comprehensive bot information 
 */
function GetBotInfo($bot_id) {
    // Validate input and check if it's a bot
    if (!is_numeric($bot_id) || $bot_id <= 0 || !IsBot($bot_id)) {
        Debug("GetBotInfo: Invalid bot_id or not a bot: " . $bot_id);
        return null;
    }
    
    // Load user and planet data using existing functions
    $user = LoadUser($bot_id);
    if (!$user) {
        Debug("GetBotInfo: Failed to load user data for bot $bot_id");
        return null;
    }
    
    $planet = GetPlanet($user['aktplanet']);
    if (!$planet) {
        Debug("GetBotInfo: Failed to load planet data for bot $bot_id");
        return null;
    }
    
    // Get bot personality using existing functions
    $personality = BotGetVarNew($bot_id, 'personality', 'unknown');
    $subtype = BotGetVarNew($bot_id, 'subtype', 'unknown');
    
    // Calculate building and research totals using existing functions
    $total_buildings = 0;
    $key_buildings = array();
    for ($i = 1; $i <= 43; $i++) {
        $level = BotGetBuild($i);
        $total_buildings += $level;
        
        // Store key buildings
        $building_names = array(
            1 => 'metal_mine', 2 => 'crystal_mine', 3 => 'deuterium_synth', 4 => 'solar_plant',
            14 => 'robotics_factory', 15 => 'nanite_factory', 21 => 'shipyard', 22 => 'research_lab', 31 => 'missile_silo'
        );
        if (isset($building_names[$i])) {
            $key_buildings[$building_names[$i]] = $level;
        }
    }
    
    $total_research = 0;
    $key_research = array();
    for ($i = 106; $i <= 124; $i++) {
        $level = BotGetResearch($i);
        $total_research += $level;
        
        // Store key research
        $research_names = array(
            106 => 'espionage', 108 => 'computer', 109 => 'weapons', 110 => 'shielding', 111 => 'armor',
            113 => 'energy', 115 => 'combustion_drive', 117 => 'impulse_drive', 118 => 'hyperspace_drive'
        );
        if (isset($research_names[$i])) {
            $key_research[$research_names[$i]] = $level;
        }
    }
    
    // Get fleet information using existing function
    $ship_types = array(202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215);
    $fleet_composition = array();
    $total_ships = 0;
    
    foreach ($ship_types as $ship_id) {
        $count = BotGetFleetCount($ship_id);
        if ($count > 0) {
            $fleet_composition[$ship_id] = $count;
            $total_ships += $count;
        }
    }
    
    // Get all bot variables
    $bot_vars = BotGetAllVars($bot_id);
    
    // Calculate activity level
    $last_activity = $user['lastclick'] ?? 0;
    $activity_level = calculateActivityLevel($last_activity);
    
    // Compile comprehensive bot information
    return array(
        // Basic Information
        'bot_id' => $bot_id,
        'name' => $user['name'],
        'email' => $user['email'],
        'created' => $user['reg_time'],
        'last_activity' => $last_activity,
        'is_active' => true, // Already confirmed by IsBot()
        
        // Personality & Configuration
        'personality' => $personality,
        'subtype' => $subtype,
        'config_vars_count' => count($bot_vars),
        'all_vars' => $bot_vars,
        
        // Game Statistics
        'total_points' => $user['total_points'] ?? 0,
        'total_rank' => $user['total_rank'] ?? 0,
        'current_planet' => $user['aktplanet'],
        'planet_name' => $planet['name'] ?? 'Unknown',
        
        // Progress Summary
        'total_building_levels' => $total_buildings,
        'total_research_levels' => $total_research,
        'total_fleet_count' => $total_ships,
        
        // Current Resources (using your field names)
        'resources' => array(
            'metal' => $planet['m'] ?? 0,
            'crystal' => $planet['k'] ?? 0,
            'deuterium' => $planet['d'] ?? 0,
            'energy' => $planet['e'] ?? 0
        ),
        
        // Fleet Composition
        'fleet_composition' => $fleet_composition,
        
        // Key Building Levels
        'key_buildings' => $key_buildings,
        
        // Key Research Levels  
        'key_research' => $key_research,
        
        // Status Information
        'status' => array(
            'online' => true,
            'last_seen' => date('Y-m-d H:i:s', $last_activity),
            'activity_level' => $activity_level,
            'bot_age_days' => round((time() - $user['reg_time']) / 86400, 1)
        )
    );
}

/**
 * Calculate bot activity level based on last activity
 */
function calculateActivityLevel($last_activity) {
    $time_diff = time() - $last_activity;
    
    if ($time_diff < 300) return 'very_active';      // < 5 minutes
    if ($time_diff < 1800) return 'active';         // < 30 minutes  
    if ($time_diff < 7200) return 'moderate';       // < 2 hours
    if ($time_diff < 86400) return 'low';           // < 24 hours
    return 'inactive';                               // > 24 hours
}

function UpdateBotStatus($bot_id, $status) {
    if (!IsBot($bot_id)) {
        Debug("UpdateBotStatus: Player $bot_id is not a bot");
        return false;
    }
    
    BotSetVarNew($bot_id, 'manual_status', $status);
    BotSetVarNew($bot_id, 'status_updated', time());
    
    Debug("UpdateBotStatus: Updated status for bot $bot_id to $status");
    return true;
}

function GetActiveBots() {
    global $db_prefix;
    
    $query = "SELECT DISTINCT owner_id FROM " . $db_prefix . "queue WHERE type = 'AI'";
    $result = dbquery($query);
    
    $active_bots = array();
    if ($result && dbrows($result) > 0) {
        while ($row = dbarray($result)) {
            $bot_info = GetBotInfo($row['owner_id']);
            if ($bot_info) {
                $active_bots[] = $bot_info;
            }
        }
    }
    
    Debug("GetActiveBots: Found " . count($active_bots) . " active bots");
    return $active_bots;
}

function BotGetFilteredRankings($category = 'ressources', $limit = 100) {
    global $db_prefix;

    // Map
    $rank_column_map = array(
        'ressources' => 'place1',
        'fleet' => 'place2',
        'research' => 'place3',
        'mine' => 'place4'
    );
    $order_by = $rank_column_map[$category] ?? 'place1';

    // Get a list of all active bot IDs from the queue.
    $bot_ids_query = dbquery("SELECT DISTINCT owner_id FROM ".$db_prefix."queue WHERE type = 'AI'");
    $bot_ids = array();
    while ($row = dbarray($bot_ids_query)) {
        $bot_ids[] = $row['owner_id'];
    }

    if (empty($bot_ids)) {
        return array(); // No active bots found.
    }

    // Fetch the ranked user data ONLY for players who are bots.
    $query = "SELECT player_id, oname, ally_id, score1, score2, score3, score4, place1, place2, place3, place4 
              FROM ".$db_prefix."users 
              WHERE player_id IN (".implode(",", $bot_ids).") 
              ORDER BY $order_by ASC 
              LIMIT $limit";
              
    $result = dbquery($query);
    
    $bot_rankings = array();
    while ($row = dbarray($result)) {
        // Add the bot's personality to the data for easy viewing.
        $row['personality'] = BotGetVar('personality', 'unknown', $row['player_id']);
        $bot_rankings[] = $row;
    }

    Debug("BotGetFilteredRankings: Fetched top $limit bots for category '$category'");
    return $bot_rankings;
}

function BotCompareTwoSimple($bot1_id, $bot2_id) {
    // Load the user data which contains the ranks.
    $bot1_data = LoadUser($bot1_id);
    $bot2_data = LoadUser($bot2_id);

    if (!$bot1_data || !$bot2_data) {
        return null;
    }

    $comparison = array(
        'bot1' => array(
            'name' => $bot1_data['oname'],
            'personality' => BotGetVar('personality', 'unknown', $bot1_id),
            'ranks' => array(
                'overall' => $bot1_data['place1'],
                'fleet' => $bot1_data['place2'],
                'research' => $bot1_data['place3'],
                'mine' => $bot1_data['place4']
            )
        ),
        'bot2' => array(
            'name' => $bot2_data['oname'],
            'personality' => BotGetVar('personality', 'unknown', $bot2_id),
            'ranks' => array(
                'overall' => $bot2_data['place1'],
                'fleet' => $bot2_data['place2'],
                'research' => $bot2_data['place3'],
                'mine' => $bot2_data['place4']
            )
        )
    );

    return $comparison;
}

function BotCheckAllianceFit($bot_id, $requirements) {
    $bot_data = LoadUser($bot_id);
    if (!$bot_data) return false;

    // Check personality requirement
    if (isset($requirements['personality'])) {
        $personality = BotGetVar('personality', 'unknown', $bot_id);
        if ($personality !== $requirements['personality']) {
            return false;
        }
    }

    // Check rank requirements (lower rank is better)
    if (isset($requirements['overall_rank_max']) && $bot_data['place1'] > $requirements['overall_rank_max']) {
        return false;
    }
    if (isset($requirements['fleet_rank_max']) && $bot_data['place2'] > $requirements['fleet_rank_max']) {
        return false;
    }
    if (isset($requirements['research_rank_max']) && $bot_data['place3'] > $requirements['research_rank_max']) {
        return false;
    }
    if (isset($requirements['mine_rank_max']) && $bot_data['place4'] > $requirements['mine_rank_max']) {
        return false;
    }

    return true; // All requirements passed
}

function BotInitializeActivityPattern() {
    global $BotID;

    $sleep_duration_hours = rand(6, 8);
    $sleep_start_hour = rand(0, 23); 
    $sleep_end_hour = ($sleep_start_hour + $sleep_duration_hours) % 24;
    BotSetVar('sleep_window_start', $sleep_start_hour);
    BotSetVar('sleep_window_end', $sleep_end_hour);
    BotSetVar('active_session_until', 0);

    Debug("BotInitializeActivityPattern: Bot {$BotID} sleep window set from {$sleep_start_hour}:00 to {$sleep_end_hour}:00.");
}

/**
 * Checks if the bot is currently within its designated sleep window.
 */
function BotIsAsleep() {
    $start_hour = BotGetVar('sleep_window_start', 22); 
    $end_hour = BotGetVar('sleep_window_end', 6);
    $current_hour = (int)date('G');

    if ($start_hour > $end_hour) {
        return ($current_hour >= $start_hour || $current_hour < $end_hour);
    }
    else {
        return ($current_hour >= $start_hour && $current_hour < $end_hour);
    }
}

/**
 * Checks if the bot is currently in an "active session".
 */
function BotIsInActiveSession() {
    $session_end_time = BotGetVar('active_session_until', 0);
    return time() < $session_end_time;
}

/**
 * Calculates the time until the next bot action
 */
function BotGetNextActionTime() {
    if (BotIsAsleep()) {
        $start_hour = (int)BotGetVar('sleep_window_start', 22);
        $end_hour = (int)BotGetVar('sleep_window_end', 6);
        $current_hour = (int)date('G');
        $current_minute = (int)date('i');
        $current_second = (int)date('s');

        if ($start_hour > $end_hour) { // Crosses midnight
            $hours_to_wait = ($end_hour - $current_hour + 24) % 24;
        } else {
            $hours_to_wait = $end_hour - $current_hour;
        }

        $seconds_to_wait = ($hours_to_wait * 3600) - ($current_minute * 60) - $current_second;
        $seconds_to_wait += rand(60, 600); 
        BotSetVar('active_session_until', 0);

        Debug("BotGetNextActionTime: Bot is asleep. Waiting {$seconds_to_wait} seconds until window ends.");
        return max(60, $seconds_to_wait); 
    }
    
    if (BotIsInActiveSession()) {
        $delay = rand(60, 300); 
        Debug("BotGetNextActionTime: In active session. Next action in {$delay} seconds.");
        return $delay;
    }

    $config = GetBotPersonalityConfig();
    if (!$config) {
        Debug("BotGetNextActionTime: Could not load personality config. Defaulting to 15 min wait.");
        return 900;
    }
    $activity_pattern = $config['activity_pattern'];
    $activity_pattern = BotModifyActivityPattern($activity_pattern);
    $start_session_chance = 30;
    if (rand(1, 100) <= $start_session_chance) {
        $session_duration = rand(1800, 7200); 
        BotSetVar('active_session_until', time() + $session_duration);
        
        $delay = rand(60, 300); 
        Debug("BotGetNextActionTime: Starting new active session for {$session_duration}s. Next action in {$delay}s.");
        return $delay;
    } else {
        $base = $activity_pattern['base_frequency'];
        $variance = $activity_pattern['variance'] ?? 0;
        $variance_amount = rand(-$variance, $variance);
        $delay = $base + $variance_amount;

        Debug("BotGetNextActionTime: Normal idle period. Next action in {$delay} seconds.");
        return max(300, $delay); 
    }
}

/**
 * A simple helper to check if a bot is socially "online".
 */
function BotIsOnline() {
    return !BotIsAsleep();
}

/**
 * Checks if a bot can perform a queued action right now.
 */
function CanPerformAction() {
    return !BotIsAsleep();
}

function BotAllianceCreate($creator_bot_id) {
    // Generate a random, unique tag and name.
    $tag = "BOT-" . rand(100, 999);
    while (IsAllyTagExist($tag)) {
        $tag = "BOT-" . rand(100, 999);
    }
    $name = "Squad " . $tag;
    $alliance_id = CreateAlly($creator_bot_id, $tag, $name);

    if (!$alliance_id) {
        Debug("BotAllianceCreate: Failed to create new alliance using core function.");
        return false;
    }
    AllianceUpdateAllDynamicData($alliance_id);

    Debug("BotAllianceCreate: Successfully created bot alliance '{$name}' [{$tag}] with ID {$alliance_id}.");
    return $alliance_id;
}

/**
 * Adds a bot to an alliance.
 */
function AllianceAddMember($alliance_id, $bot_id) {
    global $db_prefix;

    if (CountAllyMembers($alliance_id) >= 20) {
        Debug("AllianceAddMember: Alliance {$alliance_id} is full.");
        return false;
    }

    if (!BotCheckAllianceRequirements($bot_id, $alliance_id)) {
        Debug("AllianceAddMember: Bot {$bot_id} does not meet requirements for alliance {$alliance_id}.");
        return false;
    }

    $joindate = time();
    $newcomer_rank = GetAllyRankByName($alliance_id, "Newcomer");
    dbquery("UPDATE ".$db_prefix."users SET ally_id = $alliance_id, joindate = $joindate, allyrank = " . ($newcomer_rank['rank_id'] ?? 0) . " WHERE player_id = $bot_id");

    // After adding the member, update the alliance's dynamic data.
    AllianceUpdateAllDynamicData($alliance_id);

    Debug("AllianceAddMember: Added bot {$bot_id} to alliance {$alliance_id}.");
    return true;
}

/**
 * Removes a bot from an alliance and triggers updates.
 */
function AllianceRemoveMember($bot_id) {
    global $db_prefix;
    $user_data = LoadUser($bot_id);
    if (!$user_data || $user_data['ally_id'] == 0) return;
    
    $alliance_id = $user_data['ally_id'];
    
    dbquery("UPDATE ".$db_prefix."users SET ally_id = 0, joindate = 0, allyrank = 0 WHERE player_id = $bot_id");

    // After removing the member, update the alliance's dynamic data.
    AllianceUpdateAllDynamicData($alliance_id);

    Debug("AllianceRemoveMember: Removed bot {$bot_id} from alliance {$alliance_id}.");
}

/**
 * Recalculates and updates the leadership roles based on member stats.
 */
function AllianceUpdateRoles($alliance_id) {
    $alliance_data = LoadAlly($alliance_id);
    if (!$alliance_data) return;
    $leader_id = $alliance_data['owner_id'];
    $members_result = EnumerateAlly($alliance_id, 3, 1, true); 
    $members = array();
    while ($row = dbarray($members_result)) {
        $row['personality'] = BotGetVar('personality', 'unknown', $row['player_id']);
        $members[] = $row;
    }

    if (empty($members)) {
        // If no members, clear the data from the leader's botvars.
        BotDeleteVar($leader_id, 'alliance_roles_json');
        return;
    }

    $leadership_roles = array('leader' => array(), 'co_leaders' => array(), 'war_coordinators' => array());

    // Assign Leader
    $leader = array_shift($members);
    $leadership_roles['leader'] = array('id' => $leader['player_id'], 'name' => $leader['oname']);
    // Ensure the alliance owner is correctly set to the current points leader.
    if ($alliance_data['owner_id'] != $leader['player_id']) {
        AllyChangeOwner($alliance_id, $leader['player_id']);
    }

    // Assign Co-Leaders (next 2).
    for ($i = 0; $i < 2 && !empty($members); $i++) {
        $co_leader = array_shift($members);
        $leadership_roles['co_leaders'][] = array('id' => $co_leader['player_id'], 'name' => $co_leader['oname']);
    }

    // Assign War Coordinators (top 3 fleeter/raider personalities).
    foreach ($members as $key => $member) {
        if (count($leadership_roles['war_coordinators']) < 3) {
            if ($member['personality'] === 'fleeter' || $member['personality'] === 'raider') {
                $leadership_roles['war_coordinators'][] = array('id' => $member['player_id'], 'name' => $member['oname']);
                unset($members[$key]);
            }
        } else { break; }
    }

    $roles_json = json_encode($leadership_roles);
    // Store the JSON in the LEADER's botvars.
    BotSetVar($leader_id, 'alliance_roles_json', $roles_json);
    Debug("AllianceUpdateRoles: Updated roles for alliance {$alliance_id}.");
}

/**
 * Calculates and updates dynamic recruitment requirements based on the alliance average.
 */
function AllianceUpdateRequirements($alliance_id) {
    $alliance_data = LoadAlly($alliance_id);
    if (!$alliance_data) return;
    $leader_id = $alliance_data['owner_id'];
    
    $members_result = EnumerateAlly($alliance_id);
    $member_count = dbrows($members_result);

    if ($member_count == 0) {
        $requirements = array('min_overall_rank' => 5000, 'min_skill' => 20);
    } else {
        $total_rank = 0;
        $total_skill = 0;
        while ($member = dbarray($members_result)) {
            $user_data = LoadUser($member['player_id']);
            $total_rank += $user_data['place1'];
            $total_skill += BotCalculateSkillScore($member['player_id']);
        }
        $avg_rank = $total_rank / $member_count;
        $avg_skill = $total_skill / $member_count;
        
        $requirements = array(
            'min_overall_rank' => floor($avg_rank * 1.1),
            'min_skill' => floor($avg_skill * 0.9)
        );
    }

    $req_json = json_encode($requirements);
    // Store the JSON in the LEADER's botvars.
    BotSetVar($leader_id, 'alliance_reqs_json', $req_json);
    Debug("AllianceUpdateRequirements: Updated requirements for alliance {$alliance_id}.");
}

/**
 * Auto-generates and updates the alliance's public description (exttext).
 */
function AllianceGenerateDescription($alliance_id) {
    global $db_prefix;
    $alliance_data = LoadAlly($alliance_id);
    if (!$alliance_data) return;
    $leader_id = $alliance_data['owner_id'];

    // Get dynamic data from the leader's botvars.
    $roles = json_decode(BotGetVar($leader_id, 'alliance_roles_json', '[]'), true);
    $reqs = json_decode(BotGetVar($leader_id, 'alliance_reqs_json', '[]'), true);

    $description = "Welcome to " . htmlspecialchars($alliance_data['name']) . " [" . htmlspecialchars($alliance_data['tag']) . "]\n\n";
    $description .= "--- LEADERSHIP ---\n";
    if (!empty($roles['leader'])) $description .= "Leader: " . htmlspecialchars($roles['leader']['name']) . "\n";
    if (!empty($roles['co_leaders'])) $description .= "Co-Leaders: " . implode(', ', array_column($roles['co_leaders'], 'name')) . "\n";
    if (!empty($roles['war_coordinators'])) $description .= "WarCo: " . implode(', ', array_column($roles['war_coordinators'], 'name')) . "\n";
    
    $description .= "\n--- RECRUITMENT ---\n";
    if (CountAllyMembers($alliance_id) >= 20) {
        $description .= "STATUS: CLOSED (Full)\n";
    } else {
        $description .= "STATUS: OPEN\n";
        if (!empty($reqs)) {
            $description .= "Min. Overall Rank: < " . number_format($reqs['min_overall_rank']) . "\n";
            $description .= "Min. Avg. Skill: > " . $reqs['min_skill'] . "\n";
        }
    }

    $escaped_description = mysqli_real_escape_string($GLOBALS['db_connect'], $description);
    // Update 'exttext' in the main 'ally' table for public viewing[1].
    dbquery("UPDATE ".$db_prefix."ally SET exttext = '$escaped_description' WHERE ally_id = $alliance_id");
    Debug("AllianceGenerateDescription: Generated new description for alliance {$alliance_id}.");
}

function AllianceUpdateAllDynamicData($alliance_id) {
    AllianceUpdateRoles($alliance_id);
    AllianceUpdateRequirements($alliance_id);
    AllianceGenerateDescription($alliance_id);
}


/**
 * Checks if a bot meets an alliance's recruitment requirements by fetching them
 * from the alliance leader's botvars.
 */
function BotCheckAllianceRequirements($bot_id, $alliance_id) {
    $bot_data = LoadUser($bot_id);
    $ally_data = LoadAlly($alliance_id);
    if (!$bot_data || !$ally_data) return false;
    
    $leader_id = $ally_data['owner_id'];
    $reqs_json = BotGetVar($leader_id, 'alliance_reqs_json', '[]');
    $reqs = json_decode($reqs_json, true);

    if (empty($reqs)) return true; // No requirements set

    if ($bot_data['place1'] > $reqs['min_overall_rank']) return false;
    if (BotCalculateSkillScore($bot_id) < $reqs['min_skill']) return false;
    
    return true; // All checks passed.
}

/**
 * A helper to get a rank by its name, useful for assigning default ranks.
 */
function GetAllyRankByName($alliance_id, $rank_name) {
    $ranks_result = EnumRanks($alliance_id);
    while($rank = dbarray($ranks_result)) {
        if ($rank['name'] === $rank_name) {
            return $rank;
        }
    }
    return null;
}

function BotExecuteAttackSequence() {
    $attack_phase = BotGetVar('attack_phase', 'idle');

    switch ($attack_phase) {
        case 'idle':
            $target = BotFindPotentialTarget();
            if ($target) {
                BotSetVar('attack_target', serialize($target));
                BotSetVar('attack_phase', 'scouting');
                Debug("BotExecuteAttackSequence: Found potential target {$target['oname']}. Moving to 'scouting' phase.");
                return 5;
            }
            return rand(3600, 7200);

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
 * Resets the attack state machine to 'idle'.
 */
function BotResetAttackState($reason, $wait_time = 900) {
    global $BotID;
    Debug("BotResetAttackState: Resetting. Reason: $reason. Waiting {$wait_time}s.");
    BotDeleteVar($BotID, 'attack_phase');
    BotDeleteVar($BotID, 'attack_target');
    return $wait_time;
}

// ===== CORE LOGIC & HELPER FUNCTIONS (STILL RELEVANT) =====

/**
 * Retrieves the structured spy report from botvars.
 */
function BotGetStructuredSpyReport($target_planet_id) {
    global $BotID;
    $var_name = "spy_report_" . $target_planet_id;
    $report_s = BotGetVar($var_name, null);

    if ($report_s) {
        $report = unserialize($report_s);
        BotDeleteVar($BotID, $var_name);
        if (isset($report['time']) && (time() - $report['time']) < 3600) {
            return $report;
        }
    }
    return null;
}

/**
 * Evaluates a spy report to determine if an attack is profitable and feasible.
 * @return array|false The fleet to send if profitable, otherwise false.
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
        Debug("BotEvaluateAttackProfitability: Target is PROFITABLE. Ratio: " . round($profit_ratio, 2) . ". Sending fleet.");
        return $attack_fleet;
    }
    
    Debug("BotEvaluateAttackProfitability: Target NOT profitable. Ratio: " . round($profit_ratio, 2) . ".");
    return false;
}

/**
 * A lightweight battle simulator to estimate losses.
 */
function BotSimulateBattle($attacker_fleet, $defender_fleet, $defender_defense) {
    global $initial;
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

/**
 * Calculates the Deuterium cost for a fleet's journey using the game's core functions.
 */
function BotCalculateFleetTravelCost($fleet, $target) {
    global $BotID, $GlobalUni;
    $user = LoadUser($BotID);
    $start_planet = GetPlanet($user['aktplanet']);
    $dist = FlightDistance($start_planet['g'], $start_planet['s'], $start_planet['p'], $target['galaxy'], $target['system'], $target['planet']);
    $fleet_speed = FlightSpeed($fleet, $user['r115'], $user['r117'], $user['r118']);
    $flight_time = FlightTime($dist, $fleet_speed, 10, $GlobalUni['speed']);
    $fuel_consumption_data = FlightCons($fleet, $dist, $flight_time, $user['r115'], $user['r117'], $user['r118'], $GlobalUni['speed']);
    return floor($fuel_consumption_data['fleet'] + $fuel_consumption_data['probes']);
}

/**
 * Calculates the total resource value (metal + crystal) of a fleet composition.
 */
function BotCalculateFleetResourceCost($fleet) {
    global $initial;
    $total_cost = 0;
    foreach ($fleet as $ship_id => $count) {
        if (isset($initial[$ship_id])) {
            $total_cost += ($initial[$ship_id][0] + $initial[$ship_id][1]) * $count;
        }
    }
    return $total_cost;
}

/**
 * Calculates the value of a potential debris field from predicted losses.
 */
function BotCalculateDebrisValue($attacker_losses, $defender_losses) {
    $attacker_cost = BotCalculateFleetResourceCost($attacker_losses);
    $defender_cost = BotCalculateFleetResourceCost($defender_losses);
    return ($attacker_cost + $defender_cost) * 0.70;
}
