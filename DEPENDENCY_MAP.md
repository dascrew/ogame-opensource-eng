# OGame Dependency Map

This document provides a comprehensive dependency map of all files and functions in the game folder, helping developers understand the codebase architecture and function relationships.

**Generated on:** 2025-09-23 16:41:32  
**Total Functions Analyzed:** 684  
**Total Files Analyzed:** 117  

## Table of Contents

1. [Overview](#overview)
2. [Core System Files](#core-system-files)
3. [Entry Points](#entry-points)
4. [Module Dependencies](#module-dependencies)
5. [Function Index](#function-index)
6. [Critical Function Dependencies](#critical-function-dependencies)
7. [File Include Hierarchy](#file-include-hierarchy)

## Overview

The OGame codebase is organized into several key layers:

- **Entry Points**: Main access files (index.php, cron.php, etc.)
- **Core System**: Database, utilities, configuration (db.php, utils.php, config.php)
- **Game Logic**: Core game mechanics (prod.php, planet.php, user.php, fleet.php)
- **User Interface**: Page controllers and views (pages/ directory)
- **Bot System**: AI player logic (bot*.php files)
- **Admin Interface**: Administrative tools (pages_admin/ directory)

## Core System Files

### Configuration and Database Layer
- **config.php**: Main configuration file
- **db.php**: Database abstraction layer (34 functions)
- **utils.php**: Core utility functions (15 functions)
- **debug.php**: Debugging and logging utilities (3 functions)

### Game Engine Core
- **prod.php**: Resource production and requirements (38 functions)
- **planet.php**: Planet management (41 functions)
- **user.php**: User account management (29 functions)
- **fleet.php**: Fleet movement and combat (53 functions)
- **battle_engine.php**: Combat calculations (28 functions)

### Support Systems
- **loca.php**: Localization system (6 functions)
- **queue.php**: Task queue management (45 functions)
- **msg.php**: Messaging system (16 functions)
- **ally.php**: Alliance management (31 functions)

## Entry Points

### Main Game Entry
- **index.php**: Primary game interface
  - Includes 20+ core modules
  - Routes to specific game pages
  - Handles session management

### Specialized Entry Points
- **ainfo.php**: Alliance information page
- **pranger.php**: Player ranking/punishment system
- **cron.php**: Background task processor
- **install.php**: Game installation

### Bot Entry Points
- **bot*.php**: AI player system (9 specialized files)

## Module Dependencies

### High-Level Dependency Flow

```
config.php
    ↓
db.php + utils.php
    ↓
Core Game Modules (prod.php, planet.php, user.php, fleet.php)
    ↓
Game Logic (battle.php, ally.php, queue.php)
    ↓
UI Pages (pages/*.php)
```

### Critical Dependencies

1. **Database Layer**
   - All modules depend on `db.php`
   - `db.php` requires `config.php`

2. **Production System**
   - `prod.php` is central to resource management
   - Called by planet management, building construction, research

3. **Queue System**
   - `queue.php` manages all timed actions
   - Used by construction, research, fleet movements

4. **User/Planet Management**
   - `user.php` and `planet.php` are core to game state
   - Nearly all game actions affect these modules

## Function Index

### Database Functions (db.php)
- `dbconnect()`: Establish database connection
- `dbquery()`: Execute SQL queries
- `dbarray()`: Fetch query results
- `AddDBRow()`: Insert new records
- `MDBConnect()`: Master database connection

### Resource/Production Functions (prod.php)
- `ProdResources()`: Calculate resource production
- `BuildingCost()`: Calculate building costs
- `ResearchCost()`: Calculate research costs
- `ResearchMeetRequirement()`: Check research prerequisites
- `CanBuild()`: Validate building construction

### Planet Management Functions (planet.php)
- `LoadPlanet()`: Load planet data
- `SavePlanet()`: Save planet changes
- `GetPlanetName()`: Get planet name
- `CreatePlanet()`: Create new planet
- `DeletePlanet()`: Remove planet

### Fleet Functions (fleet.php)
- `SendFleet()`: Dispatch fleet mission
- `FleetArrive()`: Handle fleet arrival
- `LoadFleets()`: Load fleet data
- `AttackPlanet()`: Execute planet attack
- `ExpeditionReturn()`: Handle expedition return

### User Management Functions (user.php)
- `LoadUser()`: Load user account
- `SaveUser()`: Save user data
- `GetUserByName()`: Find user by name
- `CreateUser()`: Create new account
- `BanUser()`: Ban user account

### Queue Functions (queue.php)
- `AddQueue()`: Add task to queue
- `ProcessQueue()`: Execute queued tasks
- `StartBuilding()`: Begin construction
- `StartResearch()`: Begin research
- `GetQueue()`: Retrieve queue status

### Battle Functions (battle_engine.php)
- `BattleEngine()`: Main combat calculation
- `CalcShipAttack()`: Calculate ship attack power
- `CalcShipDef()`: Calculate ship defense
- `ApplyDamage()`: Apply battle damage

## Critical Function Dependencies

### Resource Production Chain
```
ProdResources() [prod.php]
    ├── Called by: LoadPlanet() [planet.php]
    ├── Called by: ProcessQueue() [queue.php]
    └── Calls: GetBuildingLevel() [planet.php]
```

### Building Construction Chain
```
StartBuilding() [queue.php]
    ├── Calls: CanBuild() [prod.php]
    ├── Calls: BuildingCost() [prod.php]
    ├── Calls: AdjustResources() [planet.php]
    └── Calls: AddQueue() [queue.php]
```

### Fleet Movement Chain
```
SendFleet() [fleet.php]
    ├── Calls: LoadUser() [user.php]
    ├── Calls: LoadPlanet() [planet.php]
    ├── Calls: FlightTime() [fleet.php]
    └── Calls: AddQueue() [queue.php]
```

### Combat Resolution Chain
```
AttackPlanet() [fleet.php]
    ├── Calls: BattleEngine() [battle_engine.php]
    ├── Calls: BattleReport() [battle.php]
    └── Calls: AddDebris() [planet.php]
```

## Key Function Relationships Table

| Calling Function | Called Function | Purpose | File Location |
|------------------|-----------------|---------|---------------|
| `LoadPlanet()` | `ProdResources()` | Update resource production | planet.php → prod.php |
| `StartBuilding()` | `CanBuild()` | Validate construction | queue.php → prod.php |
| `StartResearch()` | `CanResearch()` | Validate research | queue.php → prod.php |
| `SendFleet()` | `FlightTime()` | Calculate travel time | fleet.php → fleet.php |
| `AttackPlanet()` | `BattleEngine()` | Execute combat | fleet.php → battle_engine.php |
| `BattleEngine()` | `CalcShipAttack()` | Calculate damage | battle_engine.php → battle_engine.php |
| `ProcessQueue()` | `StartBuilding()` | Execute queued construction | queue.php → queue.php |
| `ProcessQueue()` | `StartResearch()` | Execute queued research | queue.php → queue.php |
| `CreateUser()` | `CreatePlanet()` | Setup starting planet | user.php → planet.php |
| `BotProcess()` | `BotBuild()` | AI construction logic | bot.php → bot_planet.php |

## File Include Hierarchy

### Core Module Loading Pattern
Most entry points follow this pattern:

1. **Configuration**: `config.php`
2. **Database**: `db.php` + `utils.php`
3. **Core Systems**: `id.php`, `loca.php`, `uni.php`
4. **Game Logic**: `prod.php`, `planet.php`, `user.php`
5. **Advanced Features**: `fleet.php`, `battle.php`, `ally.php`
6. **Support**: `queue.php`, `msg.php`, `debug.php`

### Specialized Modules
- **Bot System**: Includes personality, skills, and specialized bot modules
- **Battle System**: Includes unit definitions and battle engine
- **Admin System**: Includes administrative utilities and interfaces

### Page Controllers
Each page in `pages/` directory typically includes:
- Core game logic modules
- Specific functionality for that page
- Template/UI generation code

## Usage Notes

1. **Function Naming**: Most functions use PascalCase (e.g., `LoadPlanet`)
2. **Global Dependencies**: Most modules depend on global variables like `$GlobalUser`, `$GlobalUni`
3. **Database Patterns**: Heavy use of direct SQL queries through `dbquery()`
4. **Session Management**: User sessions managed through cookies and database
5. **Localization**: Text strings managed through the loca system

## Development Guidelines

When modifying the codebase:

1. **Check Dependencies**: Before modifying a function, check which files call it
2. **Test Entry Points**: Changes to core modules affect multiple entry points
3. **Database Changes**: Be careful with db.php modifications as they affect everything
4. **Queue System**: Understand queue processing for timed actions
5. **Bot Compatibility**: Consider bot system when changing game logic

---

*This dependency map was generated automatically by analyzing all PHP files in the game directory. It provides a high-level overview of the codebase structure and relationships.*