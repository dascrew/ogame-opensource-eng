# Research Validation Functions in OGame

This document identifies and explains the functions responsible for checking if a research can be started in the OGame codebase.

## Primary Functions

### 1. `CanResearch($user, $planet, $id, $lvl)` 
**Location**: `/game/queue.php` (lines 707-745)
**Purpose**: Main validation function that performs comprehensive checks before allowing research to start

#### Validation Checks:
- **Universe Status**: Checks if universe is frozen (`$GlobalUni['freeze']`)
- **Research Queue**: Ensures no research is already in progress via `GetResearchQueue()`
- **Research Lab Status**: Verifies research lab is not being upgraded/demolished
- **Resource Requirements**: Validates sufficient resources using `ResearchPrice()` and `IsEnoughResources()`
- **Valid Research ID**: Confirms ID exists in research map (`$resmap`)
- **Vacation Mode**: Prevents research during vacation (`$user['vacation']`)
- **Planet Ownership**: Ensures player owns the planet
- **Technology Prerequisites**: Calls `ResearchMeetRequirement()` for dependency checks

#### Return Value:
- Empty string `""` if research can be started
- Localized error message if validation fails

### 2. `BotCanResearch($obj_id)`
**Location**: `/game/botapi.php` (lines 201-210)  
**Purpose**: Bot API wrapper for research validation

#### Process:
1. Loads user and active planet data
2. Updates resource production with `ProdResources()`
3. Calculates next research level
4. Calls `CanResearch()` for validation
5. Returns boolean result (`true` if allowed, `false` if not)

### 3. `ResearchMeetRequirement($user, $planet, $id)`
**Location**: `/game/prod.php` (lines 190-210)
**Purpose**: Checks technology and building prerequisites for specific research

#### Prerequisite Checks:
- **Building Requirements**: Research Lab levels, other facility requirements
- **Technology Dependencies**: Prior research that must be completed
- **Complex Chains**: Multi-requirement technologies (e.g., Hyperspace Drive needs Energy Tech + Impulse Drive + Research Lab level 7)

#### Examples:
- Espionage Tech: Requires Research Lab level 3
- Hyperspace Drive: Requires Energy Tech level 5, Impulse Drive level 5, Research Lab level 7
- Graviton Technology: Requires Research Lab level 12

## Supporting Functions

### `StartResearch($player_id, $planet_id, $id, $now)`
**Location**: `/game/queue.php` (lines 748-780)
**Purpose**: Actually starts research after validation

#### Process:
1. Calls `CanResearch()` for final validation
2. Calculates research duration
3. Deducts resources
4. Adds research to queue

### `GetResearchQueue($player_id)`
**Location**: `/game/queue.php` (lines 830-834)
**Purpose**: Retrieves current research queue for a player

## Usage Flow

### Web Interface Flow (buildings.php):
```
User clicks research button
         ↓
ResearchMeetRequirement() [UI filter - only shows valid research]
         ↓
IsEnoughResources() [UI color coding - green if affordable]
         ↓
StartResearch() [Called when user clicks research button]
         ↓
    CanResearch() [Full validation inside StartResearch]
```

### Bot API Flow:
```
Bot calls BotCanResearch()
         ↓
    CanResearch() [Main validation]
         ↓
ResearchMeetRequirement() [Prerequisites check]
         ↓
Returns boolean result
```

## Real-World Usage

### In Web Interface (`/game/pages/buildings.php`):
- **Line 296**: `ResearchMeetRequirement()` filters which research items are displayed to user
- **Line 69**: `StartResearch()` is called when user clicks research button (via GET parameter `bau`)
- **UI Logic**: Only research items that pass `ResearchMeetRequirement()` are shown; color coding based on resource availability

### In Bot API (`/game/botapi.php`):
- **Line 208**: `BotCanResearch()` calls `CanResearch()` for comprehensive validation
- **Line 220**: `BotResearch()` calls `StartResearch()` to actually start research

## Key Constants

- `QTYP_RESEARCH`: Queue type constant for research
- `GID_B_RES_LAB`: Building ID for Research Laboratory
- `QTYP_BUILD`, `QTYP_DEMOLISH`: Queue types for construction/demolition

## Summary

The **main function** that checks if research can be started is **`CanResearch()`** in `/game/queue.php`. This function performs all necessary validations and is used by both the web interface and bot API through various wrapper functions.