<?php

// Check if we can build the specified building on the active planet (1-yes, 0-no).
function BotCanBuild ($obj_id)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    ProdResources ( $aktplanet, $aktplanet['lastpeek'], $BotNow );
    $level = $aktplanet['b'.$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, 0 );
    return  $text === '';
}

// Start building on an active planet.
// Return 0 if there are not enough conditions or resources to start building. 
// Return the number of seconds to wait until the construction is completed.
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
    else {
        return 0;
    }
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
    return $currentenergy >= $energy;
}

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
    return $text === '';
}

// Begin research on the active planet.
function BotResearch ($obj_id)
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $level = $aktplanet['r'.$obj_id] + 1;
    $text = StartResearch ($user['player_id'], $user['aktplanet'], $obj_id, 0);
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $reslab = ResearchNetwork ( $user['planet_id'], $obj_id );
        $prem = PremiumStatus ($user);
        if ( $prem['technocrat'] ) $r_factor = 1.1;
        else $r_factor = 1.0;
        $seconds = ResearchDuration ( $obj_id, $level, $reslab, $speed * $r_factor);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        UpdateLastClick($BotID);
        return $seconds;
    }
    else {
        return 0;
    }
}

function BotGetLastBuilt() {
    global $BotID;

    $user = LoadUser($BotID);
    if (!$user || !isset($user['aktplanet'])) {
        Debug("Failed to load user or aktplanet missing");
        return 0;
    }

    $aktplanet = GetPlanet($user['aktplanet']);
    if (!is_array($aktplanet) || !isset($aktplanet['planet_id'])) {
        Debug("Invalid planet data for ID: " . $user['aktplanet']);
        return 0;
    }

    if (isset($aktplanet['last_built'])) {
        return $aktplanet['last_built'];
    }

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

function BotGetBuildingEnergyCost($buildingId, $current_level)
{
    $consumption_current_level = CalculateBuildingBaseConsumption($buildingId, $current_level);
    $consumption_next_level = CalculateBuildingBaseConsumption($buildingId, $current_level + 1);

    $increase = $consumption_next_level - $consumption_current_level;
    $increase = max(0, $increase);

    return $increase;
}

function BotCanAffordEnergy($mine_id)
{
    global $BotID, $BotNow;
    
    if (!in_array($mine_id, [GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH])) {
        return false;
    }
    
    $user = LoadUser($BotID);
    $aktplanet = GetPlanet($user['aktplanet']);
    ProdResources($aktplanet, $aktplanet['lastpeek'], $BotNow);
    
    $current_level = $aktplanet['b'.$mine_id];
    $current_energy = $aktplanet['e'];
    
    // Get energy increase for next level
    $energy_increase = BotGetBuildingEnergyCost($mine_id, $current_level);
    
    // Simple check: would energy remain non-negative?
    return ($current_energy - $energy_increase) >= 0;
}
