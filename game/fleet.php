<?php

// Fleet Management.

/*
fleet_id: Ordinal number of the fleet in the table (INT AUTO_INCREMENT PRIMARY KEY)
owner_id: User number to which the fleet belongs (INT)
union_id: The number of the union in which the fleet is flying (INT)
m, k, d: Cargo transported (metal/crystal/deuterium) (DOUBLE)
fuel: Loaded fuel for flight (deuterium) (DOUBLE)
mission: Type of mission (INT)
start_planet: Start (INT)
target_planet: Target (INT)
flight_time: One-way flight time in seconds (INT)
deploy_time: Fleet holding time in seconds (INT)
ipm_amount: Number of interplanetary missiles (INT)
ipm_target: target id for interplanetary rockets, 0 - all (INT)
shipXX: number of ships of each type (INT)

Fleet missions are issued as an events for the global queue.

Sending a fleet consists of taking away the following fields from the planet: fXX (fleet), m/k/d - resources.
Fleet arrival: adds these fields (or takes them away again, when attacking), and generates messages.

The first three pages of flottenX prepare the parameters for the flottenversand page, which either sends the fleet or returns an error.

One fleet task can spawn another task upon completion, e.g. after reaching Transport, a new task is created - return Transport.

In Overview, all subsequent missions are "predicted", they are not really there. The Fleet menu shows the description of the missions close to the database data.

The structure of the ACS table:
union_id: Union ID (INT PRIMARY KEY)
fleet_id: ID of the ACS's lead fleet (initial Attack = slot 0) (INT)
name: union name. default: "KV" + number (CHAR(20))
players: IDs of invited players, separated by commas (TEXT)

*/

// List of fleet mission types
const FTYP_ATTACK = 1;      // Attack
const FTYP_ACS_ATTACK = 2;  // ACS Attack (slot > 0)
const FTYP_TRANSPORT = 3;   // Transport
const FTYP_DEPLOY = 4;      // Deploy
const FTYP_ACS_HOLD = 5;    // ACS Hold
const FTYP_SPY = 6;         // Spy
const FTYP_COLONIZE = 7;    // Colonize
const FTYP_RECYCLE = 8;     // Recycle
const FTYP_DESTROY = 9;     // Destroy (moon)
const FTYP_EXPEDITION = 15; // Expedition
const FTYP_MISSILE = 20;        // Missile attack (IPMs)
const FTYP_ACS_ATTACK_HEAD = 21;    // ACS Attack head (slot = 0)
const FTYP_RETURN = 100;            // Fleet returns (add this value to any mission)
const FTYP_ORBITING = 200;          // Fleet is in orbit (add this value to any mission)

// ==================================================================================
// Get a list of available missions for the fleet.

/*
Possible assignments:

X:0
>= X:16 (position becomes 16, any type of planet)          Expedition
empty space X:1 ... X:15 without colon       Transport, Attack
empty space X:1 ... X:15 with colon       Transport, Attack, Colonize

own planet                         Transport, Deploy
own moon                               Transport, Deploy

debris field with recycler            Recycle
debris field without recycler        No suitable missions (Нет подходящих заданий)

buddy/ally planet              Transport, Attack, Hold, ACS Attack
buddy/ally moon with Deathstar            Transport, Attack, Hold, ACS Attack, Destroy
buddy/ally moon without Deathstar        Transport, Attack, Hold, ACS Attack
(if there's a Spy probe add Espionage)
if there's only a spy in the fleet     Espionage

foreign planet                     Transport, Attack, ACS Attack
foreign moon with Deathstar                     Transport, Attack, ACS Attack, Destroy
foreign moon without Deathstar                Transport, Attack, ACS Attack
(if there's a Spy probe add Espionage)
if there's only a spy in the fleet     Espionage
*/

function FleetAvailableMissions ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype, $galaxy, $system, $planet, $planettype, $fleet )
{
    $missions = array ( );

    $uni = LoadUniverse ();
    $origin = LoadPlanet ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype );
    $target = LoadPlanet ( $galaxy, $system, $planet, $planettype );

    if ( $planet >= 16 )
    {
        $missions[0] = FTYP_EXPEDITION;
        return $missions;
    }

    if ( $planettype == 2)        // debris field.
    {
        if ( $fleet[GID_F_RECYCLER] > 0 ) $missions[0] = FTYP_RECYCLE;    // if there are recyclers in the fleet
        return $missions;
    }

    if ( $target == NULL )        // empty space
    {
        $missions[0] = FTYP_TRANSPORT;
        $missions[1] = FTYP_ATTACK;
        if ( $fleet[GID_F_COLON] > 0 ) $missions[2] = FTYP_COLONIZE;    // if there's a colonizer in the fleet
        return $missions;
    }

    if ( $origin['owner_id'] == $target['owner_id'] )        // own moons/planets
    {
        $missions[0] = FTYP_TRANSPORT;
        $missions[1] = FTYP_DEPLOY;
        return $missions;
    }
    else
    {
        $i = 0;
        $origin_user = LoadUser ($origin['owner_id']);
        $target_user = LoadUser ($target['owner_id']);

        if ( ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) )      // allies or buddies
        {
            $missions[$i++] = FTYP_TRANSPORT;
            $missions[$i++] = FTYP_ATTACK;
            if ( $uni['acs'] > 0 ) $missions[$i++] = FTYP_ACS_HOLD;
            if ( $fleet[GID_F_DEATHSTAR] > 0 && GetPlanetType($target) == 3 ) $missions[$i++] = FTYP_DESTROY;
            if ( $fleet[GID_F_PROBE] > 0  ) $missions[$i++] = FTYP_SPY;
        }
        else        // all others
        {
            $missions[$i++] = FTYP_TRANSPORT;
            $missions[$i++] = FTYP_ATTACK;
            if ( $fleet[GID_F_DEATHSTAR] > 0 && GetPlanetType($target) == 3 ) $missions[$i++] = FTYP_DESTROY;
            if ( $fleet[GID_F_PROBE] > 0  ) $missions[$i++] = FTYP_SPY;
        }

        // If the target planet is on the ACS attack list, add the task
        $unions = EnumUnion ( $origin_user['player_id'] );
        foreach ( $unions as $u=>$union ) {
            $fleet_obj = LoadFleet ( $union['fleet_id'] );
            $fleet_target = GetPlanet ( $fleet_obj['target_planet'] );
            if ( $fleet_target['planet_id'] == $target['planet_id'] ) {
                $missions[$i++] = FTYP_ACS_ATTACK;
                break;
            }
        }
        return $missions;
    }
}

// ==================================================================================
// Flight Calculation.

// Distance.
function FlightDistance ( $thisgalaxy, $thissystem, $thisplanet, $galaxy, $system, $planet )
{
    if ($thisgalaxy == $galaxy) {
        if ($thissystem == $system) {
            if ($planet == $thisplanet) $dist = 5;
            else $dist = abs ($planet - $thisplanet) * 5 + 1000;
        }
        else $dist = abs ($system - $thissystem) * 5 * 19 + 2700;
    }
    else $dist = abs ($galaxy - $thisgalaxy) * 20000;
    return $dist;
}

// Group fleet speed.
function FlightSpeed ($fleet, $combustion, $impulse, $hyper)
{
    $minspeed = FleetSpeed ( GID_F_PROBE, $combustion, $impulse, $hyper );        // the fastest ship is the Spy Probe.
    foreach ($fleet as $id=>$amount)
    {
        $speed = FleetSpeed ( $id, $combustion, $impulse, $hyper);
        if ( $amount == 0 || $speed == 0 ) continue;
        if ($speed < $minspeed) $minspeed = $speed;
    }
    return $minspeed;
}

// Deuterium consumption per flight by the entire fleet.
function FlightCons ($fleet, $dist, $flighttime, $combustion, $impulse, $hyper, $speedfactor, $hours=0)
{
    $cons = array ( 'fleet' => 0, 'probes' => 0 );
    foreach ($fleet as $id=>$amount)
    {
        if ($amount > 0) {
            $spd = 35000 / ( $flighttime * $speedfactor - 10) * sqrt($dist * 10 / FleetSpeed($id, $combustion, $impulse, $hyper ) );
            $basecons = $amount * FleetCons ($id, $combustion, $impulse, $hyper );
            $consumption = $basecons * $dist / 35000 * (($spd / 10) + 1) * (($spd / 10) + 1);
            $consumption += $hours * $amount * FleetCons ($id, $combustion, $impulse, $hyper ) / 10;    // holding costs
            if ( $id == GID_F_PROBE ) $cons['probes'] += $consumption;
            else $cons['fleet'] += $consumption;
        }
    }
    return $cons;
}

// Flight time in seconds, at a given percentage.
function FlightTime ($dist, $slowest_speed, $prc, $xspeed)
{
    return round ( (35000 / ($prc*10) * sqrt ($dist * 10 / $slowest_speed ) + 10) / $xspeed );
}

// The speed of the ship
// 202-C/I, 203-C, 204-C, 205-I, 206-I, 207-H, 208-I, 209-C, 210-C, 211-I/H, 212-C, 213-H, 214-H, 215-H
function FleetSpeed ( $id, $combustion, $impulse, $hyper)
{
    global $UnitParam;

    $baseSpeed = $UnitParam[$id][4];

    switch ($id) {
        case GID_F_SC:
            if ($impulse >= 5) return ($baseSpeed + 5000) * (1 + 0.2 * $impulse);
            else return $baseSpeed * (1 + 0.1 * $combustion);
        case GID_F_BOMBER:
            if ($hyper >= 8) return ($baseSpeed + 1000) * (1 + 0.3 * $hyper);
            else return $baseSpeed * (1 + 0.2 * $impulse);
        case GID_F_LC:
        case GID_F_LF:
        case GID_F_RECYCLER:
        case GID_F_PROBE:
        case GID_F_SAT:
            return $baseSpeed * (1 + 0.1 * $combustion);
        case GID_F_HF:
        case GID_F_CRUISER:
        case GID_F_COLON:
            return $baseSpeed * (1 + 0.2 * $impulse);
        case GID_F_BATTLESHIP:
        case GID_F_DESTRO:
        case GID_F_DEATHSTAR:
        case GID_F_BATTLECRUISER:
            return $baseSpeed * (1 + 0.3 * $hyper);
        default: return $baseSpeed;
    }
}

function FleetCargo ( $id )
{
    global $UnitParam;
    return $UnitParam[$id][3];
}

// Total carrying capacity of the fleet
function FleetCargoSummary ( $fleet )
{
    global $fleetmap;
    $cargo = 0;
    foreach ( $fleetmap as $n=>$gid )
    {
        $amount = $fleet[$gid];
        if ($gid != GID_F_PROBE) $cargo += FleetCargo ($gid) * $amount;        // not counting probes.
    }
    return $cargo;
}

function FleetCons ($id, $combustion, $impulse, $hyper )
{
    global $UnitParam;
    // The Small Cargo has a 2X increase in consumption when changing engines. In a bomber, it does NOT increase.
    if ($id == GID_F_SC && $impulse >= 5) return $UnitParam[$id][5] * 2;
    else return $UnitParam[$id][5];
}

// ==================================================================================

// Alter the number of ships on a planet.
function AdjustShips ($fleet, $planet_id, $sign)
{
    global $fleetmap;
    global $db_prefix;

    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ($fleetmap as $i=>$gid)
    {
        if ($i > 0) $query .= ",";
        $query .= "f$gid = f$gid $sign " . $fleet[$gid] ;
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Dispatch the fleet. No checks are performed. Returns the ID of the fleet.
function DispatchFleet ($fleet, $origin, $target, $order, $seconds, $m, $k ,$d, $cons, $when, $union_id=0, $deploy_time=0)
{
    global $db_prefix;
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    $now = $when;
    $prio = QUEUE_PRIO_FLEET + $order;
    $flight_time = $seconds;

    // Add the fleet.
    $fleet_obj = array ( null, $origin['owner_id'], $union_id, $m, $k, $d, $cons, $order, $origin['planet_id'], $target['planet_id'], $flight_time, $deploy_time,
                         0, 0, $fleet[202], $fleet[203], $fleet[204], $fleet[205], $fleet[206], $fleet[207], $fleet[208], $fleet[209], $fleet[210], $fleet[211], $fleet[212], $fleet[213], $fleet[214], $fleet[215] );
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Log entry
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( null, $origin['owner_id'], $target['owner_id'], $union_id, $origin['m'], $origin['k'], $origin['d'], $m, $k, $d, $cons, $order, $flight_time, $deploy_time, $now, $now+$seconds, 
                        $origin['g'], $origin['s'], $origin['p'], $origin['type'], $target['g'], $target['s'], $target['p'], $target['type'], 
                        0, 0, $fleet[202], $fleet[203], $fleet[204], $fleet[205], $fleet[206], $fleet[207], $fleet[208], $fleet[209], $fleet[210], $fleet[211], $fleet[212], $fleet[213], $fleet[214], $fleet[215] );
    AddDBRow ($fleetlog, 'fleetlogs');

    // Add the task to the global event queue.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    if ($order == FTYP_ATTACK && IsBot($target_planet['owner_id'])) {
        $target_user = LoadUser($target_planet['owner_id']);
        if ($target_user['ally_id'] > 0) {
            $alliance_data = LoadAlly($target_user['ally_id']);
            $leader_id = $alliance_data['owner_id'];

            // Get attacking fleet details.
            $attacking_fleet_obj = LoadFleet($fleet_id);

            $defense_request = array(
                'request_id'        => 'def-' . time(),
                'target_bot_id'     => $target_planet['owner_id'],
                'target_planet_id'  => $target_planet['id'],
                'attacker_id'       => $attacking_fleet_obj['owner_id'],
                'attacking_fleet'   => $fleet, // The fleet composition array
                'arrival_time'      => $attacking_fleet_obj['end_time']
            );

            // Store the request in the alliance leader's botvars.
            // We store it as a list, as there could be multiple simultaneous attacks on the alliance.
            $requests_s = BotGetVar($leader_id, 'defense_requests', '[]');
            $requests = json_decode($requests_s, true);
            $requests[] = $defense_request;
            BotSetVar($leader_id, 'defense_requests', json_encode($requests));

            Debug("DispatchFleet: Created defense request for bot {$target_planet['owner_id']} in alliance {$target_user['ally_id']}.");
        }
    }
    return $fleet_id;
}

// Recall the fleet (if possible)
function RecallFleet ($fleet_id, $now=0)
{
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    if ($now == 0) $now = time ();
    $fleet_obj = LoadFleet ($fleet_id);
    global $fleetmap;
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    // If the fleet is already returning, do nothing.
    if ( $fleet_obj['mission'] >= FTYP_RETURN && $fleet_obj['mission'] < FTYP_ORBITING ) return;

    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );
    $queue = GetFleetQueue ($fleet_obj['fleet_id']);

    if ($fleet_obj['mission'] < FTYP_RETURN) $new_mission = $fleet_obj['mission'] + FTYP_RETURN;
    else $new_mission = $fleet_obj['mission'] - FTYP_RETURN;
    UserLog ( $fleet_obj['owner_id'], "FLEET", 
        va(loca_lang("DEBUG_LOG_FLEET_RECALL", $uni['lang']), $fleet_obj['fleet_id']) . GetMissionNameDebug ($new_mission) . " " .
        $origin['name'] ." [".$origin['g'].":".$origin['s'].":".$origin['p']."] &lt;- ".$target['name']." [".$target['g'].":".$target['s'].":".$target['p']."]<br>" .
        DumpFleet ($fleet) );

    // For recall missions with a hold, the hold time is used as the return flight time.
    if ($fleet_obj['mission'] < FTYP_RETURN) DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] + FTYP_RETURN, $now-$queue['start'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $now);
    else DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] - FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $now);

    DeleteFleet ($fleet_obj['fleet_id']);            // delete fleet
    RemoveQueue ( $queue['task_id'] );    // delete the task

    // If the last union fleet is recalled, delete the entire union.
    $union_id = $fleet_obj['union_id'];
    if ( $union_id && ( $fleet_obj['mission'] == FTYP_ACS_ATTACK || $fleet_obj['mission'] == FTYP_ACS_ATTACK_HEAD ) ) 
    {
        $result = EnumUnionFleets ($union_id);
        if ( dbrows ( $result ) == 0 ) RemoveUnion ( $union_id );    // delete union
    }
}

// Load the fleet
function LoadFleet ($fleet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE fleet_id = '".$fleet_id."'";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Delete the fleet
function DeleteFleet ($fleet_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."fleet WHERE fleet_id = $fleet_id;";
    dbquery ($query);
}

// Modify the fleet.
function SetFleet ($fleet_id, $fleet)
{
    global $db_prefix;
    global $fleetmap;
    $query = "UPDATE ".$db_prefix."fleet SET ";
    foreach ( $fleetmap as $i=>$gid ) {
        if ( $i == 0 ) $query .= "ship".$gid."=".$fleet[$gid];
        else $query .= ", ship".$gid."=".$fleet[$gid];
    }
    $query .= " WHERE fleet_id=$fleet_id;";
    dbquery ($query);
}

// Get mission description (for debugging)
function GetMissionNameDebug ($num)
{
    switch ($num)
    {
        case FTYP_ATTACK    :      return "Атака убывает";
        case (FTYP_ATTACK+FTYP_RETURN) :      return "Атака возвращается";
        case FTYP_ACS_ATTACK    :      return "Совместная атака убывает";
        case (FTYP_ACS_ATTACK+FTYP_RETURN) :     return "Совместная атака возвращается";
        case FTYP_TRANSPORT    :     return "Транспорт убывает";
        case (FTYP_TRANSPORT+FTYP_RETURN) :     return "Транспорт возвращается";
        case FTYP_DEPLOY    :     return "Оставить убывает";
        case (FTYP_DEPLOY+FTYP_RETURN) :     return "Оставить возвращается";
        case FTYP_ACS_HOLD   :      return "Держаться убывает";
        case (FTYP_ACS_HOLD+FTYP_RETURN) :     return "Держаться возвращается";
        case (FTYP_ACS_HOLD+FTYP_ORBITING) :    return "Держаться на орбите";
        case FTYP_SPY   :      return "Шпионаж убывает";
        case (FTYP_SPY+FTYP_RETURN) :     return "Шпионаж возвращается";
        case FTYP_COLONIZE    :     return "Колонизировать убывает";
        case (FTYP_COLONIZE+FTYP_RETURN) :     return "Колонизировать возвращается";
        case FTYP_RECYCLE    :     return "Переработать убывает";
        case (FTYP_RECYCLE+FTYP_RETURN) :    return "Переработать возвращается";
        case FTYP_DESTROY   :      return "Уничтожить убывает";
        case (FTYP_DESTROY+FTYP_RETURN):      return "Уничтожить возвращается";
        case 14  :      return "Испытание убывает";             // wtf ???
        case (14+FTYP_RETURN):      return "Испытание возвращается";           // wtf ???
        case FTYP_EXPEDITION  :      return "Экспедиция убывает";
        case (FTYP_EXPEDITION+FTYP_RETURN):      return "Экспедиция возвращается";
        case (FTYP_EXPEDITION+FTYP_ORBITING):      return "Экспедиция на орбите";
        case FTYP_MISSILE:       return "Ракетная атака";
        case FTYP_ACS_ATTACK_HEAD  :      return "Атака САБ убывает";
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN) :      return "Атака САБ возвращается";

        default: return "Неизвестно";
    }
}

// Launch interplanetary rockets
function LaunchRockets ( $origin, $target, $seconds, $amount, $type )
{
    global $db_prefix;
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    if ( $amount > $origin['d503'] ) return;    // You can't launch more missiles than there are rockets on the planet.

    $now = time ();
    $prio = QUEUE_PRIO_FLEET + FTYP_MISSILE;

    // Write the IPM off the planet.
    $origin['d503'] -= $amount;
    SetPlanetDefense ( $origin['planet_id'], $origin );

    // Add a missile attack.
    $fleet_obj = array ( null, $origin['owner_id'], 0, 0, 0, 0, 0, FTYP_MISSILE, $origin['planet_id'], $target['planet_id'], $seconds, 0,
                         $amount, $type, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Log entry
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( null, $origin['owner_id'], $target['owner_id'], 0, 0, 0, 0, 0, 0, 0, 0, FTYP_MISSILE, $seconds, 0, $now, $now+$seconds, 
                        $origin['g'], $origin['s'], $origin['p'], $origin['type'], $target['g'], $target['s'], $target['p'], $target['type'], 
                        $amount, $type, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    AddDBRow ($fleetlog, 'fleetlogs');

    // Add the task to the global event queue.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// ==================================================================================
// Fleet Task Processing.

function FleetList ($fleet, $lang)
{
    global $fleetmap;
    $res = "";
    foreach ( $fleetmap as $i=>$gid )
    {
        if ($fleet[$gid] > 0) $res .= loca_lang("NAME_$gid", $lang) . ": " . nicenum ($fleet[$gid]) . " ";
    }
    return $res;
}

// *** Attack ***

function AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Transport ***

function TransportArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    $oldm = $target['m'];
    $oldk = $target['k'];
    $oldd = $target['d'];

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $target['planet_id'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    DispatchFleet ($fleet, $origin, $target, FTYP_TRANSPORT+FTYP_RETURN, $fleet_obj['flight_time'], 0, 0, 0, $fleet_obj['fuel'] / 2, $queue['end']);

    $text = va(loca_lang("FLEET_TRANSPORT_OWN", $origin_user['lang']), 
            "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_ARRIVE", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Transport to foreign planet.
    if ( $origin['owner_id'] != $target['owner_id'] )
    {
        $target_user = LoadUser ( $target['owner_id'] );
        loca_add ( "fleetmsg", $target_user['lang'] );

        $text = va(loca_lang("FLEET_TRANSPORT_OTHER", $target_user['lang']),
                $origin_user['oname'],
                $target['name'],
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
                nicenum($fleet_obj['m']),
                nicenum($fleet_obj['k']),
                nicenum($fleet_obj['d']),
                nicenum($oldm),
                nicenum($oldk),
                nicenum($oldd),
                nicenum($oldm+$fleet_obj['m']),
                nicenum($oldk+$fleet_obj['k']),
                nicenum($oldd+$fleet_obj['d']) );
        SendMessage ( $target['owner_id'], 
            loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']), 
            loca_lang("FLEET_MESSAGE_TRADE", $target_user['lang']), 
            $text, MTYP_MISC, $queue['end']);
    }
}

function CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target)
{
    if ( $fleet_obj['m'] < 0 ) $fleet_obj['m'] = 0;    // Protection against negative resources (just in case)
    if ( $fleet_obj['k'] < 0 ) $fleet_obj['k'] = 0;
    if ( $fleet_obj['d'] < 0 ) $fleet_obj['d'] = 0;

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        "<a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
        $origin['name'],
        "<a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>" );
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** Deploy ***

function DeployArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Also unload half the fuel
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'] + floor ($fleet_obj['fuel'] / 2), $target['planet_id'], '+' );
    AdjustShips ( $fleet, $fleet_obj['target_planet'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_DEPLOY", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        $target['name'],
        "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>" );
    $text .= va(loca_lang("FLEET_DEPLOY_RES", $origin_user['lang']),
        nicenum($fleet_obj['m']),
        nicenum($fleet_obj['k']),
        nicenum($fleet_obj['d'] + floor ($fleet_obj['fuel'] / 2)) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_HOLD", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** ACS Hold ***

// Count the number of fleets sent to hold on the specified planet (flying and in orbit)
function GetHoldingFleetsCount ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_ACS_HOLD." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).") AND target_planet = $planet_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Check if it is possible to send a fleet to a player to hold on a planet (no more than `maxhold_users` players can hold their fleets on a planet at the same time)
function CanStandHold ( $planet_id, $player_id, $maxhold_users )
{
    global $db_prefix;
    $query = "SELECT owner_id FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_ACS_HOLD." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).") AND target_planet = $planet_id;";
    $result = dbquery ($query);
    return dbrows ($result) < $maxhold_users;
}

function HoldingArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Update the activity on the planet.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Start an orbit hold task.
    // Make the hold time a flight time (so that it can be used when returning the fleet)
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_ORBITING, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end'], 0, $fleet_obj['flight_time']);
}

function HoldingHold ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Return the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);
}

// *** Espionage ***

function SpyArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    global $UnitParam;
    global $fleetmap;
    global $defmap;
    global $buildmap;
    global $resmap;

    $now = $queue['end'];

    $origin_user = LoadUser ( $origin['owner_id'] );
    $target_user = LoadUser ( $target['owner_id'] );

    $origin_ships = $target_ships = $origin_cost = 0;
    foreach ( $fleetmap as $i=>$gid )
    {
        $origin_ships += $fleet_obj["ship$gid"];
        $origin_cost += $fleet_obj["ship$gid"] * $UnitParam[$gid][0];
        $target_ships += $target["f$gid"];
    }

    $origin_prem = PremiumStatus ($origin_user);
    $target_prem = PremiumStatus ($target_user);
    $origin_tech = $origin_user['r'.GID_R_ESPIONAGE];
    if ($origin_prem['technocrat']) $origin_tech += 2;
    $target_tech = $target_user['r'.GID_R_ESPIONAGE];
    if ($target_prem['technocrat']) $target_tech += 2;

    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "espionage", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );

    // A chance at espionage protection
    $level = $origin_tech - $target_tech;
    $level = $level * abs($level) - 1 + $origin_ships;
    $cost = $origin_cost / 1000 / 400;
    $c = sqrt ( pow (2,($origin_ships-($level+1))) ) * ($cost * sqrt($target_ships)*5);
    if ($c > 2) $c = 2;
    $c = rand (0, $c*100) / 100;
    if ($c < 0) $c = 0;
    if ($c > 1) $c = 1;
    $counter = $c * 100;

    if (IsBot($origin['owner_id'])) {
        $spy_data_raw = [
            'success'   => true,
            'time'      => $now,
            'target_planet_id' => $target['planet_id'],
            'resources' => [
                'm' => $target['m'],
                'k' => $target['k'],
                'd' => $target['d'],
                'e' => $target['emax']
            ],
            'fleet' => [],
            'defense' => [],
            'buildings' => [],
            'research' => []
        ];

        // Gather fleet data if espionage level is sufficient.
        if ($level > 0) {
            foreach ($fleetmap as $gid) { $spy_data_raw['fleet'][$gid] = $target["f$gid"]; }
        }
        // Gather defense data.
        if ($level > 1) {
            foreach ($defmap as $gid) { $spy_data_raw['defense'][$gid] = $target["d$gid"]; }
        }
        // Gather building data.
        if ($level > 3) {
            foreach ($buildmap as $gid) { $spy_data_raw['buildings'][$gid] = $target["b$gid"]; }
        }
        // Gather research data.
        if ($level > 5) {
            foreach ($resmap as $gid) { $spy_data_raw['research'][$gid] = $target_user["r$gid"]; }
        }

        $var_name = "spy_report_" . $target['planet_id'];
        BotSetVar($origin['owner_id'], $var_name, serialize($spy_data_raw));
        Debug("SpyArrive: Stored structured spy report for bot {$origin['owner_id']} on target {$target['planet_id']}.");
    }

    $subj = "\n<span class=\"espionagereport\">\n" .
                va(loca_lang("SPY_SUBJ", $origin_user['lang']), $target['name']) . "\n" .
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n";

    $report = "";

    // Head
    $report .= "<table width=400><tr><td class=c colspan=4>" .
            va(loca_lang("SPY_RESOURCES", $origin_user['lang']), $target['name']) .
            " <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> " .
            va(loca_lang("SPY_PLAYER", $origin_user['lang']), $target_user['oname'], date ("m-d H:i:s", $now)) .
            "</td></tr>\n";
    $report .= "</div></font></TD></TR><tr><td>".loca_lang("SPY_M", $origin_user['lang'])."</td><td>".nicenum($target['m'])."</td>\n";
    $report .= "<td>".loca_lang("SPY_K", $origin_user['lang'])."</td><td>".nicenum($target['k'])."</td></tr>\n";
    $report .= "<tr><td>".loca_lang("SPY_D", $origin_user['lang'])."</td><td>".nicenum($target['d'])."</td>\n";
    $report .= "<td>".loca_lang("SPY_E", $origin_user['lang'])."</td><td>".nicenum($target['emax'])."</td></tr>\n";
    $report .= "</table>\n";

    // Activity
    $report .= "<table width=400><tr><td class=c colspan=4>     </td></tr>\n";
    $report .= "<TR><TD colspan=4><div onmouseover='return overlib(\"&lt;font color=white&gt;".loca_lang("SPY_ACTIVITY", $origin_user['lang'])."&lt;/font&gt;\", STICKY, MOUSEOFF, DELAY, 750, CENTER, WIDTH, 100, OFFSETX, -130, OFFSETY, -10);' onmouseout='return nd();'></TD></TR></table>\n";

    // Fleet on hold
    $result = GetHoldingFleets ( $target['planet_id'] );
    $holding_fleet = array ();
    foreach ( $fleetmap as $i=>$gid ) {
        $holding_fleet[$gid] = 0;
    }    
    while ( $fobj = dbarray ($result) )
    {
        foreach ( $fleetmap as $i=>$gid ) {
            $holding_fleet[$gid] += $fobj["ship$gid"];
        }
    }

    // Fleet
    if ( $level > 0 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_FLEET", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $fleetmap as $i=>$gid )
        {
            $amount = $target["f$gid"] + $holding_fleet[$gid];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Defense
    if ( $level > 1 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_DEFENSE", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $defmap as $i=>$gid )
        {
            $amount = $target["d$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Buildings
    if ( $level > 3 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_BUILDINGS", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $buildmap as $i=>$gid )
        {
            $amount = $target["b$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Research
    if ( $level > 5 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_RESEARCH", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $resmap as $i=>$gid )
        {
            $amount = $target_user["r$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    $report .= "<center>".va(loca_lang("SPY_COUNTER", $origin_user['lang']), floor($counter))."</center>\n";
    $report .= "<center><a href='#' onclick='showFleetMenu(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target).",1);'>".loca_lang("SPY_ATTACK", $origin_user['lang'])."</a></center>\n";

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        $subj, 
        $report, MTYP_SPY_REPORT, $queue['end'], $target['planet_id']);

    // Send a message to other player about spying.
    $text = va(loca_lang("FLEET_SPY_OTHER", $target_user['lang']), 
            $origin['name'],
            "<a onclick=\"showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p'].");\" href=\"#\">[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>",
            $target['name'],
            "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            $counter ) ;
    SendMessage ( $target['owner_id'],
        loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']),
        loca_lang("FLEET_MESSAGE_SPY", $target_user['lang']),
        $text, MTYP_MISC, $queue['end']);

    // Update activity on the foreign planet.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Return the fleet.
    if ( mt_rand (0, 100) < $counter ) StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
    else DispatchFleet ($fleet, $origin, $target, FTYP_SPY+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
}

function SpyReturn ($queue, $fleet_obj, $fleet)
{
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );
}

// *** Colonize ***

function ColonizationArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    global $db_prefix;

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_COLONIZE", $origin_user['lang']), 
                "<a href=\"javascript:showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].")\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>" );

    if ( !HasPlanet($target['g'], $target['s'], $target['p']) )    // If the place is unoccupied, then colonization is successful.
    {
        // If the number of planets in the empire is greater than the maximum, then don't establish a new colony.
        $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$fleet_obj['owner_id']."' AND (type = ".PTYP_PLANET.");";
        $result = dbquery ($query);
        $num_planets = dbrows ($result);
        if ( $num_planets >= 9 )
        {
            $text .= loca_lang("FLEET_COLONIZE_MAX", $origin_user['lang']);

            // Add an abandoned colony.
            $id = CreateAbandonedColony ( $target['g'], $target['s'], $target['p'], $queue['end'] );
        }
        else
        {
            $text .= loca_lang("FLEET_COLONIZE_SUCCESS", $origin_user['lang']);

            // Create a new colony.
            $id = CreatePlanet ( $target['g'], $target['s'], $target['p'], $fleet_obj['owner_id'], 1, 0, 0, $queue['end'] );
            Debug ( "Игроком ".$origin['owner_id']." колонизирована планета $id [".$target['g'].":".$target['s'].":".$target['p']."]");

            // Take 1 colony ship away from the fleet
            if ( $fleet[GID_F_COLON] > 0 ) {
                $fleet[GID_F_COLON]--;
                $met = $kris = $deut = $energy = 0;
                $cost = ShipyardPrice ( GID_F_COLON );
                AdjustStats ( $origin['owner_id'], ($cost['m'] + $cost['k'] + $cost['d']), 1, 0, '-' );
                RecalcRanks ();
            }
        }

        // Return the fleet, if there's anything left.
        global $fleetmap;
        $num_ships = 0;
        foreach ($fleetmap as $i=>$gid) {
            $num_ships += $fleet[$gid];
        }
        if ($num_ships > 0) {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
            $target = GetPlanet ($id);
            DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }
        else {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
        }
    }
    else
    {
        $text .= loca_lang("FLEET_COLONIZE_FAIL", $origin_user['lang']);

        // Return the fleet.
        DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
    }

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_COLONIZE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_COLONIZE_SUBJ", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

function ColonizationReturn ($queue, $fleet_obj, $fleet, $origin, $target)
{
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']), 
            FleetList($fleet, $origin_user['lang']),
            "<a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            $origin['name'],
            "<a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>" );
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Delete the colonization phantom.
    if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
}

// *** Recycle ***

function RecycleArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    if ( $fleet[GID_F_RECYCLER] == 0 ) Error ( "Попытка сбора ПО без переработчиков" );
    if ( $target['type'] != PTYP_DF ) Error ( "Перерабатывать можно только поля обломков!" );

    $sum_cargo = FleetCargoSummary ( $fleet ) - ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']);
    $recycler_cargo = FleetCargo (GID_F_RECYCLER) * $fleet[GID_F_RECYCLER];
    $cargo = min ($recycler_cargo, $sum_cargo);

    $harvest = HarvestDebris ( $target['planet_id'], $cargo, $queue['end'] );
    $dm = $harvest['m'];
    $dk = $harvest['k'];

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $subj = "\n<span class=\"espionagereport\">".loca_lang("FLEET_MESSAGE_INTEL", $origin_user['lang'])."</span>\n";   
    $report = va(loca_lang("FLEET_RECYCLE", $origin_user['lang']), 
        nicenum($fleet[GID_F_RECYCLER]),
        nicenum($cargo),
        nicenum($target['m']),
        nicenum($target['k']),
        nicenum($dm),
        nicenum($dk) );

    // Return the fleet.
    DispatchFleet ($fleet, $origin, $target, FTYP_RECYCLE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'] + $dm, $fleet_obj['k'] + $dk, $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);

    SendMessage ( $fleet_obj['owner_id'], loca_lang("FLEET_MESSAGE_FLEET", $origin_user['lang']), $subj, $report, MTYP_MISC, $queue['end']);
}

// *** Destroy ***

function DestroyArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Expedition ***

require_once "expedition.php";

// *** Missile attack ***

function RocketAttackArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    RocketAttack ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

function Queue_Fleet_End ($queue)
{
    global $GlobalUser;
    global $fleetmap;
    $fleet_obj = LoadFleet ( $queue['sub_id'] );
    if ( $fleet_obj == null ) return;

    if ( $fleet_obj['m'] < 0 ) $fleet_obj['m'] = 0;
    if ( $fleet_obj['k'] < 0 ) $fleet_obj['k'] = 0;
    if ( $fleet_obj['d'] < 0 ) $fleet_obj['d'] = 0;

    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    // Update resource production on planets
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );
    ProdResources ( $target, $target['lastpeek'], $queue['end'] );
    ProdResources ( $origin, $origin['lastpeek'], $queue['end'] );

    switch ( $fleet_obj['mission'] )
    {
        case FTYP_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_TRANSPORT: TransportArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_TRANSPORT+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DEPLOY: DeployArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DEPLOY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_HOLD: HoldingArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_ORBITING): HoldingHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_SPY: SpyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_SPY+FTYP_RETURN): SpyReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_COLONIZE: ColonizationArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_COLONIZE+FTYP_RETURN): ColonizationReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_RECYCLE: RecycleArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_RECYCLE+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DESTROY: DestroyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DESTROY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_EXPEDITION: ExpeditionArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_ORBITING): ExpeditionHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_MISSILE: RocketAttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK_HEAD: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        //default: Error ( "Неизвестное задание для флота: " . $fleet_obj['mission'] ); break;
    }

    if ( $fleet_obj['union_id'] && $fleet_obj['mission'] < FTYP_RETURN )    // remove all fleets and union missions so that ACS attack will no longer trigger
    {
        $union_id = $fleet_obj['union_id'];
        $result = EnumUnionFleets ( $union_id );
        $rows = dbrows ($result);
        while ($rows--)
        {
            $fleet_obj = dbarray ($result);
            $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
            DeleteFleet ($fleet_obj['fleet_id']);    // delete fleet
            RemoveQueue ( $queue['task_id'] );    // delete task
        }
        RemoveUnion ( $union_id );    // delete union
    }
    else
    {
        DeleteFleet ($fleet_obj['fleet_id']);    // delete fleet
        RemoveQueue ( $queue['task_id'] );    // delete task
    }

    $player_id = $fleet_obj['owner_id'];
    if ( $GlobalUser['player_id'] == $player_id) { 
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }
}

// ==================================================================================

// ACS Management.

// Create ACS union. $fleet_id - head fleet. $name - union name.
function CreateUnion ($fleet_id, $name)
{
    global $db_prefix;

    $fleet_obj = LoadFleet ($fleet_id);

    // Check to see if there's already an union?
    if ( $fleet_obj['union_id'] != 0 ) return $fleet_obj['union_id'];

    // Unions can only be created for departing attacks.
    if ($fleet_obj['mission'] != 1) return 0;

    $target_planet = GetPlanet ( $fleet_obj['target_planet'] );
    $target_player = $target_planet['owner_id'];

    // You can't create an union against yourself
    if ( $target_player == $fleet_obj['owner_id'] ) return 0;

    // Add union
    $union = array ( null, $fleet_id, $target_player, $name, $fleet_obj['owner_id'] );
    $union_id = AddDBRow ($union, 'union');

    // Add a fleet to the union and change the Attack type (the ACS head is shown in a special way in the event list)
    $query = "UPDATE ".$db_prefix."fleet SET union_id = $union_id, mission = ".FTYP_ACS_ATTACK_HEAD." WHERE fleet_id = $fleet_id";
    dbquery ($query);
    return $union_id;
}

// Load ACS union
function LoadUnion ($union_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."union WHERE union_id = $union_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return null;
    $union = dbarray ($result);
    $union['player'] = explode (",", $union['players'] );
    $union['players'] = count ($union['player']);
    return $union;
}

// An union is removed when the last union fleet is recalled, or the objective is reached
function RemoveUnion ($union_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."union WHERE union_id = $union_id";        // delete the union record
    dbquery ($query);
}

// Rename the ACS union.
function RenameUnion ($union_id, $name)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."union SET name = '".$name."' WHERE union_id = " . intval ($union_id);
    dbquery ($query);
}

// Add a new member to the union.
function AddUnionMember ($union_id, $name)
{
    global $db_prefix;
    global $GlobalUni;
    global $GlobalUser;
    $union = LoadUnion ($union_id);

    // The error of adding a player to ACS union is given in the language of the current user (the one who adds players via the Fleet menu)
    loca_add ("union", $GlobalUser['lang']);

    // Empty name, do nothing.
    if ($name === "") return "";

    // Maximum number of users reached
    $max_players = $GlobalUni['acs'] + 1;
    if ( $union['players'] >= $max_players ) return va(loca("ACS_MAX_USERS"), $max_players);

    // Find a user
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return loca("ACS_USER_NOT_FOUND");
    $user = dbarray ($result);

    // Check if there is already such a user in ACS union.
    for ($i=0; $i<$union['players']; $i++)
    {
        if ( $union["player"][$i] == $user['player_id'] ) return loca("ACS_ALREADY_ADDED");    // there is.
    }

    // Add the user to the ACS union and send them an invitation message.
    $union['player'][$union['players']] = $user['player_id'];
    $query = "UPDATE ".$db_prefix."union SET players = '".implode(",", $union['player'])."' WHERE union_id = $union_id";
    dbquery ($query);

    $target_player = LoadUser ( $union['target_player'] );
    $head_fleet = LoadFleet ( $union['fleet_id'] );
    $target_planet = GetPlanet ( $head_fleet['target_planet'] );
    $queue = GetFleetQueue ( $union['fleet_id'] );

    // The ACS invitation message is sent in the language of the invited user.
    loca_add ("union", $user['lang']);

    $text = va ( loca_lang("ACS_INVITE_TEXT1", $user['lang']),
                        $GlobalUser['oname'], 
                        $union['name'], 
                        $target_player['oname'] ) .
            va (" <a href=\"#\" onClick=showGalaxy(#1,#2,#3)><b><u>[#4:#5:#6]</u></b></a>. ",
                        $target_planet['g'], $target_planet['s'], $target_planet['p'], 
                        $target_planet['g'], $target_planet['s'], $target_planet['p'] ) .
            va ( loca_lang("ACS_INVITE_TEXT2", $user['lang']), date ( "D M Y H:i:s", $queue['end'] ) );
    SendMessage ( $user['player_id'], $GlobalUser['oname'], loca_lang("ACS_INVITE_SUBJ", $user['lang']), $text, MTYP_MISC );

    return "";
}

// List the unions the player is in, as well as the union that the player is targeting (unless the friendly flag is set).
function EnumUnion ($player_id, $friendly=0)
{
    global $db_prefix;
    $count = 0;
    $unions = array ();
    $query = "SELECT * FROM ".$db_prefix."union ";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $union = dbarray ($result);
        $union['player'] = explode (",", $union['players'] );
        $union['players'] = count ($union['player']);
        for ($i=0; $i<$union['players']; $i++) {
            if ( $union["player"][$i] == $player_id || ( $union['target_player'] == $player_id && !$friendly )) { $unions[$count++] = $union; break; }
        }
    }
    return $unions;
}

// List the Union fleets
function EnumUnionFleets ($union_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE union_id = $union_id";
    return dbquery ( $query );
}

// Update the arrival time of all union fleets except fleet_id. Return the new arrival time of the union.
function UpdateUnionTime ($union_id, $end, $fleet_id, $force_set=false)
{
    global $db_prefix;
    $result = EnumUnionFleets ($union_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        if ( $fleet_obj['fleet_id'] == $fleet_id ) continue;
        $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
        $union_time = $queue['end'];
        $queue_id = $queue['task_id'];
        if ( $end > $union_time || $force_set )
        {
            $union_time = $end;
            $query = "UPDATE ".$db_prefix."queue SET end = $end WHERE task_id = $queue_id";
            dbquery ($query);
        }
    }
    return $union_time;
}

// Update fleet arrival time
function UpdateFleetTime ($fleet_id, $when)
{
    global $db_prefix;
    $queue = GetFleetQueue ($fleet_id);
    $queue_id = $queue['task_id'];
    $query = "UPDATE ".$db_prefix."queue SET end = $when WHERE task_id = $queue_id";
    dbquery ($query);
}

// List the fleets on hold
function GetHoldingFleets ($planet_id)
{
    global $db_prefix;
    $uni = LoadUniverse ();    // limit the number of fleets to the universe settings
    $max = max (0, $uni['acs'] * $uni['acs'] - 1);
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE mission = ".(FTYP_ORBITING+FTYP_ACS_HOLD)." AND target_planet = $planet_id LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

function IsPlayerInUnion ($player_id, $union)
{
    if ( $union == null ) return false;
    foreach ( $union['player'] as $i=>$pid )
    {
        if ( $pid == $player_id ) return true;
    }
    return false;
}

// Flight logs.

function FleetlogsMissionText ($num)
{
    if ($num >= FTYP_ORBITING)
    {
        $desc = "<a title=\"На планете\">(Д)</a>";
        $num -= FTYP_ORBITING;
    }
    else if ($num >= FTYP_RETURN)
    {
        $desc = "<a title=\"Возвращение к планете\">(В)</a>";
        $num -= FTYP_RETURN;
    }
    else $desc = "<a title=\"Уход на задание\">(У)</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

function FleetlogsFromPlayer ($player_id, $missions)
{
    global $db_prefix;

    if ( count ($missions) == 0 ) return null;

    $list = "";
    foreach ($missions as $i=>$num) {
        if ($i > 0) $list .= "OR ";
        $list .= "mission = $num ";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE (".$list.") AND owner_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function FleetlogsToPlayer ($player_id, $missions)
{
    global $db_prefix;

    if ( count ($missions) == 0 ) return null;

    $list = "";
    foreach ($missions as $i=>$num) {
        if ($i > 0) $list .= "OR ";
        $list .= "mission = $num ";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE (".$list.") AND owner_id <> target_id AND target_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function DumpFleet ($fleet)
{
    global $fleetmap;
    $result = "";
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount != 0 ) $result .= loca ("NAME_$gid") . " " . nicenum($amount) . " ";
    }
    return $result;
}

?>