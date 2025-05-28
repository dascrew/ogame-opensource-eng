<?php

// Bot Management.

require_once "botapi.php";        // API

// Global bot variables.
$BotID = 0;        // ordinal number of the current bot
$BotNow = 0;       // start time of bot task execution

// Add a block to the queue
function AddBotQueue ($player_id, $strat_id, $block_id, $when, $seconds)
{
    $queue = array ( '', $player_id, 'AI', $strat_id, $block_id, 0, $when, $when+$seconds, 1000 );
    return AddDBRow ( $queue, 'queue' );
}

// Block Interpreter
function ExecuteBlock ($queue, $block, $childs )
{
    global $db_prefix, $BotID, $BotNow;

    $BotNow = $queue['end'];
    $BotID = $queue['owner_id'];
    $strat_id = $queue['sub_id'];

    // Trace block execution
    $bot_trace = false;

    if ($bot_trace) {
        Debug ( "Bot trace : " . $block['category'] . "(".$block['key']."): " . $block['text'] );
    }

    switch ( $block['category'] )
    {
        case "Start":
            $block_id = $childs[0]['to'];
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "End":
            RemoveQueue ( $queue['task_id'] );    // Simply remove the block, thus no AI executable strategy AI tasks are left in the queue
            break;

        case "Label":     // Start execution of a new block chain
            // Select from all descendants the one that comes from the bottom of the block (fromPort="B")
            $block_id = $childs[0]['to'];
            foreach ( $childs as $i=>$child ) {
                if ( $child['fromPort'] === "B" ) {
                    $block_id = $child['to'];
                    break;
                }            
            }
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "Branch":    // Jumps to another label with the specified text.
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $strat_id LIMIT 1";
            $result = dbquery ($query);
            if ($result) {
                $row = dbarray ($result);
                $strat = json_decode ( $row['source'], true );
                $done = false;
                foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
                    if ( $arr['text'] === $block['text'] && $arr['category'] === "Label" ) {
                        AddBotQueue ( $BotID, $strat_id, $arr['key'], $BotNow, 0 );
                        $done = true;
                        break;
                    }
                }
                if (!$done) Debug ( "Не удалось найти метку перехода \"".$block['text']."\"" );
            }
            else Debug ( "Failed to load the current strategy while processing the transition." );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "Cond":        // Condition check
            $result = eval ( "return ( " . $block['text'] . " );" );
            $block_id = $block_no = 0xdeadbeef;
            $prefix = "";
            foreach ( $childs as $i=>$child ) {
                if ( strtolower ($child['text']) === "no" ) {
                    if ( $result == false ) {
                        if ($bot_trace) {
                            Debug ($block['text'] . " : ".$prefix."NO");
                        }
                        $block_id = $child['to']; break;
                    }
                    else $block_no = $child['to'];
                }
                if ( strtolower ($child['text']) === "yes" && $result == true ) {
                    if ($bot_trace)
                        Debug ($block['text'] . " : YES");
                    $block_id = $child['to']; break;
                }
                if ( preg_match('/([0-9]{1,2}|100)%/', $child['text'], $matches) && $result == true ) {    // random jump
                    $prc = str_replace ( "%", "", $matches[0]);
                    $roll = mt_rand (1, 100);
                    if ( $roll <= $prc ) {
                        if ($bot_trace) {
                            Debug ($block['text'] . " : PROBABLY($roll/$prc) YES");
                        }
                        $block_id = $child['to']; break;
                    }
                    else {
                        if ( $block_no == 0xdeadbeef ) {
                            $prefix = "PROBABLY($roll/$prc) ";
                            $result = false;
                        }
                        else {
                            if ($bot_trace) {
                                Debug ($block['text'] . " : PROBABLY($roll/$prc) NO");
                            }
                            $block_id = $block_no; break;
                        }
                    }
                }    // random jump
            }
            if ( $block_id != 0xdeadbeef ) AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            else Debug ( "Failed to select conditional branch." );
            RemoveQueue ( $queue['task_id'] );
            break;

        default:    // Regular block, single output.
            $sleep = eval ( $block['text'] . ";" );
            if ( $sleep == null ) $sleep = 1;
            $block_id = $childs[0]['to'];
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, $sleep );
            RemoveQueue ( $queue['task_id'] );
            break;
    }
}

// Add bot.
function AddBot ($name)
{
    global $db_prefix;

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
        return true;
    }
    else return false;
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

?>