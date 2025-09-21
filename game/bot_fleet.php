<?php

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

function BotGetFleetCount($shipTypeId)
{
    $all_ship_counts = BotGetShipCount();
    return $all_ship_counts[$shipTypeId] ?? 0;
}

function BotGetShipCount()
{
    global $BotID;
    $user = LoadUser($BotID);
    $planet = GetPlanet($user['aktplanet']);
    
    $ship_counts = array();
    for ($i = 202; $i <= 215; $i++) {
        $ship_counts[$i] = $planet['f' . $i] ?? 0;
    }
    return $ship_counts;
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
