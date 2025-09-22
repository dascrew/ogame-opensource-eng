# Bot Personality System

The bot personality system defines different behavior patterns for bots, determining their building priorities, research focus, and fleet composition preferences.

## Table of Contents

1. [Overview](#overview)
2. [Personality Types](#personality-types)
3. [Personality Configuration](#personality-configuration)
4. [Subtype System](#subtype-system)
5. [Priority Systems](#priority-systems)
6. [Customizing Personalities](#customizing-personalities)

---

## Overview

Bot personalities are defined in the `personality.php` file and control:
- Building construction priorities and level caps
- Research priorities and level caps
- Fleet composition and ship caps
- Resource allocation strategies

Each bot is assigned a personality on creation and can have different subtypes within that personality.

---

## Personality Types

### Miner Personality
**Focus:** Resource production and economic development  
**Characteristics:**
- High building caps for mines and storage
- Research focused on economic technologies
- Minimal fleet requirements
- Emphasis on long-term resource accumulation

### Fleeter Personality
**Focus:** Military development and fleet operations  
**Characteristics:**
- High caps for military ships
- Research focused on combat and drive technologies
- Moderate economic development
- Emphasis on fleet composition and combat capability

---

## Personality Configuration

### Basic Structure
```php
$PERSONALITIES = [
    'personality_name' => [
        'default_subtype' => 'subtype_name',
        'subtypes' => [
            'subtype_name' => [
                'ship_caps' => [...],
                'priority_ships' => [...]
            ]
        ],
        'building_caps' => [...],
        'priority_buildings' => [...],
        'research_caps' => [...],
        'priority_research' => [...]
    ]
];
```

---

## Miner Personality

### Building Caps
```php
'building_caps' => [
    GID_B_METAL_MINE => 30,     // High cap for resource production
    GID_B_CRYS_MINE => 28,      // High cap for crystal mining
    GID_B_DEUT_SYNTH => 26,     // High cap for deuterium
    GID_B_SOLAR => 30,          // High energy production
    GID_B_FUSION => 15,         // Moderate fusion reactor
    GID_B_METAL_STOR => 25,     // High storage capacity
    GID_B_CRYS_STOR => 25,      // High storage capacity
    GID_B_DEUT_STOR => 25,      // High storage capacity
    GID_B_ROBOTS => 10,         // Moderate robotics
    GID_B_NANITES => 5,         // Low nanites
    GID_B_SHIPYARD => 5,        // Low shipyard (minimal fleet)
    GID_B_RES_LAB => 10,        // Moderate research
    GID_B_TERRAFORMER => 0,     // No terraformer
    GID_B_ALLY_DEPOT => 0,      // No alliance depot
    GID_B_LUNAR_BASE => 0,      // No lunar development
    GID_B_PHALANX => 0,         // No phalanx
    GID_B_JUMP_GATE => 0,       // No jump gate
    GID_B_MISS_SILO => 0,       // No missile silo
]
```

### Building Priorities
```php
'priority_buildings' => [
    GID_B_METAL_MINE,      // Highest priority: metal production
    GID_B_CRYS_MINE,       // Crystal production
    GID_B_DEUT_SYNTH,      // Deuterium production
    GID_B_SOLAR,           // Energy for mines
    GID_B_FUSION,          // Additional energy
    GID_B_METAL_STOR,      // Storage for resources
    GID_B_CRYS_STOR,       // Storage for resources
    GID_B_DEUT_STOR,       // Storage for resources
    GID_B_ROBOTS,          // Build speed
    GID_B_NANITES,         // Build speed
    GID_B_RES_LAB,         // Research capability
    GID_B_SHIPYARD         // Minimal fleet capability
]
```

### Research Caps and Priorities
```php
'research_caps' => [
    GID_R_ENERGY => 15,            // High energy tech for efficiency
    GID_R_LASER_TECH => 12,        // Moderate laser tech
    GID_R_ION_TECH => 10,          // Moderate ion tech
    GID_R_PLASMA_TECH => 8,        // Low plasma tech
    GID_R_ESPIONAGE => 8,          // Moderate espionage
    GID_R_COMPUTER => 10,          // Moderate computer
    GID_R_COMBUST_DRIVE => 10,     // Basic drives
    GID_R_IMPULSE_DRIVE => 8,      // Basic drives
    GID_R_HYPERSPACE => 5,         // Minimal hyperspace
    GID_R_WEAPON => 5,             // Minimal weapons
    GID_R_SHIELD => 5,             // Minimal shields
    GID_R_ARMOUR => 5,             // Minimal armor
    GID_R_HYPER_DRIVE => 4,        // Minimal hyper drive
    GID_R_IGN => 0,                // No IGN
    GID_R_EXPEDITION => 0,         // No expeditions
    GID_R_GRAVITON => 0,           // No graviton
]
```

### Miner Subtypes

**Pure Miner**
- Minimal fleet (500 SC, 100 LF)
- Focus purely on resource production
- No military development

**Trader Miner**
- Moderate cargo fleet (5000 LC, 1000 SC)
- Some recyclers for debris collection
- Balanced resource and transport focus

**Balanced Miner**
- Mixed fleet composition
- Some defensive capability
- Balance between economy and minimal defense

---

## Fleeter Personality

### Building Caps
```php
'building_caps' => [
    GID_B_METAL_MINE => 28,     // Moderate resource production
    GID_B_CRYS_MINE => 24,      // Moderate crystal mining
    GID_B_DEUT_SYNTH => 6,      // Low deuterium (used for fuel)
    GID_B_SOLAR => 25,          // High energy for production
    GID_B_FUSION => 0,          // No fusion (energy hungry)
    GID_B_ROBOTS => 10,         // Fast construction
    GID_B_NANITES => 4,         // Fast construction
    GID_B_SHIPYARD => 16,       // High shipyard for fleet
    GID_B_METAL_STOR => 5,      // Low storage (resources used quickly)
    GID_B_CRYS_STOR => 5,       // Low storage
    GID_B_DEUT_STOR => 6,       // Moderate deut storage for fuel
    GID_B_RES_LAB => 12,        // High research for tech
    GID_B_TERRAFORMER => 4,     // Some terraforming
    GID_B_ALLY_DEPOT => 4,      // Alliance support
    GID_B_LUNAR_BASE => 5,      // Lunar development
    GID_B_PHALANX => 5,         // Intelligence gathering
    GID_B_JUMP_GATE => 5,       // Fleet mobility
    GID_B_MISS_SILO => 4,       // Missile capability
]
```

### Building Priorities
```php
'priority_buildings' => [
    GID_B_ROBOTS,          // Fast construction
    GID_B_SHIPYARD,        // Fleet production
    GID_B_SOLAR,           // Energy for production
    GID_B_METAL_MINE,      // Metal for ships
    GID_B_CRYS_MINE,       // Crystal for ships
    GID_B_DEUT_SYNTH,      // Fuel production
    GID_B_METAL_STOR,      // Resource storage
    GID_B_CRYS_STOR,       // Resource storage
    GID_B_DEUT_STOR,       // Fuel storage
    GID_B_NANITES,         // Faster construction
    GID_B_MISS_SILO,       // Missile capability
    GID_B_LUNAR_BASE,      // Lunar expansion
    GID_B_PHALANX,         // Intelligence
    GID_B_JUMP_GATE,       // Fleet mobility
    GID_B_ALLY_DEPOT,      // Alliance support
    GID_B_TERRAFORMER,     // Planet expansion
    GID_B_RES_LAB          // Technology research
]
```

### Research Priorities
```php
'priority_research' => [
    GID_R_WEAPON,          // Combat effectiveness
    GID_R_SHIELD,          // Defense
    GID_R_ARMOUR,          // Defense
    GID_R_HYPERSPACE,      // Advanced drives
    GID_R_IMPULSE_DRIVE,   // Ship speed
    GID_R_COMBUST_DRIVE,   // Basic drives
    GID_R_ENERGY,          // Technology foundation
    GID_R_ESPIONAGE,       // Intelligence
    GID_R_COMPUTER,        // Fleet size
]
```

### Fleeter Subtypes

**Swarm Fleeter**
- Massive numbers of light ships
- High caps for SC, LF, Cruisers
- Overwhelming numerical advantage

**Speed Fleeter**
- Focus on fast ships
- High caps for fast ships (LF, Cruiser, Destroyer)
- Hit-and-run tactics

**Smasher Fleeter**
- Heavy combat ships
- High caps for Battleships, Destroyers, Deathstars
- Overwhelming firepower

**Balanced Fleeter**
- Mixed fleet composition
- Balanced caps across all ship types
- Versatile combat capability

---

## Priority Systems

### Building Priority Logic
When a bot needs to build, it:
1. Checks `priority_buildings` list in order
2. For each building, checks if current level < cap
3. Verifies building requirements and resources
4. Builds the first viable building in priority order

### Research Priority Logic
Similar to buildings:
1. Checks `priority_research` list in order
2. Verifies current level < cap
3. Checks research requirements and resources
4. Starts the first viable research

### Fleet Priority Logic
For fleet building:
1. Checks `priority_ships` list in order
2. Verifies current count < cap
3. Checks shipyard availability and resources
4. Builds ships in priority order

---

## Bot Personality Assignment

### Automatic Assignment
New bots are randomly assigned personalities:
```php
$personalities = array_keys($PERSONALITIES);
$personality = $personalities[array_rand($personalities)];
$available_subtypes = array_keys($PERSONALITIES[$personality]['subtypes']);
$subtype = $available_subtypes[array_rand($available_subtypes)];

BotSetVar('personality', $personality);
BotSetVar('subtype', $subtype);
```

### Manual Assignment
Personalities can be manually set:
```php
BotSetVar('personality', 'fleeter');
BotSetVar('subtype', 'swarm');
```

---

## Using Personalities in Strategies

### Personality Checks
```php
// In condition blocks
IS_MINER      // Returns true if personality is 'miner'
IS_FLEETER    // Returns true if personality is 'fleeter'

// In custom code
$personality = BotGetVar('personality', 'miner');
if ($personality === 'fleeter') {
    // Fleeter-specific logic
}
```

### Priority-Based Building
```php
// Built-in conditions use personality priorities
CAN_BUILD     // Checks if any building in priority list can be built
CAN_RESEARCH  // Checks if any research in priority list can be started
```

### Subtype-Specific Behavior
```php
$subtype = BotGetVar('subtype', 'balanced');
if ($subtype === 'pure') {
    // Pure miner behavior - minimal fleet
    $max_fleet = 500;
} else if ($subtype === 'trader') {
    // Trader behavior - large cargo fleet
    $max_fleet = 5000;
}
```

---

## Customizing Personalities

### Adding New Personalities
1. Define personality structure in `personality.php`
2. Set caps and priorities for buildings, research, and ships
3. Create subtypes for specialization
4. Test with bot strategies

### Modifying Existing Personalities
1. Adjust caps to change maximum development levels
2. Reorder priorities to change building/research focus
3. Add/remove subtypes for more specialization
4. Balance for game meta and server settings

### Best Practices
- Keep personalities balanced for gameplay
- Ensure all personalities can reach basic functionality
- Test personalities across different game phases
- Consider resource availability and server speed
- Balance offensive and defensive capabilities
- Provide clear specialization differences

### Example Custom Personality
```php
'researcher' => [
    'default_subtype' => 'balanced',
    'subtypes' => [
        'balanced' => [
            'ship_caps' => [
                GID_F_PROBE => 10000,  // High probe count for research
                GID_F_SC => 1000,      // Basic cargo
            ],
            'priority_ships' => [GID_F_PROBE, GID_F_SC]
        ]
    ],
    'building_caps' => [
        GID_B_RES_LAB => 20,       // Very high research lab
        GID_B_METAL_MINE => 15,    // Moderate resources
        GID_B_CRYS_MINE => 15,
        GID_B_DEUT_SYNTH => 15,
        // ... other buildings
    ],
    'priority_buildings' => [
        GID_B_RES_LAB,         // Research first
        GID_B_METAL_MINE,      // Then resources
        // ... other priorities
    ],
    'research_caps' => [
        // High caps for all research
        GID_R_COMPUTER => 20,
        GID_R_ESPIONAGE => 20,
        // ... all research types
    ],
    'priority_research' => [
        GID_R_COMPUTER,        // Research capacity first
        GID_R_ESPIONAGE,       // Intelligence gathering
        // ... other research
    ]
]
```