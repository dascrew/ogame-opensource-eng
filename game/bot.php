<?php

// Bot Management.

require_once "botapi.php";
require_once "personality.php";

// Global bot variables.
$BotID = 0;        // ordinal number of the current bot
$BotNow = 0;       // start time of bot task execution

// Add a block to the queue
function AddBotQueue ($player_id, $strat_id, $block_id, $when, $seconds)
{
    $queue = array ( '', $player_id, 'AI', $strat_id, $block_id, 0, $when, $when+$seconds, 1000 );
    return AddDBRow ( $queue, 'queue' );
}

function shouldTraceBlock() {
    global $BOT_TRACE_ENABLED, $BOT_TRACE_SAMPLING;
    return $BOT_TRACE_ENABLED && rand(1, 100) <= $BOT_TRACE_SAMPLING;
}

function debugBlockTrace($block) {
    Debug(sprintf("[TRACE] Block %s (%s): %s | Vars: %s",
        $block['key'],
        $block['category'],
        $block['text'],
        json_encode([
            'personality' => BotGetVar('personality'),
            'subtype' => BotGetVar('subtype')
        ])
    ));
}

function handleBranchBlock($queue, $block, $BotID, $strat_id, $db_prefix, $BotNow) {
    $result = dbquery("SELECT source FROM {$db_prefix}botstrat WHERE id = $strat_id");
    
    if (!$result) {
        Debug("Branch failed: Strategy $strat_id not found");
        RemoveQueue($queue['task_id']);
        return;
    }

    $strat = json_decode(dbarray($result)['source'], true);
    $targetLabel = trim($block['text']);
    
    foreach ($strat['nodeDataArray'] as $node) {
        if ($node['category'] === "Label" && $node['text'] === $targetLabel) {
            AddBotQueue($BotID, $strat_id, $node['key'], $BotNow, 0);
            RemoveQueue($queue['task_id']);
            return;
        }
    }
    
    Debug("Branch failed: Label '$targetLabel' not found");
    RemoveQueue($queue['task_id']);
}

function handleCondBlock($queue, $block, $childs, $BotID, $strat_id, $BotNow, $PERSONALITIES) {
    $result = evaluateCondition(
        trim($block['text']),
        BotGetVar('personality', 'default'),
        $PERSONALITIES
    );

    $nextBlock = match ($result) {
        true => findChildBlock($childs, 'yes'),
        false => findChildBlock($childs, 'no')
    };

    if ($nextBlock) {
        AddBotQueue($BotID, $strat_id, $nextBlock['to'], $BotNow, 0);
    } else {
        Debug("Cond: No valid path for ".($result ? 'yes' : 'no'));
    }

    RemoveQueue($queue['task_id']);
}

function handleActionBlock($queue, $block, $childs, $BotID, $strat_id, $BotNow, $PERSONALITIES) {
    $personality = BotGetVar('personality', 'default');
    $config = $PERSONALITIES[$personality] ?? [];

    $sleep = 0;

    switch (trim($block['text'])) {
        case 'BUILD':
            $sleep = executeBuildAction($config);
            break;
        case 'RESEARCH':
            $sleep = executeResearchAction($config);
            break;
        case 'BUILD_FLEET':
            $sleep = BotBuildFleetAction($queue['params']);
            break;
        default:
            $sleep = handleCustomAction($block['text']);
            break;
    }

    if ($sleep >= 0 && !empty($childs)) {
        AddBotQueue($BotID, $strat_id, $childs[0]['to'], $BotNow, $sleep);
    }

    RemoveQueue($queue['task_id']);
}

function checkBuildablePriority($config) {
    if (empty($config['priority_buildings'])) {
        Debug("No priority buildings in config");
        return false;
    }

    foreach ($config['priority_buildings'] as $buildingId) {
        $current = BotGetBuild($buildingId);
        $cap = $config['building_caps'][$buildingId] ?? 999;
        
        if ($current < $cap && BotCanBuild($buildingId)) {
            Debug("Priority building $buildingId selected (current: $current, cap: $cap)");
            return $buildingId;
        }
    }
    
    Debug("All priority buildings at cap or unavailable");
    return false;
}

function executeBuildAction($config) {
    foreach ($config['priority_buildings'] ?? [] as $id) {
        if (BotCanBuild($id)) {
            return BotBuild($id);
        }
    }
    return 0;
}

function findChildBlock($children, $type) {
    $type = strtolower($type);
    
    foreach ($children as $child) {
        $childText = strtolower(trim($child['text']));
        
        if ($type === 'random' && preg_match('/^\d{1,3}%$/', $childText)) {
            return $child;
        }
        
        if ($childText === $type) {
            return $child;
        }
    }
    
    return null;
}


function evaluateCondition($text, $personality, $PERSONALITIES) {
    $config = $PERSONALITIES[$personality] ?? [];
    
    // Implement condition logic from original ExecuteBlock
    switch ($text) {
        case 'CAN_BUILD':
            return checkBuildablePriority($config);
        case 'CAN_RESEARCH':
            return checkResearchablePriority($config);
        // ... other condition cases
        default:
            return @eval("return ($text);");
    }
}

function handleCustomAction($code) {
    // Allowlist of safe commands
    $allowedCommands = [
        'BUILD_FLEET' => function($params) {
            $shipTypeId = $params[0] ?? -1;
            $amount = $params[1] ?? 0;
            return BotBuildFleet($shipTypeId, $amount);
        }
    ];
    
    // Parse command pattern: COMMAND(param1, param2)
    if (preg_match('/^(\w+)\s*\(([^)]*)\)$/', $code, $matches)) {
        $command = strtoupper(trim($matches[1]));
        $params = array_map('trim', explode(',', $matches[2]));
        
        if (isset($allowedCommands[$command])) {
            Debug("Executing allowed command: $command");
            return $allowedCommands[$command]($params);
        }
    }
    
    // Fallback to eval with sanitization
    $sanitizedCode = preg_replace('/[^a-zA-Z0-9_\(\)\-\+\*\/%\s]/', '', $code);
    Debug("Executing custom code: $sanitizedCode");
    
    $result = @eval("return ($sanitizedCode);");
    
    if ($result === false || error_get_last() !== null) {
        Debug("Custom action error in: $sanitizedCode - " . error_get_last()['message']);
        return 0;
    }
    
    return (int)$result;
}


// Block Interpreter
function ExecuteBlock($queue, $block, $childs)
{
    global $db_prefix, $BotID, $BotNow, $PERSONALITIES;

    $BotNow = $queue['end'];
    $BotID = $queue['owner_id'];
    $strat_id = $queue['sub_id'];

    if (shouldTraceBlock()) {
        debugBlockTrace($block);
    }

    switch ($block['category']) {
        case "Start":
            handleStartBlock($queue, $childs, $BotID, $strat_id, $BotNow);
            break;
        case "End":
            handleEndBlock($queue);
            break;
        case "Label":
            handleLabelBlock($queue, $childs, $BotID, $strat_id, $BotNow);
            break;
        case "Branch":
            handleBranchBlock($queue, $block, $BotID, $strat_id, $db_prefix, $BotNow);
            break;
        case "Cond":
            handleCondBlock($queue, $block, $childs, $BotID, $strat_id, $BotNow, $PERSONALITIES);
            break;
        default:
            handleActionBlock($queue, $block, $childs, $BotID, $strat_id, $BotNow, $PERSONALITIES);
            break;
    }
}

// Add bot.
function AddBot ($name)
{
    global $db_prefix, $PERSONALITIES;

    // Generate password
    $len = 8;
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    $pass = $r;

    if ( !IsUserExist ($name) ) {
        $player_id = CreateUser ( $name, $pass, '', true );
        $query = "UPDATE ".$db_prefix."users SET validatemd = '', validated = 1 WHERE player_id = " . $player_id;
        dbquery ($query);
        StartBot ( $player_id );
        SetVar ( $player_id, 'password', $pass );
        $personalities = array_keys($PERSONALITIES);
        $personality = $personalities[array_rand($personalities)];
        $subtypes = array_keys($PERSONALITIES[$personality]);
        $subtype = $subtypes[array_rand($subtypes)];
        BotSetVar('personality', $personality);
        BotSetVar('subtype', $subtype);
        return true;
    }
    return false;
}

// Start the bot (execute the Start block for the _start strategy)
function StartBot ($player_id)
{
    global $BotID, $BotNow;

    $BotID = $player_id;
    $BotNow = time ();

    if ( BotExec("_start") == 0 ) Debug ( "Starting strategy not found." );
    else
    {
        $query = "SELECT * FROM queue WHERE type = 'AI' AND owner_id = $player_id ORDER BY task_id LIMIT 1";
        $result = dbquery ($query);
        if ( dbrows ($result) > 0 ) {
            $row = dbarray ($result);
            Queue_Bot_End ($row);
        }
    }
}

// Stop the bot (just remove all AI tasks)
function StopBot ($player_id)
{
    global $db_prefix;
    if ( IsBot ($player_id) ) 
    {
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'AI' AND owner_id = $player_id";
        dbquery ($query);
    }
}

// Check if the player is a bot.
function IsBot ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' AND owner_id = $player_id";
    $result = dbquery ($query);
    return ( dbrows ($result) > 0 ) ;
}

// Task completion event for the bot. Called from queue.php
// Activate the bot's task parser.
function Queue_Bot_End ($queue)
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = ".$queue['sub_id']." LIMIT 1";
    $result = dbquery ($query);
    if ($result) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['key'] == $queue['obj_id'] ) {
                $block = $arr;

                $childs = array ();
                foreach ( $strat['linkDataArray'] as $i=>$arr ) {
                    if ( $arr['from'] == $block['key'] ) $childs[] = $arr;
                }

                ExecuteBlock ($queue, $block, $childs );
                break;
            }
        }

    }
    else Debug ( "Не удалось загрузить программу " . $queue['sub_id'] );
}

// Bot Variables.

function GetVar ( $owner_id, $var, $def_value=null )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botvars WHERE var = '".$var."' AND owner_id = $owner_id LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $var = dbarray ( $result );
        return $var['value'];
    }
    else
    {
        $var = array ( '', $owner_id, $var, $def_value );
        AddDBRow ( $var, 'botvars' );
        return $def_value;
    }
}

function SetVar ( $owner_id, $var, $value )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botvars WHERE var = '".$var."' AND owner_id = $owner_id LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $query = "UPDATE ".$db_prefix."botvars SET value = '".$value."' WHERE var = '".$var."' AND owner_id = $owner_id;";
        dbquery ($query);
    }
    else
    {
        $var = array ( '', $owner_id, $var, $value );
        AddDBRow ( $var, 'botvars' );
    }
}