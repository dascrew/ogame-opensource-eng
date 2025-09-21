<?php

function BotAllianceCreate($creator_bot_id) {
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

function BotAllianceRemoveMember($bot_id) {
    global $db_prefix;
    $user_data = LoadUser($bot_id);
    if (!$user_data || $user_data['ally_id'] == 0) return;
    
    $alliance_id = $user_data['ally_id'];
    
    dbquery("UPDATE ".$db_prefix."users SET ally_id = 0, joindate = 0, allyrank = 0 WHERE player_id = $bot_id");

    AllianceUpdateAllDynamicData($alliance_id);

    Debug("AllianceRemoveMember: Removed bot {$bot_id} from alliance {$alliance_id}.");
}

function BotCheckAllianceRequirements($bot_id, $alliance_id) {
    $bot_data = LoadUser($bot_id);
    $ally_data = LoadAlly($alliance_id);
    if (!$bot_data || !$ally_data) return false;
    
    $leader_id = $ally_data['owner_id'];
    $reqs_json = BotGetVar($leader_id, 'alliance_reqs_json', '[]');
    $reqs = json_decode($reqs_json, true);

    if (empty($reqs)) return true;

    if ($bot_data['place1'] > $reqs['min_overall_rank']) return false;
    if (BotCalculateSkillScore($bot_id) < $reqs['min_skill']) return false;
    
    return true;
}

