# Bot API Reference

This document provides a comprehensive reference for all bot functions available in the OGame engine.

## Table of Contents

1. [Auxiliary Functions](#auxiliary-functions)
2. [Bot Variables](#bot-variables)
3. [Building Management](#building-management)
4. [Fleet Management](#fleet-management)
5. [Research Management](#research-management)
6. [Resource Management](#resource-management)
7. [Information Functions](#information-functions)

---

## Auxiliary Functions

### BotIdle()
**Description:** Do nothing operation  
**Parameters:** None  
**Returns:** None  
**Usage:** Used as a placeholder action or to introduce delays in bot strategies.

```php
BotIdle();
```

### BotStrategyExists($name)
**Description:** Check if a strategy with the specified name exists  
**Parameters:**
- `$name` (string): Name of the strategy to check
**Returns:** Boolean - true if strategy exists, false otherwise

```php
if (BotStrategyExists("attack_strategy")) {
    // Strategy exists, proceed
}
```

### BotExec($name)
**Description:** Start a new bot strategy in parallel  
**Parameters:**
- `$name` (string): Name of the strategy to execute
**Returns:** Integer - 1 if successful, 0 if failed

```php
$result = BotExec("mining_strategy");
if ($result == 1) {
    // Strategy started successfully
}
```

---

## Bot Variables

Bot variables provide persistent storage for bot state and configuration.

### BotGetVar($var, $def_value=null)
**Description:** Get a bot variable value  
**Parameters:**
- `$var` (string): Variable name
- `$def_value` (mixed): Default value if variable doesn't exist
**Returns:** Mixed - variable value or default value

```php
$personality = BotGetVar('personality', 'miner');
$build_count = BotGetVar('buildings_built', 0);
```

### BotSetVar($var, $value)
**Description:** Set a bot variable value  
**Parameters:**
- `$var` (string): Variable name
- `$value` (mixed): Value to set
**Returns:** None

```php
BotSetVar('personality', 'fleeter');
BotSetVar('last_attack', time());
```

---

## Building Management

### BotCanBuild($obj_id)
**Description:** Check if a specific building can be built on the active planet  
**Parameters:**
- `$obj_id` (integer): Building ID (see [Building IDs](#building-ids))
**Returns:** Boolean - true if building can be built, false otherwise

```php
if (BotCanBuild(GID_B_METAL_MINE)) {
    // Can build metal mine
}
```

### BotBuild($obj_id)
**Description:** Start building construction on the active planet  
**Parameters:**
- `$obj_id` (integer): Building ID to build
**Returns:** Integer - seconds until completion (0 if failed)

```php
$build_time = BotBuild(GID_B_METAL_MINE);
if ($build_time > 0) {
    // Building started, will take $build_time seconds
}
```

### BotGetBuild($n)
**Description:** Get the current level of a building  
**Parameters:**
- `$n` (integer): Building ID
**Returns:** Integer - current building level

```php
$metal_mine_level = BotGetBuild(GID_B_METAL_MINE);
$shipyard_level = BotGetBuild(GID_B_SHIPYARD);
```

### BotGetLastBuilt()
**Description:** Get the ID of the last building that was built  
**Parameters:** None  
**Returns:** Integer - building ID of last built structure

```php
$last_building = BotGetLastBuilt();
```

---

## Fleet Management

### BotBuildFleet($obj_id, $n)
**Description:** Build fleet units in the shipyard  
**Parameters:**
- `$obj_id` (integer): Ship type ID (see [Ship IDs](#ship-ids))
- `$n` (integer): Number of ships to build
**Returns:** Integer - seconds until completion (0 if failed)

```php
$build_time = BotBuildFleet(GID_F_SC, 10); // Build 10 small cargo ships
```

### BotBuildFleetAction($params)
**Description:** Advanced fleet building with resource validation  
**Parameters:**
- `$params` (array): Array containing [ship_id, amount]
**Returns:** Integer - seconds until completion (0 if failed)

```php
$build_time = BotBuildFleetAction([GID_F_LF, 50]); // Build 50 light fighters
```

### BotGetFleetCount($shipTypeId)
**Description:** Get the total count of a specific ship type across all planets and fleets  
**Parameters:**
- `$shipTypeId` (integer): Ship type ID
**Returns:** Integer - total ship count

```php
$light_fighters = BotGetFleetCount(GID_F_LF);
$small_cargo = BotGetFleetCount(GID_F_SC);
```

### BotGetShipCount()
**Description:** Get counts of all ship types  
**Parameters:** None  
**Returns:** Array - associative array with ship IDs as keys and counts as values

```php
$all_ships = BotGetShipCount();
$total_lf = $all_ships[GID_F_LF];
```

---

## Research Management

### BotCanResearch($obj_id)
**Description:** Check if a specific research can be started  
**Parameters:**
- `$obj_id` (integer): Research ID (see [Research IDs](#research-ids))
**Returns:** Boolean - true if research can be started, false otherwise

```php
if (BotCanResearch(GID_R_WEAPON)) {
    // Can research weapons technology
}
```

### BotResearch($obj_id)
**Description:** Start research on the active planet  
**Parameters:**
- `$obj_id` (integer): Research ID to start
**Returns:** Integer - seconds until completion (0 if failed)

```php
$research_time = BotResearch(GID_R_WEAPON);
```

### BotGetResearch($n)
**Description:** Get the current level of a research  
**Parameters:**
- `$n` (integer): Research ID
**Returns:** Integer - current research level

```php
$weapon_level = BotGetResearch(GID_R_WEAPON);
$drive_level = BotGetResearch(GID_R_COMBUST_DRIVE);
```

---

## Resource Management

### BotResourceSettings($last1=100, $last2=100, $last3=100, $last4=100, $last12=100, $last212=100)
**Description:** Set resource production settings for the active planet  
**Parameters:**
- `$last1` (integer): Metal mine production percentage (0-100)
- `$last2` (integer): Crystal mine production percentage (0-100)
- `$last3` (integer): Deuterium synthesizer production percentage (0-100)
- `$last4` (integer): Solar plant production percentage (0-100)
- `$last12` (integer): Fusion reactor production percentage (0-100)
- `$last212` (integer): Solar satellite production percentage (0-100)
**Returns:** None

```php
// Set all production to maximum
BotResourceSettings(100, 100, 100, 100, 100, 100);

// Reduce deuterium production to save energy
BotResourceSettings(100, 100, 50, 100, 100, 100);
```

### BotEnergyAbove($energy)
**Description:** Check if current energy is at or above a specified value  
**Parameters:**
- `$energy` (integer): Energy threshold to check
**Returns:** Boolean - true if energy >= threshold, false otherwise

```php
if (BotEnergyAbove(1000)) {
    // Sufficient energy available
}
```

---

## Information Functions

### BotGetBuildingEnergyCost($buildingId, $current_level)
**Description:** Calculate energy cost increase for upgrading a building  
**Parameters:**
- `$buildingId` (integer): Building ID
- `$current_level` (integer): Current building level
**Returns:** Integer - additional energy consumption for next level

```php
$energy_cost = BotGetBuildingEnergyCost(GID_B_METAL_MINE, 10);
```

---

## Game Object IDs

### Building IDs
- `GID_B_METAL_MINE` (1) - Metal Mine
- `GID_B_CRYS_MINE` (2) - Crystal Mine
- `GID_B_DEUT_SYNTH` (3) - Deuterium Synthesizer
- `GID_B_SOLAR` (4) - Solar Plant
- `GID_B_FUSION` (12) - Fusion Reactor
- `GID_B_ROBOTS` (14) - Robotics Factory
- `GID_B_NANITES` (15) - Nanite Factory
- `GID_B_SHIPYARD` (21) - Shipyard
- `GID_B_METAL_STOR` (22) - Metal Storage
- `GID_B_CRYS_STOR` (23) - Crystal Storage
- `GID_B_DEUT_STOR` (24) - Deuterium Tank
- `GID_B_RES_LAB` (31) - Research Lab
- `GID_B_TERRAFORMER` (33) - Terraformer
- `GID_B_ALLY_DEPOT` (34) - Alliance Depot
- `GID_B_LUNAR_BASE` (41) - Lunar Base
- `GID_B_PHALANX` (42) - Sensor Phalanx
- `GID_B_JUMP_GATE` (43) - Jump Gate
- `GID_B_MISS_SILO` (44) - Missile Silo

### Research IDs
- `GID_R_ESPIONAGE` (106) - Espionage Technology
- `GID_R_COMPUTER` (108) - Computer Technology
- `GID_R_WEAPON` (109) - Weapons Technology
- `GID_R_SHIELD` (110) - Shielding Technology
- `GID_R_ARMOUR` (111) - Armour Technology
- `GID_R_ENERGY` (113) - Energy Technology
- `GID_R_HYPERSPACE` (114) - Hyperspace Technology
- `GID_R_COMBUST_DRIVE` (115) - Combustion Drive
- `GID_R_IMPULSE_DRIVE` (117) - Impulse Drive
- `GID_R_HYPER_DRIVE` (118) - Hyperspace Drive
- `GID_R_LASER_TECH` (120) - Laser Technology
- `GID_R_ION_TECH` (121) - Ion Technology
- `GID_R_PLASMA_TECH` (122) - Plasma Technology
- `GID_R_IGN` (123) - Intergalactic Research Network
- `GID_R_EXPEDITION` (124) - Expedition Technology
- `GID_R_GRAVITON` (199) - Graviton Technology

### Ship IDs
- `GID_F_SC` (202) - Small Cargo
- `GID_F_LC` (203) - Large Cargo
- `GID_F_LF` (204) - Light Fighter
- `GID_F_HF` (205) - Heavy Fighter
- `GID_F_CRUISER` (206) - Cruiser
- `GID_F_BATTLESHIP` (207) - Battleship
- `GID_F_COLON` (208) - Colony Ship
- `GID_F_RECYCLER` (209) - Recycler
- `GID_F_PROBE` (210) - Espionage Probe
- `GID_F_BOMBER` (211) - Bomber
- `GID_F_SAT` (212) - Solar Satellite
- `GID_F_DESTRO` (213) - Destroyer
- `GID_F_DEATHSTAR` (214) - Deathstar
- `GID_F_BATTLECRUISER` (215) - Battlecruiser

---

## Usage Examples

### Basic Building Strategy
```php
// Check if we can build metal mine and build it
if (BotCanBuild(GID_B_METAL_MINE)) {
    $build_time = BotBuild(GID_B_METAL_MINE);
    if ($build_time > 0) {
        BotSetVar('last_build_time', $build_time);
    }
}
```

### Fleet Building Strategy
```php
// Build small cargo ships if we have less than 100
$current_sc = BotGetFleetCount(GID_F_SC);
if ($current_sc < 100) {
    $needed = 100 - $current_sc;
    BotBuildFleet(GID_F_SC, $needed);
}
```

### Resource Management
```php
// Reduce deuterium production if energy is low
if (!BotEnergyAbove(500)) {
    BotResourceSettings(100, 100, 50, 100, 100, 100);
} else {
    BotResourceSettings(100, 100, 100, 100, 100, 100);
}
```