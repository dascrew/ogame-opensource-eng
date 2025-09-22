# Bot Strategy Development Guide

This guide explains how to create and manage bot strategies in the OGame engine using the visual strategy editor.

## Table of Contents

1. [Strategy Overview](#strategy-overview)
2. [Block Types](#block-types)
3. [Creating Strategies](#creating-strategies)
4. [Flow Control](#flow-control)
5. [Best Practices](#best-practices)
6. [Common Patterns](#common-patterns)
7. [Debugging Strategies](#debugging-strategies)

---

## Strategy Overview

Bot strategies are visual flowcharts consisting of connected blocks that define bot behavior. Each strategy is stored as a JSON structure containing nodes and links that represent the decision-making process.

### Key Concepts

- **Blocks (Nodes):** Individual units of logic or action
- **Connections (Links):** Define the flow between blocks
- **Strategy:** A complete flowchart defining bot behavior
- **Execution:** Strategies are converted to queue tasks for execution

---

## Block Types

### Start Block
**Category:** `Start`  
**Description:** Entry point for strategy execution  
**Connections:** One output to begin strategy flow

```
[Start] → [Next Block]
```

### End Block
**Category:** `End`  
**Description:** Terminates strategy execution  
**Connections:** One input, no outputs

```
[Previous Block] → [End]
```

### Action Block
**Category:** Default (no specific category)  
**Description:** Performs specific actions based on text content  
**Connections:** One input, one output (usually)

#### Built-in Actions

**BUILD**
- Automatically determines next building to construct based on personality
- Waits for completion before proceeding

**RESEARCH**
- Automatically determines next research based on personality
- Waits for completion before proceeding

**BUILD_FLEET**
- Builds fleet units (requires parameters in queue)
- Parameters: `[ship_id, amount]`

**BUILD_WAIT**
- Waits for current building construction to complete
- Uses `BotGetLastBuilt()` to determine wait time

**Custom Actions**
- Any PHP code can be evaluated as custom actions
- Must return integer (sleep time in seconds)

### Condition Block
**Category:** `Cond`  
**Description:** Makes decisions based on conditions  
**Connections:** One input, two outputs (YES/NO paths)

#### Built-in Conditions

**CAN_BUILD**
- Checks if any building can be built based on personality priorities

**CAN_RESEARCH**
- Checks if any research can be started based on personality priorities

**BASIC_DONE**
- Checks if basic infrastructure is complete
- `BotGetBuild(1) >= 4 && BotGetBuild(2) >= 2 && BotGetBuild(4) >= 4`

**IS_MINER**
- Checks if bot personality is 'miner'

**IS_FLEETER**
- Checks if bot personality is 'fleeter'

**Custom Conditions**
- Any PHP expression that returns boolean
- Example: `BotGetBuild(GID_B_METAL_MINE) >= 10`

### Label Block
**Category:** `Label`  
**Description:** Named waypoint for branching  
**Connections:** One input, one output

```
[Label: "build_phase"]
```

### Branch Block
**Category:** `Branch`  
**Description:** Jumps to a labeled section  
**Connections:** One input, no direct output (jumps to label)

```
[Branch] → jumps to → [Label: "target_name"]
```

---

## Creating Strategies

### 1. Access the Strategy Editor

Navigate to the admin panel and select "Bot Edit" to access the visual strategy editor.

### 2. Basic Strategy Structure

Every strategy must have:
- One `Start` block
- At least one `End` block or branch to another strategy
- Connected flow between blocks

### 3. Simple Example Strategy

```
[Start] → [Action: BUILD] → [Condition: CAN_BUILD] → YES → [Action: BUILD]
                                    ↓
                                   NO
                                    ↓
                                 [End]
```

### 4. Advanced Example Strategy

```
[Start] → [Condition: BASIC_DONE] → YES → [Action: RESEARCH] → [End]
                    ↓
                   NO
                    ↓
         [Action: BUILD] → [Action: BUILD_WAIT] → [Branch: "start"]
```

---

## Flow Control

### Sequential Execution
Blocks execute one after another following connections.

### Conditional Branching
Use `Cond` blocks to create decision points:
- Connect to child blocks labeled "yes" or "no"
- Percentage labels (e.g., "75%") create probability-based branching

### Loops
Create loops using `Branch` blocks that jump back to `Label` blocks:

```
[Label: "loop_start"] → [Action] → [Condition] → YES → [Branch: "loop_start"]
                                        ↓
                                       NO
                                        ↓
                                     [End]
```

### Parallel Execution
Use `BotExec()` to start additional strategies in parallel:

```php
// In a custom action block
BotExec("mining_strategy");
BotExec("defense_strategy");
```

---

## Best Practices

### 1. Strategy Organization
- Use descriptive names for strategies
- Break complex logic into smaller, reusable strategies
- Use labels to organize major sections

### 2. Error Handling
- Always provide fallback paths for conditions
- Use `End` blocks to gracefully terminate strategies
- Test edge cases (no resources, max building levels, etc.)

### 3. Performance Considerations
- Avoid infinite loops without delays
- Use appropriate sleep times for resource-intensive operations
- Consider bot personality when setting priorities

### 4. Resource Management
- Check resource availability before major actions
- Implement energy management for mining operations
- Balance between different resource types

### 5. Timing
- Use `BUILD_WAIT` for building completion
- Implement delays between expensive operations
- Consider server load and queue timing

---

## Common Patterns

### Basic Development Pattern
```
[Start] → [Build Infrastructure] → [Research Basics] → [Build Fleet] → [End]
```

### Conditional Building Pattern
```
[Check Resources] → [Sufficient?] → YES → [Build] → [Wait] → [Check Resources]
                          ↓
                         NO
                          ↓
                    [Wait for Resources]
```

### Personality-Based Pattern
```
[Start] → [Check Personality] → [Miner] → [Mining Strategy]
                    ↓
              [Fleeter] → [Fleet Strategy]
                    ↓
              [Default] → [Balanced Strategy]
```

### Multi-Phase Pattern
```
[Start] → [Phase 1: Economy] → [Phase 2: Military] → [Phase 3: Expansion] → [End]
```

---

## Debugging Strategies

### 1. Enable Tracing
Set bot tracing in configuration to monitor block execution:
```php
$BOT_TRACE_ENABLED = true;
$BOT_TRACE_SAMPLING = 100; // 100% sampling for full debugging
```

### 2. Use Debug Outputs
Add debug messages in custom actions:
```php
Debug("Current metal mine level: " . BotGetBuild(GID_B_METAL_MINE));
```

### 3. Check Bot Status
Monitor bot activity through admin panel:
- View active bots
- Check queue status
- Monitor resource levels

### 4. Common Issues

**Strategy Not Starting**
- Ensure `_start` strategy exists
- Check for syntax errors in JSON
- Verify Start block exists and is connected

**Infinite Loops**
- Add sleep delays in action blocks
- Implement exit conditions
- Use proper flow control

**Resource Starvation**
- Check energy levels
- Verify resource production settings
- Implement resource waiting logic

### 5. Testing Strategies

**Unit Testing**
- Test individual blocks with known conditions
- Verify condition logic with different game states
- Test edge cases (zero resources, max levels)

**Integration Testing**
- Run complete strategies on test bots
- Monitor long-term behavior
- Check for resource management issues

**Performance Testing**
- Monitor server load during strategy execution
- Check queue processing times
- Verify memory usage

---

## Strategy File Format

Strategies are stored as JSON with the following structure:

```json
{
  "class": "go.GraphLinksModel",
  "linkFromPortIdProperty": "fromPort",
  "linkToPortIdProperty": "toPort",
  "nodeDataArray": [
    {
      "key": 1,
      "category": "Start",
      "text": "Start",
      "loc": "100 100"
    },
    {
      "key": 2,
      "category": "Cond",
      "text": "CAN_BUILD",
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

### Node Properties
- `key`: Unique identifier
- `category`: Block type (Start, End, Cond, Label, Branch)
- `text`: Block content/condition/action
- `loc`: Visual position in editor

### Link Properties
- `from`/`to`: Node keys to connect
- `fromPort`/`toPort`: Connection points (T=Top, B=Bottom, L=Left, R=Right)

---

## Advanced Techniques

### Dynamic Strategy Modification
Bots can modify their own strategies at runtime:
```php
// Save current strategy as backup
// Modify strategy in database
// Changes apply to all bots using the strategy
```

### Strategy Inheritance
Create base strategies that other strategies can build upon:
```php
BotExec("base_economy");  // Start economy strategy
BotExec("military_addon"); // Add military behavior
```

### Parameter Passing
Use bot variables to pass parameters between strategies:
```php
BotSetVar('target_fleet_size', 1000);
BotExec("fleet_builder");
```

### Conditional Strategy Loading
Load different strategies based on game state:
```php
if (BotGetVar('game_phase') == 'early') {
    BotExec("early_game_strategy");
} else {
    BotExec("late_game_strategy");
}
```