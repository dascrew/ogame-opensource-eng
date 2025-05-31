<?php

require_once "game/id.php";
require_once "game/prod.php";


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
    $text = StartResearch ($user[player_id], $user[aktplanet], $obj_id, 0);
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

function BotGetBuildingEnergyCost($buildingId, $current_level, $personality_config, $subtype_config = null)
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
