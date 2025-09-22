# Bot Administration Guide

This guide covers the administration and management of bots in the OGame engine, including creation, monitoring, and strategy management.

## Table of Contents

1. [Admin Panel Access](#admin-panel-access)
2. [Bot Management](#bot-management)
3. [Strategy Editor](#strategy-editor)
4. [Monitoring and Debugging](#monitoring-and-debugging)
5. [Database Management](#database-management)
6. [Configuration](#configuration)

---

## Admin Panel Access

### Requirements
- Admin level 2 or higher (`$GlobalUser['admin'] >= 2`)
- Access to admin interface via `index.php?page=admin`

### Bot-Related Admin Pages
- **Bots:** `&mode=Bots` - Bot management and control
- **Bot Edit:** `&mode=Botedit` - Strategy creation and editing

---

## Bot Management

### Adding New Bots

#### Via Admin Interface
1. Navigate to Admin → Bots
2. Ensure `_start` strategy exists
3. Enter bot name in the form
4. Submit to create bot

#### Programmatically
```php
if (BotStrategyExists("_start")) {
    if (AddBot($bot_name)) {
        // Bot created successfully
    } else {
        // Bot creation failed (user exists or other error)
    }
} else {
    // _start strategy required
}
```

### Bot Creation Process
1. **Username Check:** Verify username doesn't exist
2. **Password Generation:** Random 8-character password
3. **User Creation:** Create user account with bot flag
4. **Planet Setup:** Create home planet
5. **Validation:** Mark account as validated
6. **Strategy Start:** Execute `_start` strategy
7. **Personality Assignment:** Random personality and subtype
8. **Variable Storage:** Store password and personality

### Viewing Active Bots

The admin interface displays:
- Bot ID (player_id)
- Bot name
- Home planet name and coordinates
- Action buttons (Stop)

```php
// Query for active bots
$query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' GROUP BY owner_id";
```

### Stopping Bots

#### Via Admin Interface
Click "Stop" link next to bot entry

#### Programmatically
```php
StopBot($player_id);  // Removes all AI tasks for the bot
```

### Bot Status Check
```php
function IsBot($player_id) {
    // Check if player is a bot
    $user = LoadUser($player_id);
    return $user['bot'] == 1;  // Assuming bot flag exists
}
```

---

## Strategy Editor

### Accessing the Editor
Navigate to Admin → Bot Edit to access the visual strategy editor.

### Strategy Management Operations

#### Creating New Strategy
```javascript
// Via AJAX POST
{
    action: "new",
    name: "strategy_name"
}
```

#### Saving Strategy
```javascript
// Via AJAX POST
{
    action: "save",
    strat: strategy_id,
    source: json_encoded_strategy
}
```

#### Renaming Strategy
```javascript
// Via AJAX POST
{
    action: "rename",
    strat: strategy_id,
    name: "new_name"
}
```

#### Loading Strategy
```javascript
// Via AJAX GET
index.php?page=admin&mode=Botedit&action=load&strat=strategy_id
```

#### Previewing Strategy
```javascript
// Via AJAX GET
index.php?page=admin&mode=Botedit&action=preview&strat=strategy_id
```

### Strategy Backup System
When saving a strategy, the previous version is automatically backed up to strategy ID 1.

### Database Schema

#### botstrat Table
```sql
CREATE TABLE botstrat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    source TEXT  -- JSON strategy data
);
```

---

## Monitoring and Debugging

### Bot Activity Monitoring

#### Active Bot Query
```php
$query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI'";
```

#### Bot Task Queue
```php
function GetBotQueue($player_id) {
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue 
              WHERE type = 'AI' AND owner_id = $player_id 
              ORDER BY end ASC";
    return dbquery($query);
}
```

### Debug Tracing

#### Enable Bot Tracing
```php
$BOT_TRACE_ENABLED = true;
$BOT_TRACE_SAMPLING = 10;  // 10% of blocks traced
```

#### Trace Function
```php
function shouldTraceBlock() {
    global $BOT_TRACE_ENABLED, $BOT_TRACE_SAMPLING;
    return $BOT_TRACE_ENABLED && rand(1, 100) <= $BOT_TRACE_SAMPLING;
}
```

#### Debug Output
```php
function debugBlockTrace($block) {
    Debug("Executing block: " . $block['category'] . " - " . $block['text']);
}
```

### Bot Performance Monitoring

#### Resource Tracking
```php
function LogBotResources($bot_id) {
    $user = LoadUser($bot_id);
    $planet = GetPlanet($user['aktplanet']);
    Debug("Bot $bot_id resources: M{$planet['m']} K{$planet['k']} D{$planet['d']}");
}
```

#### Queue Statistics
```php
function GetBotQueueStats() {
    global $db_prefix;
    $query = "SELECT 
                COUNT(*) as total_tasks,
                COUNT(DISTINCT owner_id) as active_bots,
                AVG(end - start) as avg_task_duration
              FROM ".$db_prefix."queue WHERE type = 'AI'";
    return dbquery($query);
}
```

---

## Database Management

### Core Tables

#### queue Table (AI Tasks)
```sql
CREATE TABLE queue (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT,           -- Bot player ID
    type VARCHAR(10),       -- 'AI' for bot tasks
    tech_id INT,            -- Unused for AI
    obj_id INT,             -- Block ID being executed
    sub_id INT,             -- Strategy ID
    start INT,              -- Task start time
    end INT,                -- Task completion time
    params TEXT             -- Serialized parameters
);
```

#### botstrat Table (Strategies)
```sql
CREATE TABLE botstrat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),      -- Strategy name
    source TEXT             -- JSON strategy definition
);
```

#### users Table (Bot Accounts)
```sql
-- Standard user table with bot-specific fields
bot TINYINT DEFAULT 0,      -- Bot flag
validatemd VARCHAR(32),     -- Empty for bots
validated TINYINT DEFAULT 1 -- Always validated for bots
```

### Database Maintenance

#### Clean Up Completed Tasks
```php
function CleanupBotTasks() {
    global $db_prefix;
    $now = time();
    $query = "DELETE FROM ".$db_prefix."queue 
              WHERE type = 'AI' AND end < $now";
    dbquery($query);
}
```

#### Archive Old Strategies
```php
function ArchiveOldStrategies() {
    global $db_prefix;
    $query = "CREATE TABLE botstrat_archive AS 
              SELECT * FROM ".$db_prefix."botstrat";
    dbquery($query);
}
```

#### Bot Statistics
```php
function GetBotStatistics() {
    global $db_prefix;
    $stats = [];
    
    // Count active bots
    $query = "SELECT COUNT(DISTINCT owner_id) as count 
              FROM ".$db_prefix."queue WHERE type = 'AI'";
    $result = dbquery($query);
    $stats['active_bots'] = dbarray($result)['count'];
    
    // Count total strategies
    $query = "SELECT COUNT(*) as count FROM ".$db_prefix."botstrat";
    $result = dbquery($query);
    $stats['total_strategies'] = dbarray($result)['count'];
    
    return $stats;
}
```

---

## Configuration

### Bot Configuration Variables

#### Global Settings
```php
// Enable/disable bot tracing
$BOT_TRACE_ENABLED = false;
$BOT_TRACE_SAMPLING = 10;

// Bot creation limits
$MAX_BOTS_PER_UNIVERSE = 1000;
$BOT_PASSWORD_LENGTH = 8;

// Strategy execution settings
$BOT_MAX_EXECUTION_TIME = 300;  // 5 minutes
$BOT_QUEUE_BATCH_SIZE = 100;
```

#### Personality Configuration
Located in `personality.php`:
```php
$PERSONALITIES = [
    'miner' => [...],
    'fleeter' => [...],
    // Additional personalities
];
```

### Server Settings

#### Queue Processing
```php
// In queue processing script
function ProcessBotQueue() {
    global $db_prefix;
    $now = time();
    
    $query = "SELECT * FROM ".$db_prefix."queue 
              WHERE type = 'AI' AND end <= $now 
              ORDER BY end ASC LIMIT 10";
    
    $result = dbquery($query);
    while ($row = dbarray($result)) {
        ExecuteBotTask($row);
    }
}
```

#### Performance Tuning
```php
// Adjust based on server capacity
$BOT_QUEUE_INTERVAL = 60;     // Process queue every 60 seconds
$BOT_CONCURRENT_LIMIT = 5;    // Max 5 bots processing simultaneously
$BOT_MEMORY_LIMIT = '256M';   // Memory limit for bot processing
```

### Security Considerations

#### Bot Account Security
- Bots use randomly generated passwords
- Accounts are marked as validated immediately
- No email verification required
- Bot flag prevents normal login

#### Strategy Security
- Admin level 2+ required for strategy editing
- Strategy modifications apply to all bots immediately
- Backup system prevents accidental loss
- JSON validation prevents malformed strategies

#### Resource Protection
- Bots follow same game rules as players
- No resource generation from nothing
- Energy and building requirements enforced
- Queue system prevents instant actions

---

## Troubleshooting

### Common Issues

#### Bots Not Starting
1. Check if `_start` strategy exists
2. Verify admin permissions
3. Check database connectivity
4. Ensure queue processing is running

#### Strategies Not Executing
1. Verify strategy JSON syntax
2. Check for infinite loops
3. Ensure proper block connections
4. Validate condition logic

#### Performance Issues
1. Monitor queue size and processing time
2. Check for stuck bots (old queue entries)
3. Verify memory and CPU usage
4. Optimize strategy complexity

### Diagnostic Commands

#### Check Bot Status
```php
function DiagnoseBotStatus($player_id) {
    $user = LoadUser($player_id);
    if (!$user) return "Bot not found";
    
    $planet = GetPlanet($user['aktplanet']);
    $queue_count = CountBotQueueItems($player_id);
    
    return [
        'bot_id' => $player_id,
        'name' => $user['name'],
        'planet' => $planet['name'],
        'coordinates' => "[".$planet['g'].":".$planet['s'].":".$planet['p']."]",
        'resources' => [
            'metal' => $planet['m'],
            'crystal' => $planet['k'],
            'deuterium' => $planet['d']
        ],
        'queue_items' => $queue_count,
        'personality' => BotGetVar('personality'),
        'subtype' => BotGetVar('subtype')
    ];
}
```

#### Strategy Validation
```php
function ValidateStrategy($strategy_id) {
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $strategy_id";
    $result = dbquery($query);
    $row = dbarray($result);
    
    $strategy = json_decode($row['source'], true);
    if (!$strategy) return "Invalid JSON";
    
    $has_start = false;
    foreach ($strategy['nodeDataArray'] as $node) {
        if ($node['category'] === 'Start') {
            $has_start = true;
            break;
        }
    }
    
    return $has_start ? "Valid" : "No Start block found";
}
```