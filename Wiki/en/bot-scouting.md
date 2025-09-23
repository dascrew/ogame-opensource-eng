# Bot Scouting Functionality

## Overview

The bot scouting functionality allows bots to automatically scout nearby systems by sending espionage probes to inactive player planets. This feature helps bots gather intelligence on potential targets while avoiding strong players and respecting newbie protection.

## Galaxy Map Player Indicators

The existing galaxy map displays important player status indicators that the bot scouting system uses for target filtering:

- **I** - Inactive player (not logged in for 7+ days)
- **s** - Strong player (significantly higher score, protected)
- **n** - Newbie/Noob player (low score, protected)
- **v** - Player in vacation mode
- **b** - Banned player

## SCOUT Action

The `SCOUT` action can be used in bot strategies to perform automated scouting missions.

### Basic Usage

```
SCOUT
```

This will:
- Scan systems within 100 systems of the current planet
- Target only inactive players (7+ days inactive)
- Avoid newbie and strong players
- Send a single probe to a randomly selected target
- Return appropriate wait times based on success/failure

### Advanced Usage with Parameters

The SCOUT action accepts optional parameters to customize its behavior:

**Parameter 0: Range** (Integer, default: 100)
- Specifies the number of systems to scan in each direction
- Minimum: 1 system, Maximum: 500 systems  
- Example: `SCOUT` with params `[50]` scans 50 systems each way

**Future Parameters** (Reserved for expansion)
- Additional filtering options
- Target prioritization settings
- Custom inactive thresholds

### How It Works

1. **Range Calculation**: Scans 100 systems in each direction from the bot's active planet
2. **Target Filtering**: Uses the same logic as the galaxy display to identify:
   - Inactive players (I indicator)
   - Avoids strong players (s indicator) 
   - Avoids newbie players (n indicator)
   - Skips players in vacation mode
   - Skips banned players

3. **Resource Checking**: Verifies the bot has:
   - At least 1 espionage probe
   - Available fleet slots
   - Sufficient deuterium for the mission

4. **Mission Execution**: Sends 1 probe to scout the selected target

## Target Selection Criteria

### Default Filters (Built-in)
- `avoid_newbie`: true - Skip players under newbie protection
- `avoid_strong`: true - Skip players under strong player protection  
- `target_inactive`: true - Only target inactive players
- `min_inactive_days`: 7 - Minimum days of inactivity required

### Player Status Detection

The bot uses the existing player status functions:
- `IsPlayerNewbie($player_id)` - Detects newbie protection
- `IsPlayerStrong($player_id)` - Detects strong player protection
- Checks `lastclick` timestamp for inactivity
- Checks `vacation` and `banned` flags

## Return Values

The SCOUT action returns different wait times based on the outcome:

- **10 seconds** - Mission sent successfully
- **60 seconds** - No probes available
- **300 seconds** (5 minutes) - No fleet slots or mission failed
- **600 seconds** (10 minutes) - No suitable targets found

## Implementation Details

### Key Functions

#### `BotScout($range, $filters)`
Main scouting function that orchestrates the entire process.

#### `BotFindScoutTargets($origin, $range, $filters)`
Scans systems within range to find suitable targets.

#### `BotIsValidScoutTarget($user, $planet, $filters)`
Applies filtering logic to determine if a planet is a valid target.

#### `BotSendScoutMission($origin, $target)`
Handles the actual fleet dispatch and resource management.

### Distance and Flight Calculation

The system uses simplified distance calculations:
- **Galaxy distance**: `abs(g1 - g2) * 20000`
- **System distance**: `abs(s1 - s2) * 5 * 19 + 2700`
- **Planet distance**: `abs(p1 - p2) * 5 + 1000`

### Fuel Consumption

Fuel consumption is calculated based on distance with a base consumption of 1 deuterium per probe, modified by distance factors.

## Example Bot Strategy

### Basic Continuous Scouting
```json
{
  "nodeDataArray": [
    {"key": 1, "category": "Start", "text": "Start"},
    {"key": 2, "category": "Action", "text": "SCOUT"},
    {"key": 3, "category": "Action", "text": "SCOUT"}
  ],
  "linkDataArray": [
    {"from": 1, "to": 2},
    {"from": 2, "to": 3},
    {"from": 3, "to": 2}
  ]
}
```

### Advanced Scouting with Range Control
The SCOUT action can accept parameters to customize behavior:

- **Parameter 0**: Range in systems (default: 100)

Example with custom range of 50 systems:
```json
{
  "nodeDataArray": [
    {"key": 1, "category": "Start", "text": "Start"},
    {"key": 2, "category": "Action", "text": "SCOUT", "params": [50]}
  ],
  "linkDataArray": [
    {"from": 1, "to": 2}
  ]
}
```

## Error Handling

The system includes comprehensive error handling:
- Validates user and planet data
- Checks resource availability
- Handles fleet slot limitations
- Provides debug logging for troubleshooting

## Performance Considerations

- Scans are limited to 100 systems in each direction
- Random target selection prevents predictable patterns
- Appropriate wait times prevent excessive system load
- Failed missions trigger longer wait periods

## Safety Features

- Respects newbie and strong player protections
- Only targets inactive players by default
- Validates all coordinates and player data
- Prevents sending to invalid or protected targets
- **Self-exclusion**: Bots will never target their own planets
- **Range limits**: Range parameter is constrained between 1-500 systems
- **Target limits**: Maximum of 100 targets per scan to prevent system overload
- **Resource validation**: Checks probe availability and deuterium before sending