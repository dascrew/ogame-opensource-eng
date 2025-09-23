# Bot Block Reference

This reference provides detailed information about all block types available in the bot strategy editor and their functionality.

## Table of Contents

1. [Block Overview](#block-overview)
2. [Control Flow Blocks](#control-flow-blocks)
3. [Action Blocks](#action-blocks)
4. [Condition Blocks](#condition-blocks)
5. [Custom Blocks](#custom-blocks)
6. [Block Properties](#block-properties)
7. [Connection System](#connection-system)

---

## Block Overview

Bot strategies are composed of interconnected blocks that define the bot's decision-making process. Each block has specific properties and connection points that determine how the strategy flows.

### Block Categories
- **Start:** Entry point for strategy execution
- **End:** Termination point for strategy execution
- **Label:** Named waypoints for navigation
- **Branch:** Jump to labeled sections
- **Cond:** Conditional decision making
- **Default:** Action blocks (no specific category)

### Visual Representation
Blocks are displayed in the graphical editor with different shapes and colors:
- **Start blocks:** Green ellipse
- **End blocks:** Red ellipse
- **Condition blocks:** Yellow diamond
- **Action blocks:** Gray rectangle
- **Label blocks:** Blue rectangle
- **Branch blocks:** Orange rectangle

---

## Control Flow Blocks

### Start Block
**Category:** `Start`  
**Shape:** Ellipse  
**Color:** Green  
**Description:** Entry point for strategy execution

#### Properties
- **Text:** "Start" (usually not editable)
- **Connections:** One output (Bottom port)

#### Behavior
- Automatically executed when strategy begins
- Immediately proceeds to connected block
- Required in every strategy

#### Example
```json
{
  "key": 1,
  "category": "Start",
  "text": "Start",
  "loc": "100 100"
}
```

### End Block
**Category:** `End`  
**Shape:** Ellipse  
**Color:** Red  
**Description:** Terminates strategy execution

#### Properties
- **Text:** "End" (editable for documentation)
- **Connections:** One input (Top port)

#### Behavior
- Terminates current strategy execution
- Removes task from bot queue
- No further blocks executed

#### Example
```json
{
  "key": 2,
  "category": "End",
  "text": "End",
  "loc": "300 200"
}
```

### Label Block
**Category:** `Label`  
**Shape:** Rectangle  
**Color:** Blue  
**Description:** Named waypoint for navigation

#### Properties
- **Text:** Label name (used by Branch blocks)
- **Connections:** One input (Top port), one output (Bottom port)

#### Behavior
- Acts as a waypoint for Branch blocks
- Immediately proceeds to next connected block
- Must have unique name within strategy

#### Example
```json
{
  "key": 3,
  "category": "Label",
  "text": "main_loop",
  "loc": "200 150"
}
```

### Branch Block
**Category:** `Branch`  
**Shape:** Rectangle  
**Color:** Orange  
**Description:** Jump to labeled section

#### Properties
- **Text:** Target label name
- **Connections:** One input (Top port)

#### Behavior
- Searches for Label block with matching text
- Jumps execution to that label
- Fails if label not found

#### Example
```json
{
  "key": 4,
  "category": "Branch",
  "text": "main_loop",
  "loc": "400 200"
}
```

---

## Action Blocks

Action blocks perform specific operations and are the core functionality of bot strategies.

### Default Action Block
**Category:** Default (no category)  
**Shape:** Rectangle  
**Color:** Gray  
**Description:** Performs actions based on text content

#### Built-in Actions

##### BUILD
**Description:** Automatically builds next priority building  
**Behavior:**
- Checks personality building priorities
- Finds first buildable building in priority list
- Starts construction and waits for completion
- Returns build time in seconds

```json
{
  "key": 5,
  "category": "",
  "text": "BUILD",
  "loc": "200 100"
}
```

##### RESEARCH
**Description:** Automatically starts next priority research  
**Behavior:**
- Checks personality research priorities
- Finds first available research in priority list
- Starts research and waits for completion
- Returns research time in seconds

```json
{
  "key": 6,
  "category": "",
  "text": "RESEARCH",
  "loc": "200 150"
}
```

##### BUILD_FLEET
**Description:** Builds fleet units using parameters  
**Behavior:**
- Uses queue parameters `[ship_id, amount]`
- Validates resources and shipyard availability
- Starts production and returns build time
- Handles resource validation and error messages

```json
{
  "key": 7,
  "category": "",
  "text": "BUILD_FLEET",
  "loc": "200 200"
}
```

**Queue Parameters Example:**
```php
AddBotQueue($BotID, $strat_id, $block_id, $BotNow, 0, [GID_F_SC, 10]);
```

##### BUILD_WAIT
**Description:** Waits for current building construction to complete  
**Behavior:**
- Gets last built building ID using `BotGetLastBuilt()`
- Calculates remaining construction time
- Waits until construction completes

```json
{
  "key": 8,
  "category": "",
  "text": "BUILD_WAIT",
  "loc": "200 250"
}
```

#### Custom Actions
Any PHP code can be used as a custom action:

```json
{
  "key": 9,
  "category": "",
  "text": "BotResourceSettings(100, 100, 50, 100, 100, 100)",
  "loc": "200 300"
}
```

**Custom Action Requirements:**
- Must be valid PHP expression
- Should return integer (sleep time in seconds)
- Can call any bot API function
- Error handling recommended

---

## Condition Blocks

### Condition Block
**Category:** `Cond`  
**Shape:** Diamond  
**Color:** Yellow  
**Description:** Makes decisions based on conditions

#### Properties
- **Text:** Condition expression
- **Connections:** One input (Top), two outputs (Left=YES, Right=NO)

#### Built-in Conditions

##### CAN_BUILD
**Description:** Checks if any priority building can be built  
**Returns:** Boolean based on personality priorities and resources

```json
{
  "key": 10,
  "category": "Cond",
  "text": "CAN_BUILD",
  "loc": "200 100"
}
```

##### CAN_RESEARCH
**Description:** Checks if any priority research can be started  
**Returns:** Boolean based on personality priorities and resources

```json
{
  "key": 11,
  "category": "Cond",
  "text": "CAN_RESEARCH",
  "loc": "200 150"
}
```

##### BASIC_DONE
**Description:** Checks if basic infrastructure is complete  
**Logic:** `BotGetBuild(1) >= 4 && BotGetBuild(2) >= 2 && BotGetBuild(4) >= 4`

```json
{
  "key": 12,
  "category": "Cond",
  "text": "BASIC_DONE",
  "loc": "200 200"
}
```

##### IS_MINER
**Description:** Checks if bot personality is 'miner'  
**Returns:** Boolean based on stored personality variable

```json
{
  "key": 13,
  "category": "Cond",
  "text": "IS_MINER",
  "loc": "200 250"
}
```

##### IS_FLEETER
**Description:** Checks if bot personality is 'fleeter'  
**Returns:** Boolean based on stored personality variable

```json
{
  "key": 14,
  "category": "Cond",
  "text": "IS_FLEETER",
  "loc": "200 300"
}
```

#### Custom Conditions
Any PHP expression that returns boolean:

```json
{
  "key": 15,
  "category": "Cond",
  "text": "BotGetBuild(GID_B_METAL_MINE) >= 10",
  "loc": "200 350"
}
```

#### Probability Conditions
Condition blocks support probability-based branching:

```json
{
  "key": 16,
  "category": "Cond",
  "text": "rand(1, 100) <= 75",
  "loc": "200 400"
}
```

---

## Custom Blocks

### Creating Custom Block Types

Custom functionality can be implemented by extending the block system:

#### Custom Action Handler
```php
function handleCustomAction($text) {
    switch (trim($text)) {
        case 'CUSTOM_BUILD_MINES':
            return customBuildMinesAction();
        case 'CUSTOM_FLEET_SAVE':
            return customFleetSaveAction();
        default:
            // Evaluate as PHP code
            return evaluateCustomCode($text);
    }
}
```

#### Custom Condition Handler
```php
function evaluateCustomCondition($text) {
    switch (trim($text)) {
        case 'CUSTOM_ENERGY_CHECK':
            return BotEnergyAbove(5000);
        case 'CUSTOM_FLEET_READY':
            return checkFleetReady();
        default:
            // Evaluate as PHP expression
            return @eval("return ($text);");
    }
}
```

---

## Block Properties

### JSON Structure
Each block is defined as a JSON object with the following properties:

```json
{
  "key": 1,                    // Unique identifier (integer)
  "category": "Start",         // Block category (string)
  "text": "Start",             // Block content/label (string)
  "loc": "100 100",           // Visual position (string)
  "figure": "Rectangle"        // Visual shape (optional)
}
```

### Property Details

#### key
- **Type:** Integer
- **Required:** Yes
- **Description:** Unique identifier within strategy
- **Usage:** Referenced by link connections

#### category
- **Type:** String
- **Required:** No (defaults to action block)
- **Values:** "Start", "End", "Cond", "Label", "Branch"
- **Description:** Determines block behavior and appearance

#### text
- **Type:** String
- **Required:** Yes
- **Description:** Block content, condition, or action
- **Usage:** Determines block functionality

#### loc
- **Type:** String (format: "x y")
- **Required:** Yes
- **Description:** Visual position in editor
- **Usage:** Editor layout only

### Visual Properties

#### figure
- **Type:** String
- **Values:** "Rectangle", "Ellipse", "Diamond"
- **Description:** Override default shape

#### color
- **Type:** String
- **Description:** Custom color (rarely used)

---

## Connection System

### Port System
Blocks connect through named ports:
- **T:** Top port
- **B:** Bottom port
- **L:** Left port
- **R:** Right port

### Link Structure
```json
{
  "from": 1,              // Source block key
  "to": 2,                // Target block key
  "fromPort": "B",        // Source port
  "toPort": "T"           // Target port
}
```

### Connection Rules

#### Start Block
- **Outputs:** Bottom port only
- **Inputs:** None

#### End Block
- **Inputs:** Top port only
- **Outputs:** None

#### Action Block
- **Inputs:** Top port
- **Outputs:** Bottom port

#### Condition Block
- **Inputs:** Top port
- **Outputs:** Left (YES), Right (NO), Bottom (default)

#### Label Block
- **Inputs:** Top port
- **Outputs:** Bottom port

#### Branch Block
- **Inputs:** Top port
- **Outputs:** None (jumps to label)

### Special Connections

#### Condition Block Child Labels
Condition blocks look for child connections with specific text:
- **"yes"** or **"YES":** True path
- **"no"** or **"NO":** False path
- **Percentage (e.g., "75%"):** Probability path

#### Flow Direction
Standard flow is top-to-bottom and left-to-right:
```
[Block] → [Block] → [Block]
   ↓         ↓         ↓
[Block]   [Block]   [Block]
```

---

## Advanced Block Usage

### Parameterized Blocks
Some blocks accept parameters through the queue system:

```php
// Add BUILD_FLEET block with parameters
AddBotQueue($BotID, $strat_id, $block_id, $BotNow, 0, [GID_F_SC, 50]);
```

### Error Handling
Blocks should handle errors gracefully:

```php
function safeBuildAction() {
    try {
        $result = BotBuild(GID_B_METAL_MINE);
        return $result > 0 ? $result : 60; // Default 1-minute wait
    } catch (Exception $e) {
        Debug("Build action failed: " . $e->getMessage());
        return 300; // 5-minute error delay
    }
}
```

### Performance Considerations
- Avoid infinite loops without delays
- Use appropriate sleep times for resource-intensive operations
- Consider server load when designing block logic
- Implement timeouts for long-running operations

### Block Documentation
Document complex blocks for maintenance:

```json
{
  "key": 20,
  "category": "",
  "text": "// Advanced mining optimization\nif (BotEnergyAbove(1000)) {\n    BotResourceSettings(100,100,100,100,100,100);\n    return 3600; // 1 hour\n} else {\n    BotResourceSettings(100,100,50,100,100,100);\n    return 1800; // 30 minutes\n}",
  "loc": "200 400"
}
```