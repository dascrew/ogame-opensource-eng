# OGame Architecture Diagram

This document provides visual representations of the OGame codebase architecture and dependencies.

## System Architecture Overview

```mermaid
graph TB
    subgraph "Entry Points"
        INDEX[index.php]
        AINFO[ainfo.php]
        PRANGER[pranger.php]
        CRON[cron.php]
        INSTALL[install.php]
    end
    
    subgraph "Core System"
        CONFIG[config.php]
        DB[db.php]
        UTILS[utils.php]
        DEBUG[debug.php]
    end
    
    subgraph "Game Engine"
        PROD[prod.php]
        PLANET[planet.php]
        USER[user.php]
        FLEET[fleet.php]
        BATTLE[battle_engine.php]
        QUEUE[queue.php]
    end
    
    subgraph "Game Features"
        ALLY[ally.php]
        MSG[msg.php]
        NOTES[notes.php]
        LOCA[loca.php]
    end
    
    subgraph "User Interface"
        PAGES[pages/*.php]
        ADMIN[pages_admin/*.php]
        PAGE[page.php]
    end
    
    subgraph "Bot System"
        BOT[bot.php]
        BOT_FLEET[bot_fleet.php]
        BOT_ALLIANCE[bot_alliance.php]
        BOT_TARGET[bot_target.php]
    end
    
    INDEX --> CONFIG
    AINFO --> CONFIG
    PRANGER --> CONFIG
    CRON --> CONFIG
    
    CONFIG --> DB
    CONFIG --> UTILS
    
    DB --> PROD
    DB --> PLANET
    DB --> USER
    DB --> FLEET
    
    UTILS --> QUEUE
    
    PROD --> QUEUE
    PLANET --> QUEUE
    USER --> QUEUE
    FLEET --> QUEUE
    FLEET --> BATTLE
    
    INDEX --> PAGES
    PAGES --> PAGE
    ADMIN --> PAGE
    
    BOT --> BOT_FLEET
    BOT --> BOT_ALLIANCE
    BOT --> BOT_TARGET
    BOT --> USER
    BOT --> PLANET
```

## Core Module Dependencies

```mermaid
graph LR
    subgraph "Database Layer"
        CONFIG[config.php]
        DB[db.php]
        UTILS[utils.php]
    end
    
    subgraph "Game State"
        USER[user.php]
        PLANET[planet.php]
        FLEET[fleet.php]
    end
    
    subgraph "Game Logic"
        PROD[prod.php]
        QUEUE[queue.php]
        BATTLE[battle_engine.php]
    end
    
    CONFIG --> DB
    DB --> USER
    DB --> PLANET
    DB --> FLEET
    
    UTILS --> PROD
    UTILS --> QUEUE
    
    USER --> PROD
    PLANET --> PROD
    FLEET --> PROD
    
    PROD --> QUEUE
    FLEET --> BATTLE
    QUEUE --> BATTLE
```

## Function Call Hierarchy

```mermaid
graph TD
    subgraph "Resource Management"
        PROD_RES[ProdResources]
        BUILD_COST[BuildingCost]
        RES_COST[ResearchCost]
        CAN_BUILD[CanBuild]
        CAN_RESEARCH[CanResearch]
    end
    
    subgraph "Planet Operations"
        LOAD_PLANET[LoadPlanet]
        SAVE_PLANET[SavePlanet]
        ADJUST_RES[AdjustResources]
        GET_BUILD_LVL[GetBuildingLevel]
    end
    
    subgraph "Queue System"
        ADD_QUEUE[AddQueue]
        PROCESS_QUEUE[ProcessQueue]
        START_BUILD[StartBuilding]
        START_RESEARCH[StartResearch]
    end
    
    subgraph "Fleet System"
        SEND_FLEET[SendFleet]
        FLEET_ARRIVE[FleetArrive]
        FLIGHT_TIME[FlightTime]
        ATTACK_PLANET[AttackPlanet]
    end
    
    subgraph "Battle System"
        BATTLE_ENGINE[BattleEngine]
        CALC_ATTACK[CalcShipAttack]
        CALC_DEFENSE[CalcShipDefense]
        BATTLE_REPORT[BattleReport]
    end
    
    LOAD_PLANET --> PROD_RES
    PROD_RES --> GET_BUILD_LVL
    
    START_BUILD --> CAN_BUILD
    CAN_BUILD --> BUILD_COST
    START_BUILD --> ADJUST_RES
    START_BUILD --> ADD_QUEUE
    
    START_RESEARCH --> CAN_RESEARCH
    CAN_RESEARCH --> RES_COST
    START_RESEARCH --> ADD_QUEUE
    
    SEND_FLEET --> FLIGHT_TIME
    SEND_FLEET --> ADD_QUEUE
    
    FLEET_ARRIVE --> ATTACK_PLANET
    ATTACK_PLANET --> BATTLE_ENGINE
    BATTLE_ENGINE --> CALC_ATTACK
    BATTLE_ENGINE --> CALC_DEFENSE
    BATTLE_ENGINE --> BATTLE_REPORT
```

## Page Controller Flow

```mermaid
graph TD
    subgraph "Main Entry"
        INDEX[index.php]
        SESSION{Session Check}
        ROUTE{Page Router}
    end
    
    subgraph "Game Pages"
        OVERVIEW[overview.php]
        BUILDINGS[buildings.php]
        RESEARCH[research.php]
        FLEET1[flotten1.php]
        GALAXY[galaxy.php]
        ALLIANCE[allianzen.php]
    end
    
    subgraph "Admin Pages"
        ADMIN[admin.php]
        ADMIN_USERS[admin_users.php]
        ADMIN_PLANETS[admin_planets.php]
        ADMIN_BOTS[admin_bots.php]
    end
    
    subgraph "Core Functions"
        LOAD_USER[LoadUser]
        LOAD_PLANET[LoadPlanet]
        PROD_RES[ProdResources]
        PROCESS_QUEUE[ProcessQueue]
    end
    
    INDEX --> SESSION
    SESSION --> ROUTE
    
    ROUTE --> OVERVIEW
    ROUTE --> BUILDINGS
    ROUTE --> RESEARCH
    ROUTE --> FLEET1
    ROUTE --> GALAXY
    ROUTE --> ALLIANCE
    ROUTE --> ADMIN
    
    ADMIN --> ADMIN_USERS
    ADMIN --> ADMIN_PLANETS
    ADMIN --> ADMIN_BOTS
    
    OVERVIEW --> LOAD_USER
    OVERVIEW --> LOAD_PLANET
    OVERVIEW --> PROD_RES
    OVERVIEW --> PROCESS_QUEUE
    
    BUILDINGS --> LOAD_PLANET
    BUILDINGS --> PROD_RES
    
    RESEARCH --> LOAD_USER
    RESEARCH --> LOAD_PLANET
```

## Bot System Architecture

```mermaid
graph TD
    subgraph "Bot Core"
        BOT[bot.php]
        BOT_LIFECYCLE[bot_lifecycle.php]
        BOT_UTILS[bot_utils.php]
        BOT_VARS[bot_vars.php]
    end
    
    subgraph "Bot Modules"
        BOT_FLEET[bot_fleet.php]
        BOT_PLANET[bot_planet.php]
        BOT_TARGET[bot_target.php]
        BOT_ALLIANCE[bot_alliance.php]
        BOT_TRAUMA[bot_trauma.php]
    end
    
    subgraph "Game Integration"
        USER[user.php]
        PLANET[planet.php]
        FLEET[fleet.php]
        QUEUE[queue.php]
    end
    
    subgraph "AI Logic"
        PERSONALITY[personality.php]
        SKILLS[skills.php]
    end
    
    BOT --> BOT_LIFECYCLE
    BOT --> BOT_UTILS
    BOT --> BOT_VARS
    
    BOT_LIFECYCLE --> BOT_FLEET
    BOT_LIFECYCLE --> BOT_PLANET
    BOT_LIFECYCLE --> BOT_TARGET
    BOT_LIFECYCLE --> BOT_ALLIANCE
    
    BOT_FLEET --> FLEET
    BOT_PLANET --> PLANET
    BOT_TARGET --> USER
    BOT_ALLIANCE --> ALLY
    
    BOT --> PERSONALITY
    BOT --> SKILLS
    
    BOT_UTILS --> QUEUE
```

## Database Access Pattern

```mermaid
graph TB
    subgraph "Application Layer"
        PAGES[Page Controllers]
        ADMIN[Admin Controllers]
        BOTS[Bot System]
        CRON[Cron Jobs]
    end
    
    subgraph "Business Logic"
        USER[user.php]
        PLANET[planet.php]
        FLEET[fleet.php]
        ALLY[ally.php]
        QUEUE[queue.php]
    end
    
    subgraph "Data Access"
        DB[db.php]
        DBQUERY[dbquery()]
        DBARRAY[dbarray()]
        ADDROW[AddDBRow()]
    end
    
    subgraph "Database"
        MYSQL[(MySQL Database)]
        TABLES[Tables: users, planets, fleets, etc.]
    end
    
    PAGES --> USER
    PAGES --> PLANET
    PAGES --> FLEET
    ADMIN --> USER
    ADMIN --> PLANET
    BOTS --> USER
    BOTS --> PLANET
    CRON --> QUEUE
    
    USER --> DB
    PLANET --> DB
    FLEET --> DB
    ALLY --> DB
    QUEUE --> DB
    
    DB --> DBQUERY
    DB --> DBARRAY
    DB --> ADDROW
    
    DBQUERY --> MYSQL
    DBARRAY --> MYSQL
    ADDROW --> MYSQL
    
    MYSQL --> TABLES
```

## Security and Session Flow

```mermaid
graph TD
    subgraph "Entry Point"
        REQUEST[HTTP Request]
        INDEX[index.php]
    end
    
    subgraph "Security Checks"
        CONFIG_CHECK{Config Exists?}
        SESSION_CHECK{Session Valid?}
        SECURITY_CHECK[SecurityCheck()]
        FREEZE_CHECK{Universe Frozen?}
    end
    
    subgraph "User Context"
        LOAD_USER[LoadUser()]
        GLOBAL_USER[GlobalUser]
        LOAD_UNI[LoadUniverse()]
        GLOBAL_UNI[GlobalUni]
    end
    
    subgraph "Page Processing"
        PAGE_ROUTER{Page Router}
        GAME_PAGES[Game Pages]
        ADMIN_PAGES[Admin Pages]
    end
    
    REQUEST --> INDEX
    INDEX --> CONFIG_CHECK
    CONFIG_CHECK -->|No| INSTALL[install.php]
    CONFIG_CHECK -->|Yes| SESSION_CHECK
    SESSION_CHECK -->|No| REDIRECT[RedirectHome()]
    SESSION_CHECK -->|Yes| SECURITY_CHECK
    SECURITY_CHECK --> FREEZE_CHECK
    FREEZE_CHECK -->|Frozen & Not Admin| ERROR[Access Denied]
    FREEZE_CHECK -->|OK| LOAD_USER
    LOAD_USER --> GLOBAL_USER
    LOAD_USER --> LOAD_UNI
    LOAD_UNI --> GLOBAL_UNI
    GLOBAL_USER --> PAGE_ROUTER
    GLOBAL_UNI --> PAGE_ROUTER
    PAGE_ROUTER --> GAME_PAGES
    PAGE_ROUTER --> ADMIN_PAGES
```

---

*These diagrams illustrate the high-level architecture and relationships in the OGame codebase. They help visualize how different components interact and depend on each other.*