# Bot Examples and Tutorials

This document provides practical examples and step-by-step tutorials for creating effective bot strategies.

## Table of Contents

1. [Basic Examples](#basic-examples)
2. [Building Strategies](#building-strategies)
3. [Fleet Strategies](#fleet-strategies)
4. [Research Strategies](#research-strategies)
5. [Complete Bot Tutorials](#complete-bot-tutorials)
6. [Advanced Patterns](#advanced-patterns)

---

## Basic Examples

### Hello World Strategy
The simplest possible bot strategy that does nothing but demonstrates the basic structure.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "End",
      "text": "End",
      "loc": "200 100"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2,
      "fromPort": "B",
      "toPort": "T"
    }
  ]
}
```

**Flow:** Start → End

### Simple Wait Strategy
A strategy that waits for one hour and then ends.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "text": "sleep(3600)",
      "loc": "200 100"
    },
    {
      "key": 3,
      "category": "End",
      "text": "End",
      "loc": "300 100"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 2,
      "to": 3,
      "fromPort": "B",
      "toPort": "T"
    }
  ]
}
```

**Flow:** Start → Wait 1 hour → End

---

## Building Strategies

### Basic Building Loop
Continuously builds priority buildings based on bot personality.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "Label",
      "text": "build_loop",
      "loc": "200 100"
    },
    {
      "key": 3,
      "category": "Cond",
      "text": "CAN_BUILD",
      "loc": "300 100"
    },
    {
      "key": 4,
      "text": "BUILD",
      "loc": "400 50"
    },
    {
      "key": 5,
      "text": "sleep(3600)",
      "loc": "400 150"
    },
    {
      "key": 6,
      "category": "Branch",
      "text": "build_loop",
      "loc": "500 100"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 2,
      "to": 3,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 3,
      "to": 4,
      "fromPort": "L",
      "toPort": "T",
      "text": "yes"
    },
    {
      "from": 3,
      "to": 5,
      "fromPort": "R",
      "toPort": "T",
      "text": "no"
    },
    {
      "from": 4,
      "to": 6,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 5,
      "to": 6,
      "fromPort": "B",
      "toPort": "T"
    }
  ]
}
```

**Flow:** Start → Loop: Check if can build → Yes: Build → Branch back | No: Wait → Branch back

### Energy-Aware Building
Builds only when sufficient energy is available.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "Label",
      "text": "energy_check",
      "loc": "200 100"
    },
    {
      "key": 3,
      "category": "Cond",
      "text": "BotEnergyAbove(500)",
      "loc": "300 100"
    },
    {
      "key": 4,
      "category": "Cond",
      "text": "CAN_BUILD",
      "loc": "400 50"
    },
    {
      "key": 5,
      "text": "BUILD",
      "loc": "500 50"
    },
    {
      "key": 6,
      "text": "BotResourceSettings(100,100,50,100,100,100)",
      "loc": "400 150"
    },
    {
      "key": 7,
      "text": "sleep(1800)",
      "loc": "300 200"
    },
    {
      "key": 8,
      "category": "Branch",
      "text": "energy_check",
      "loc": "200 200"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 2,
      "to": 3,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 3,
      "to": 4,
      "fromPort": "L",
      "toPort": "T",
      "text": "yes"
    },
    {
      "from": 3,
      "to": 6,
      "fromPort": "R",
      "toPort": "T",
      "text": "no"
    },
    {
      "from": 4,
      "to": 5,
      "fromPort": "L",
      "toPort": "T",
      "text": "yes"
    },
    {
      "from": 4,
      "to": 7,
      "fromPort": "R",
      "toPort": "T",
      "text": "no"
    },
    {
      "from": 5,
      "to": 8,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 6,
      "to": 7,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 7,
      "to": 8,
      "fromPort": "B",
      "toPort": "T"
    }
  ]
}
```

**Flow:** Check energy → Sufficient: Check if can build → Build | Insufficient: Reduce deut production → Wait → Loop

---

## Fleet Strategies

### Basic Fleet Builder
Builds small cargo ships up to a specified limit.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "Label",
      "text": "fleet_check",
      "loc": "200 100"
    },
    {
      "key": 3,
      "category": "Cond",
      "text": "BotGetFleetCount(202) < 100",
      "loc": "300 100"
    },
    {
      "key": 4,
      "text": "BotBuildFleet(202, 10)",
      "loc": "400 50"
    },
    {
      "key": 5,
      "text": "sleep(7200)",
      "loc": "400 150"
    },
    {
      "key": 6,
      "category": "Branch",
      "text": "fleet_check",
      "loc": "500 100"
    },
    {
      "key": 7,
      "category": "End",
      "text": "Fleet Complete",
      "loc": "300 200"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 2,
      "to": 3,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 3,
      "to": 4,
      "fromPort": "L",
      "toPort": "T",
      "text": "yes"
    },
    {
      "from": 3,
      "to": 7,
      "fromPort": "R",
      "toPort": "T",
      "text": "no"
    },
    {
      "from": 4,
      "to": 5,
      "fromPort": "B",
      "toPort": "T"
    },
    {
      "from": 5,
      "to": 6,
      "fromPort": "B",
      "toPort": "T"
    }
  ]
}
```

**Flow:** Check fleet count < 100 → Yes: Build 10 SC → Wait → Loop | No: End

### Multi-Ship Fleet Strategy
Builds different ship types in sequence.

```php
// Custom action block content
$sc_count = BotGetFleetCount(202);  // Small Cargo
$lf_count = BotGetFleetCount(204);  // Light Fighter

if ($sc_count < 50) {
    return BotBuildFleet(202, 5);    // Build 5 SC
} elseif ($lf_count < 100) {
    return BotBuildFleet(204, 10);   // Build 10 LF
} else {
    return 3600;  // All done, wait 1 hour
}
```

---

## Research Strategies

### Basic Research Loop
Continuously researches priority technologies.

```json
{
  "class": "go.GraphLinksModel",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "Label",
      "text": "research_loop",
      "loc": "200 100"
    },
    {
      "key": 3,
      "category": "Cond",
      "text": "CAN_RESEARCH",
      "loc": "300 100"
    },
    {
      "key": 4,
      "text": "RESEARCH",
      "loc": "400 50"
    },
    {
      "key": 5,
      "text": "sleep(7200)",
      "loc": "400 150"
    },
    {
      "key": 6,
      "category": "Branch",
      "text": "research_loop",
      "loc": "500 100"
    }
  ],
  "linkDataArray": [
    {
      "from": 1,
      "to": 2
    },
    {
      "from": 2,
      "to": 3
    },
    {
      "from": 3,
      "to": 4,
      "text": "yes"
    },
    {
      "from": 3,
      "to": 5,
      "text": "no"
    },
    {
      "from": 4,
      "to": 6
    },
    {
      "from": 5,
      "to": 6
    }
  ]
}
```

### Conditional Research Strategy
Researches specific technologies based on conditions.

```php
// Custom research action
$energy_tech = BotGetResearch(113);
$weapon_tech = BotGetResearch(109);

if ($energy_tech < 10) {
    return BotResearch(113);  // Energy Technology first
} elseif ($weapon_tech < 5) {
    return BotResearch(109);  // Then Weapons Technology
} else {
    return 0;  // Research complete
}
```

---

## Complete Bot Tutorials

### Tutorial 1: Basic Miner Bot

#### Step 1: Create Initial Strategy
Create a strategy named `basic_miner` with the following components:

1. **Start Block:** Entry point
2. **Infrastructure Phase:** Build basic buildings
3. **Production Phase:** Optimize resource production
4. **Maintenance Phase:** Maintain and optimize

#### Step 2: Infrastructure Phase
```json
{
  "key": 2,
  "category": "Label",
  "text": "infrastructure",
  "loc": "200 100"
},
{
  "key": 3,
  "category": "Cond",
  "text": "BASIC_DONE",
  "loc": "300 100"
},
{
  "key": 4,
  "text": "BUILD",
  "loc": "400 50"
}
```

#### Step 3: Production Phase
```json
{
  "key": 5,
  "category": "Label",
  "text": "production",
  "loc": "200 200"
},
{
  "key": 6,
  "category": "Cond",
  "text": "BotGetBuild(1) < 20",
  "loc": "300 200"
},
{
  "key": 7,
  "text": "if (BotCanBuild(1)) return BotBuild(1); else return 3600;",
  "loc": "400 200"
}
```

#### Step 4: Complete Strategy Flow
```
Start → Infrastructure Check → Build if needed → Production Check → 
Build mines → Energy management → Loop back
```

### Tutorial 2: Balanced Fleet Bot

#### Step 1: Economy Foundation
Build economic base before fleet development:

```php
// Economy check condition
BotGetBuild(1) >= 15 && BotGetBuild(2) >= 12 && BotGetBuild(4) >= 20
```

#### Step 2: Fleet Development Phases
1. **Cargo Phase:** Build transport capacity
2. **Defense Phase:** Build defensive fleet
3. **Offense Phase:** Build attack fleet

#### Step 3: Cargo Phase Implementation
```php
$sc_count = BotGetFleetCount(202);
$lc_count = BotGetFleetCount(203);

if ($sc_count < 50) {
    return BotBuildFleet(202, 10);
} elseif ($lc_count < 20) {
    return BotBuildFleet(203, 5);
} else {
    BotSetVar('cargo_complete', 1);
    return 0;
}
```

#### Step 4: Defense Phase Implementation
```php
if (BotGetVar('cargo_complete', 0)) {
    $lf_count = BotGetFleetCount(204);
    if ($lf_count < 100) {
        return BotBuildFleet(204, 15);
    } else {
        BotSetVar('defense_complete', 1);
        return 0;
    }
}
```

---

## Advanced Patterns

### Pattern 1: State Machine Bot
Uses bot variables to track state and make decisions.

```php
// State management action
$state = BotGetVar('bot_state', 'init');

switch ($state) {
    case 'init':
        BotSetVar('bot_state', 'building');
        return 0;
    
    case 'building':
        if (BotGetBuild(1) >= 20) {
            BotSetVar('bot_state', 'research');
        }
        return BotBuild(1);
    
    case 'research':
        if (BotGetResearch(113) >= 10) {
            BotSetVar('bot_state', 'fleet');
        }
        return BotResearch(113);
    
    case 'fleet':
        return BotBuildFleet(202, 10);
        
    default:
        return 3600;
}
```

### Pattern 2: Resource-Aware Building
Adapts building strategy based on resource availability.

```php
// Resource analysis action
$planet = GetPlanet(LoadUser($BotID)['aktplanet']);
$metal = $planet['m'];
$crystal = $planet['k'];
$deuterium = $planet['d'];

$metal_ratio = $metal / max(1, $crystal + $deuterium);
$crystal_ratio = $crystal / max(1, $metal + $deuterium);

if ($metal_ratio > 2.0 && BotCanBuild(2)) {
    return BotBuild(2);  // Build crystal mine
} elseif ($crystal_ratio > 2.0 && BotCanBuild(1)) {
    return BotBuild(1);  // Build metal mine
} elseif (BotCanBuild(4)) {
    return BotBuild(4);  // Build solar plant
} else {
    return 3600;  // Wait for resources
}
```

### Pattern 3: Multi-Strategy Coordination
Launches multiple specialized strategies that work together.

```php
// Main coordinator strategy
if (BotGetVar('strategies_launched', 0) == 0) {
    BotExec('economy_manager');
    BotExec('fleet_manager');
    BotExec('research_manager');
    BotSetVar('strategies_launched', 1);
}

// Monitor and restart if needed
if (!BotStrategyRunning('economy_manager')) {
    BotExec('economy_manager');
}

return 3600;  // Check hourly
```

### Pattern 4: Adaptive Personality
Changes behavior based on game conditions.

```php
// Adaptive behavior action
$planet_count = count(EnumPlanets($BotID));
$current_personality = BotGetVar('personality', 'miner');

if ($planet_count >= 5 && $current_personality == 'miner') {
    BotSetVar('personality', 'fleeter');
    BotSetVar('adaptation_reason', 'multi_planet_expansion');
    BotExec('fleeter_conversion');
}

// Continue with current strategy
return 0;
```

---

## Debugging and Testing

### Debug Strategy Template
Add debugging capabilities to any strategy:

```php
// Debug action block
$debug_enabled = BotGetVar('debug_mode', 0);

if ($debug_enabled) {
    $metal_mine = BotGetBuild(1);
    $crystal_mine = BotGetBuild(2);
    $energy = BotEnergyAbove(0) ? "positive" : "negative";
    
    Debug("Bot Status - Metal Mine: $metal_mine, Crystal Mine: $crystal_mine, Energy: $energy");
}

return 0;  // Continue immediately
```

### Test Scenarios
Create test strategies to verify bot functionality:

1. **Resource Test:** Verify resource calculations
2. **Building Test:** Test building logic
3. **Fleet Test:** Verify fleet counting and building
4. **Research Test:** Test research priorities
5. **Error Test:** Test error handling

### Performance Monitoring
Monitor bot performance with logging:

```php
// Performance monitoring action
$start_time = microtime(true);

// Perform bot action
$result = BotBuild(1);

$end_time = microtime(true);
$execution_time = $end_time - $start_time;

BotSetVar('last_execution_time', $execution_time);
if ($execution_time > 5.0) {
    Debug("Slow execution detected: {$execution_time}s");
}

return $result;
```