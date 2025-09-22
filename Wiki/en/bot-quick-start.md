# Bot Quick Start Guide

Get up and running with OGame bots in minutes. This guide covers the essential steps to create, deploy, and manage your first bot.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Creating Your First Bot](#creating-your-first-bot)
3. [Basic Strategy Creation](#basic-strategy-creation)
4. [Testing and Deployment](#testing-and-deployment)
5. [Common Issues](#common-issues)

---

## Prerequisites

### Admin Access
- Admin level 2 or higher required
- Access to the admin panel via `index.php?page=admin`

### Required Strategy
- Must have a strategy named `_start` 
- This is the entry point for all new bots

---

## Creating Your First Bot

### Step 1: Access Bot Management
1. Navigate to the admin panel
2. Click on "Bots" section
3. You'll see the bot management interface

### Step 2: Create the _start Strategy
If you don't have a `_start` strategy yet:

1. Go to "Bot Edit" in admin panel
2. Create a new strategy named `_start`
3. Use this minimal strategy:

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
      "text": "BUILD",
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

### Step 3: Add Your First Bot
1. Return to "Bots" section
2. Enter a unique bot name in the form
3. Click submit
4. Bot will be created and automatically started

### Step 4: Verify Bot Creation
- Check the bot list to see your new bot
- Note the bot ID, name, and home planet
- Bot should appear with a "Stop" action link

---

## Basic Strategy Creation

### Understanding the Visual Editor

The strategy editor is a drag-and-drop interface with:
- **Palette** (left side): Available block types
- **Canvas** (center): Strategy workspace  
- **Properties** (right side): Block configuration

### Block Types You'll Use Most

#### Start Block
- Green ellipse
- Entry point for strategy
- Every strategy needs exactly one

#### Action Blocks  
- Gray rectangles
- Perform bot actions
- Common actions: BUILD, RESEARCH, BUILD_FLEET

#### Condition Blocks
- Yellow diamonds  
- Make decisions
- Have YES/NO output paths
- Common conditions: CAN_BUILD, CAN_RESEARCH

#### End Block
- Red ellipse
- Terminates strategy
- Optional (can use Branch instead)

### Creating a Simple Mining Strategy

1. **Drag blocks from palette:**
   - Start block
   - Action block (set text to "BUILD")
   - Condition block (set text to "CAN_BUILD") 
   - End block

2. **Connect the blocks:**
   - Start → BUILD action
   - BUILD → CAN_BUILD condition
   - CAN_BUILD YES → BUILD action (creates loop)
   - CAN_BUILD NO → End

3. **Save the strategy:**
   - Use the save button
   - Name it "simple_miner"

### Strategy Flow
```
Start → BUILD → Can Build? → Yes (loop back to BUILD)
                          → No (End)
```

---

## Testing and Deployment

### Testing Your Strategy

1. **Create a test bot:**
   - Use a test name like "testbot_01"
   - Monitor its behavior

2. **Check bot activity:**
   - View bot list regularly
   - Monitor resource changes
   - Check building progress

3. **Debug issues:**
   - Enable bot tracing if needed
   - Check for stuck bots
   - Verify strategy logic

### Deploying to Production

1. **Backup existing strategies:**
   - Export current strategies
   - Save configuration

2. **Update _start strategy:**
   - Replace with your tested strategy
   - All new bots will use the new strategy

3. **Create production bots:**
   - Add bots with appropriate names
   - Monitor performance

---

## Common Issues

### Bot Won't Start
**Problem:** "Bot not found" or "Strategy not found" error

**Solutions:**
- Verify `_start` strategy exists
- Check strategy JSON syntax
- Ensure admin permissions
- Verify database connectivity

### Bot Gets Stuck
**Problem:** Bot appears in list but no activity

**Solutions:**
- Check for infinite loops in strategy
- Verify building requirements are met
- Check resource availability
- Look for condition blocks without proper exits

### Strategy Not Working
**Problem:** Bot doesn't follow expected behavior

**Solutions:**
- Verify block connections
- Check condition logic
- Test individual blocks
- Add debug actions

### Performance Issues
**Problem:** Bots running slowly or server lag

**Solutions:**
- Reduce number of active bots
- Optimize strategy complexity
- Add appropriate sleep delays
- Monitor server resources

---

## Next Steps

### Learn More Advanced Features
- **[Bot Strategy Development Guide](bot-strategy-guide.md)** - Advanced strategy patterns
- **[Bot Personality System](bot-personality-system.md)** - Customize bot behavior
- **[Bot Examples and Tutorials](bot-examples.md)** - Practical examples

### Explore the API
- **[Bot API Reference](bot-api-reference.md)** - All available functions
- **[Bot Block Reference](bot-block-reference.md)** - Complete block documentation

### Administration Tools
- **[Bot Administration Guide](bot-administration.md)** - Management and monitoring

---

## Sample Strategies

### Basic Economy Bot
Focuses on building resource production:

```
Start → Check if basic economy done → No: BUILD economy buildings
                                   → Yes: Check if can research → RESEARCH
```

### Balanced Bot  
Builds economy, then fleet:

```
Start → Economy phase → Fleet phase → Research phase → End/Loop
```

### Mining Specialist
Pure resource focus:

```
Start → BUILD mines → Optimize production → Monitor energy → Loop
```

Use these as starting points and customize based on your needs!

---

## Quick Commands

### Essential Bot Functions
```php
// Building
BotBuild(1)              // Build metal mine
BotCanBuild(1)           // Check if can build metal mine
BotGetBuild(1)           // Get metal mine level

// Research  
BotResearch(113)         // Research energy technology
BotCanResearch(113)      // Check if can research energy
BotGetResearch(113)      // Get energy tech level

// Fleet
BotBuildFleet(202, 10)   // Build 10 small cargo ships
BotGetFleetCount(202)    // Get small cargo count

// Resources
BotResourceSettings(100, 100, 50, 100, 100, 100)  // Reduce deuterium to 50%
BotEnergyAbove(1000)     // Check if energy >= 1000

// Variables
BotSetVar('phase', 'building')    // Set bot variable
BotGetVar('phase', 'init')        // Get bot variable
```

### Common Building IDs
- 1: Metal Mine
- 2: Crystal Mine  
- 3: Deuterium Synthesizer
- 4: Solar Plant
- 14: Robotics Factory
- 21: Shipyard
- 31: Research Lab

### Common Ship IDs
- 202: Small Cargo
- 203: Large Cargo
- 204: Light Fighter
- 206: Cruiser
- 210: Espionage Probe

Start with these basics and expand as you learn more!