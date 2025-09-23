# Galaxy Map Player Status Indicators

## Overview

The galaxy map in OGame displays critical player status information through visual indicators that help identify player types and their protection status. This system is now integrated with the bot scouting functionality to ensure proper target selection.

## Player Status Indicators

### Primary Indicators

#### **I** - Inactive Player
- **Display**: `<span class='inactive'>` in galaxy.php line 629
- **Criteria**: Player has not logged in for 7+ days (`$user['lastclick'] <= $week`)
- **Function**: Used in `IsPlayerNewbie()` and bot target filtering
- **Bot Behavior**: These players are **targeted** by scouting bots

#### **s** - Strong Player  
- **Display**: `<span class='strong'>` in galaxy.php line 622
- **Criteria**: Determined by `IsPlayerStrong($user['player_id'])` function
- **Protection**: Strong player protection active
- **Bot Behavior**: These players are **avoided** by scouting bots

#### **n** - Newbie/Noob Player
- **Display**: `<span class='noob'>` in galaxy.php line 618  
- **Criteria**: Determined by `IsPlayerNewbie($user['player_id'])` function
- **Protection**: Newbie protection active
- **Bot Behavior**: These players are **avoided** by scouting bots

### Secondary Indicators

#### **v** - Vacation Mode
- **Display**: `<span class='vacation'>` in galaxy.php line 632
- **Criteria**: `$user['vacation']` flag is set
- **Status**: Player is temporarily protected
- **Bot Behavior**: These players are **skipped** by scouting bots

#### **b** - Banned Player
- **Display**: `<span class='banned'>` in galaxy.php line 630
- **Criteria**: `$user['banned']` flag is set
- **Status**: Player account is suspended
- **Bot Behavior**: These players are **skipped** by scouting bots

#### **Long Inactive** 
- **Display**: `<span class='longinactive'>` in galaxy.php line 631
- **Criteria**: Player has not logged in for 28+ days (`$user['lastclick'] <= $week4`)
- **Status**: Extended inactivity period
- **Bot Behavior**: These players are **preferred targets** for scouting

## Protection System Logic

### Newbie Protection (`IsPlayerNewbie()`)
Located in `game/user.php` lines 440-454:

```php
function IsPlayerNewbie($player_id)
{
    global $GlobalUser;
    $user = LoadUser($player_id);
    $week = time() - 604800;
    if ($user['lastclick'] <= $week || $user['vacation'] || $user['banned']) 
        return false;
    
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p2 >= $p1 || $p2 >= USER_NOOB_LIMIT) return false;
    if ($p1 <= $p2*5) return false;
    return true;
}
```

**Protection Criteria:**
- Target player's score is below current player's score
- Target player's score is below `USER_NOOB_LIMIT`
- Current player's score is more than 5x target player's score
- Target is not inactive, in vacation, or banned

### Strong Player Protection (`IsPlayerStrong()`)
Located in `game/user.php` lines 455-469:

```php
function IsPlayerStrong($player_id)
{
    global $GlobalUser;
    $user = LoadUser($player_id);
    $week = time() - 604800;
    if ($user['lastclick'] <= $week || $user['vacation'] || $user['banned']) 
        return false;
    
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p1 >= $p2 || $p1 >= USER_NOOB_LIMIT) return false;
    if ($p2 <= $p1*5) return false;
    return true;
}
```

**Protection Criteria:**
- Current player's score is below target player's score
- Current player's score is below `USER_NOOB_LIMIT`
- Target player's score is more than 5x current player's score
- Target is not inactive, in vacation, or banned

## Galaxy Display Implementation

### Player Name Display
Located in `game/pages/galaxy.php` lines 616-636:

The galaxy page displays player names with appropriate styling based on their status:

```php
if (IsPlayerNewbie($user['player_id'])) {
    $pstat = "noob"; 
    $stat = "<span class='noob'>".loca("GALAXY_LEGEND_NOOB")."</span>";
}
else if (IsPlayerStrong($user['player_id'])) {
    $pstat = "strong"; 
    $stat = "<span class='strong'>".loca("GALAXY_LEGEND_STRONG")."</span>";
}
else {
    // Check for inactive, banned, vacation status
    if ($user['lastclick'] <= $week) { 
        $stat .= "<span class='inactive'>".loca("GALAXY_LEGEND_INACTIVE7")."</span>"; 
        $pstat = "inactive"; 
    }
    // Additional status checks...
}
```

### Legend Display
The galaxy page includes a legend (lines 707-713) showing all status types:

- Strong Player Protection
- Newbie Player Protection  
- Vacation Mode
- Banned Player
- 7-day Inactive
- 28-day Inactive

## Integration with Bot Scouting

### Target Selection Logic
The bot scouting system (`BotIsValidScoutTarget()`) uses these same functions:

```php
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
```

### Default Bot Behavior
- ✅ **Target**: Inactive players (7+ days)
- ❌ **Avoid**: Newbie protected players
- ❌ **Avoid**: Strong protected players  
- ❌ **Skip**: Players in vacation mode
- ❌ **Skip**: Banned players

## Fleet Mission Restrictions

### Spy Mission Validation
Located in `game/pages/flottenversand.php` line 243:

```php
if (IsPlayerNewbie($target['owner_id']) || IsPlayerStrong($target['owner_id'])) 
    FleetError(loca("FLEET_ERR_SPY_NOOB"));
```

This ensures that manual spy missions also respect protection rules.

## Constants and Configuration

### Time Constants
- **1 week**: `604800` seconds (7 days)
- **4 weeks**: `604800*4` seconds (28 days)

### Score Limits
- `USER_NOOB_LIMIT`: Configurable threshold for newbie protection
- Score multiplier: 5x ratio for protection calculations

## CSS Styling Classes

The galaxy display uses specific CSS classes for visual differentiation:
- `.noob` - Newbie protected players
- `.strong` - Strong protected players
- `.inactive` - 7-day inactive players
- `.longinactive` - 28-day inactive players
- `.vacation` - Players in vacation mode
- `.banned` - Banned players

This styling system provides immediate visual feedback to players about potential targets and protection status.