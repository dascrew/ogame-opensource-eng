# Complete Function Reference

This document provides a complete alphabetical reference of all functions found in the OGame codebase with their locations and basic usage patterns.

## Function Definitions by File

### ally.php (31 functions)
- `AcceptBuddy()` - Accept buddy request
- `AddApplication()` - Add alliance application
- `AddBuddy()` - Add buddy to list
- `AddRank()` - Add alliance rank
- `AllyChangeName()` - Change alliance name
- `AllyChangeOwner()` - Change alliance leader
- `AllyChangeTag()` - Change alliance tag
- `ApplyAlly()` - Apply to alliance
- `CanKick()` - Check if member can be kicked
- `CreateAlliance()` - Create new alliance
- `CreateRank()` - Create alliance rank
- `DeleteAlliance()` - Delete alliance
- `DeleteApplication()` - Delete application
- `DeleteBuddy()` - Remove buddy
- `DeleteRank()` - Delete alliance rank
- `DenyApplication()` - Deny alliance application
- `EditRank()` - Edit alliance rank
- `GetAlliance()` - Get alliance data
- `GetAlliances()` - Get all alliances
- `GetBuddyList()` - Get buddy list
- `GetBuddyRequests()` - Get buddy requests
- `GetMember()` - Get alliance member
- `GetMembers()` - Get alliance members
- `GetRank()` - Get alliance rank
- `GetRanks()` - Get alliance ranks
- `IsAllyOwner()` - Check if alliance owner
- `KickMember()` - Kick alliance member
- `LoadAlliances()` - Load alliance data
- `RefuseBuddy()` - Refuse buddy request
- `SaveAlliance()` - Save alliance data
- `SetRank()` - Set member rank

### battle.php (8 functions)
- `BattleReport()` - Generate battle report
- `CheckFleetBattle()` - Check for fleet battles
- `FleetToPlanet()` - Move fleet to planet
- `GetBattleReports()` - Get battle reports
- `GetFleetsByCoordinates()` - Get fleets at coordinates
- `PlunderResources()` - Handle resource plundering
- `SaveBattleReport()` - Save battle report
- `ShowTargetInfo()` - Display target information

### battle_engine.php (28 functions)
- `BattleDebug()` - Battle debugging output
- `BattleEngine()` - Main battle calculation engine
- `CalcShipAttack()` - Calculate ship attack power
- `CalcShipDefense()` - Calculate ship defense
- `CalcShipShield()` - Calculate ship shields
- `CalcShipSpeed()` - Calculate ship speed
- `CanFight()` - Check if unit can fight
- `CreateBattleGroup()` - Create battle group
- `DamageShip()` - Apply damage to ship
- `GetAttackerShips()` - Get attacking ships
- `GetDefenderShips()` - Get defending ships
- `GetRandomTarget()` - Select random target
- `GetShipClass()` - Get ship class
- `GetShipStats()` - Get ship statistics
- `GetUnitAttack()` - Get unit attack value
- `GetUnitDefense()` - Get unit defense value
- `GetUnitShield()` - Get unit shield value
- `HasShips()` - Check if has ships
- `IsExpedition()` - Check if expedition
- `IsFleetDestroyed()` - Check if fleet destroyed
- `IsRapidFire()` - Check rapid fire capability
- `ProcessBattleRound()` - Process battle round
- `ProcessRapidFire()` - Process rapid fire
- `SelectTarget()` - Select battle target
- `ShipIsDestroyed()` - Check if ship destroyed
- `SortShipsByType()` - Sort ships by type
- `TotalShipCount()` - Count total ships
- `UpdateBattleStats()` - Update battle statistics

### bbcode.php (3 functions)
- `bbcode()` - Process BBCode formatting
- `parseReports()` - Parse battle reports
- `playerlink()` - Generate player links

### bot.php (63 functions)
- `AddBot()` - Add new bot
- `AddBotQueue()` - Add bot to queue
- `BotAttack()` - Bot attack logic
- `BotBuild()` - Bot building logic
- `BotCanAttack()` - Check if bot can attack
- `BotCanBuild()` - Check if bot can build
- `BotCanResearch()` - Check if bot can research
- `BotColonize()` - Bot colonization
- `BotCreatePlanet()` - Bot planet creation
- `BotDeletePlanet()` - Bot planet deletion
- `BotDestroy()` - Bot destruction logic
- `BotExpedition()` - Bot expedition logic
- `BotFleet()` - Bot fleet management
- `BotGetDumpCoords()` - Get dump coordinates
- `BotGetEmptyCoords()` - Get empty coordinates
- `BotGetTargets()` - Get bot targets
- `BotIsEnabled()` - Check if bot enabled
- `BotLifecycle()` - Bot lifecycle management
- `BotLoadTarget()` - Load bot target
- `BotMoon()` - Bot moon logic
- `BotProcess()` - Process bot actions
- `BotProcessQueue()` - Process bot queue
- `BotRecycle()` - Bot recycling logic
- `BotResearch()` - Bot research logic
- `BotSendFleet()` - Bot fleet sending
- `BotSetTarget()` - Set bot target
- `BotTransport()` - Bot transport logic
- `CanDelete()` - Check if can delete
- `CreateBot()` - Create new bot
- `DeleteBot()` - Delete bot
- `DisableBot()` - Disable bot
- `EnableBot()` - Enable bot
- `GetBot()` - Get bot data
- `GetBotDifficulty()` - Get bot difficulty
- `GetBotName()` - Get bot name
- `GetBotPlanets()` - Get bot planets
- `GetBots()` - Get all bots
- `IsMoonBot()` - Check if moon bot
- `LoadBot()` - Load bot data
- `SaveBot()` - Save bot data
- (Additional bot functions...)

### db.php (34 functions)
- `AddDBRow()` - Add database row
- `dbarray()` - Fetch database array
- `dbconnect()` - Connect to database
- `dbfree()` - Free database result
- `dbquery()` - Execute database query
- `dbrows()` - Count database rows
- `DeleteDBRow()` - Delete database row
- `GetDBRow()` - Get database row
- `GetDBValue()` - Get database value
- `InitDB()` - Initialize database
- `MDBConnect()` - Master database connect
- `MDBQuery()` - Master database query
- `MDBRows()` - Master database rows
- `SaveDBRow()` - Save database row
- `UpdateDBRow()` - Update database row
- (Additional database functions...)

### fleet.php (53 functions)
- `AddUnionMember()` - Add union member
- `AdjustShips()` - Adjust ship counts
- `AttackArrive()` - Handle attack arrival
- `CanSendFleet()` - Check if can send fleet
- `CheckFleetSecurity()` - Check fleet security
- `CreateFleet()` - Create new fleet
- `DeleteFleet()` - Delete fleet
- `FleetArrive()` - Handle fleet arrival
- `FleetReturn()` - Handle fleet return
- `FlightTime()` - Calculate flight time
- `GetFleet()` - Get fleet data
- `GetFleets()` - Get all fleets
- `GetShipName()` - Get ship name
- `GetShipNames()` - Get ship names
- `LoadFleets()` - Load fleet data
- `SaveFleet()` - Save fleet data
- `SendFleet()` - Send fleet
- (Additional fleet functions...)

### planet.php (41 functions)
- `AddDebris()` - Add debris field
- `AdjustResources()` - Adjust planet resources
- `AdminPlanetCoord()` - Admin planet coordinates
- `AdminPlanetName()` - Admin planet name
- `CanColonize()` - Check if can colonize
- `CreatePlanet()` - Create new planet
- `DeletePlanet()` - Delete planet
- `GetBuildingLevel()` - Get building level
- `GetCoordinates()` - Get planet coordinates
- `GetDebris()` - Get debris data
- `GetPlanet()` - Get planet data
- `GetPlanetByCoords()` - Get planet by coordinates
- `GetPlanetName()` - Get planet name
- `GetPlanets()` - Get all planets
- `LoadPlanet()` - Load planet data
- `SavePlanet()` - Save planet data
- (Additional planet functions...)

### prod.php (38 functions)
- `BuildingCost()` - Calculate building cost
- `BuildingMeetRequirement()` - Check building requirements
- `CanBuild()` - Check if can build
- `CanDemolish()` - Check if can demolish
- `CanResearch()` - Check if can research
- `GetBuildingDescription()` - Get building description
- `GetBuildingName()` - Get building name
- `GetResearchDescription()` - Get research description
- `GetResearchName()` - Get research name
- `ProdResources()` - Calculate resource production
- `ResearchCost()` - Calculate research cost
- `ResearchMeetRequirement()` - Check research requirements
- (Additional production functions...)

### queue.php (45 functions)
- `AddAllowNameEvent()` - Add name change event
- `AddChangeEmailEvent()` - Add email change event
- `AddCleanDebrisEvent()` - Add debris cleanup event
- `AddQueue()` - Add item to queue
- `AddShipyard()` - Add shipyard item
- `DeleteQueue()` - Delete queue item
- `GetQueue()` - Get queue items
- `ProcessQueue()` - Process queue items
- `StartBuilding()` - Start building
- `StartDemolish()` - Start demolition
- `StartResearch()` - Start research
- `StartShipyard()` - Start ship construction
- (Additional queue functions...)

### user.php (29 functions)
- `AdminUserName()` - Admin user name
- `AdjustStats()` - Adjust user stats
- `BanUser()` - Ban user
- `BanUserAttacks()` - Ban user attacks
- `CanDelete()` - Check if can delete
- `CreateUser()` - Create new user
- `DeleteUser()` - Delete user
- `GetUser()` - Get user data
- `GetUserByEmail()` - Get user by email
- `GetUserByName()` - Get user by name
- `GetUsers()` - Get all users
- `LoadUser()` - Load user data
- `SaveUser()` - Save user data
- (Additional user functions...)

### utils.php (15 functions)
- `hostname()` - Get hostname
- `InitDB()` - Initialize database
- `method()` - Get request method
- `nicenum()` - Format numbers
- `pretty_time()` - Format time
- `RedirectHome()` - Redirect to home
- `scriptname()` - Get script name
- `SecurityCheck()` - Security validation
- `sksort()` - Sort array by subkey
- `va()` - Variable argument formatting
- (Additional utility functions...)

## Common Function Patterns

### Loading Functions
Most modules provide `Load*()` functions:
- `LoadUser()` - Load user data
- `LoadPlanet()` - Load planet data
- `LoadFleets()` - Load fleet data
- `LoadAlliances()` - Load alliance data

### Saving Functions
Corresponding `Save*()` functions:
- `SaveUser()` - Save user data
- `SavePlanet()` - Save planet data
- `SaveFleet()` - Save fleet data
- `SaveAlliance()` - Save alliance data

### Validation Functions
`Can*()` and `*MeetRequirement()` functions:
- `CanBuild()` - Check building requirements
- `CanResearch()` - Check research requirements
- `CanSendFleet()` - Check fleet requirements

### Administrative Functions
`Admin*()` functions for administration:
- `AdminUserName()` - Admin user management
- `AdminPlanetName()` - Admin planet management

---

*This reference provides an overview of the main functions. For detailed parameter information and usage examples, refer to the source files directly.*