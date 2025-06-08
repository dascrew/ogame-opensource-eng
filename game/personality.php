<?php
/**
 * Advanced Bot & Alliance Management System - Personality Configuration
 * Phase 1: Foundation & Database Setup
 * 
 * This file defines bot personalities with weighted decision-making systems
 * that integrate with the existing block execution parser.
 * 
 * @author Advanced Bot Management System
 * @version 1.0
 * @date 2025-06-05
 */

/**
 * Global personality configuration array
 * Defines weighted decision-making for dynamic priorities
 */
$PERSONALITIES = array(
    
    // FLEETER PERSONALITY - Combat-focused weighted decisions
    'fleeter' => array(
    'name' => 'Fleeter',
    'description' => 'Combat-focused bot with dynamic fleet-oriented decisions',
    'default_subtype' => 'balanced',
    
    'subtypes' => array(
        'speed' => array(
            'name' => 'Speed Fleeter',
            'description' => 'Fast ships, hit-and-run tactics, frequent activity',
            
            // Weighted building decisions (higher weight = more likely to build)
            'building_weights' => array(
                1 => 25,   // Metal Mine - high for fleet production
                2 => 20,   // Crystal Mine - moderate for ships
                3 => 15,   // Deuterium Synthesizer - moderate for fuel
                4 => 22,   // Solar Plant - high for energy
                12 => 8,   // Fusion Reactor - low priority
                14 => 18,  // Robotics Factory - high for build speed
                15 => 12,  // Nanite Factory - moderate
                21 => 30,  // Shipyard - highest priority
                22 => 16,  // Research Lab - moderate
                31 => 5,   // Missile Silo - very low
                41 => 3,   // Metal Storage - very low
                42 => 3,   // Crystal Storage - very low
                43 => 4    // Deuterium Tank - very low
            ),
            
            // Weighted research decisions
            'research_weights' => array(
                106 => 8,   // Espionage Technology
                108 => 10,  // Computer Technology
                109 => 25,  // Weapons Technology - high
                110 => 22,  // Shielding Technology - high
                111 => 20,  // Armour Technology - high
                113 => 12,  // Energy Technology
                114 => 15,  // Hyperspace Technology
                115 => 28,  // Combustion Drive - highest
                117 => 26,  // Impulse Drive - very high
                118 => 24,  // Hyperspace Drive - high
                120 => 5,   // Laser Technology - low
                121 => 6,   // Ion Technology - low
                122 => 10,  // Computer Technology
                123 => 4,   // Plasma Technology - very low
                124 => 3    // Graviton Research - very low
            ),
            
            // Weighted ship building decisions
            'ship_weights' => array(
                202 => 8,   // Small Cargo Ship - low
                203 => 6,   // Large Cargo Ship - low
                204 => 30,  // Light Fighter - highest
                205 => 25,  // Heavy Fighter - very high
                206 => 20,  // Cruiser - high
                207 => 15,  // Battleship - moderate
                208 => 5,   // Colony Ship - very low
                209 => 3,   // Recycler - very low
                210 => 12,  // Espionage Probe - moderate
                211 => 2,   // Bomber - very low
                212 => 1,   // Solar Satellite - very low
                213 => 8,   // Destroyer - low
                214 => 2,   // Deathstar - very low
                215 => 18   // Battlecruiser - moderate-high
            ),
            
            // Activity pattern weights
            'activity_pattern' => array(
                'base_frequency' => 300,     // 5 minutes base
                'variance' => 120,           // ±2 minutes
                'idle_probability' => 15,    // 15% chance of idle
                'action_weights' => array(
                    'BUILD' => 30,
                    'RESEARCH' => 20,
                    'BUILD_FLEET' => 35,
                    'ATTACK' => 15
                )
            ),
            
            // Building level caps for decision weighting - FIXED: All buildings from weights included
            'building_caps' => array(
                1 => 25,   // Metal Mine
                2 => 20,   // Crystal Mine
                3 => 20,   // Deuterium Synthesizer
                4 => 25,   // Solar Plant
                12 => 8,   // Fusion Reactor
                14 => 10,  // Robotics Factory
                15 => 5,   // Nanite Factory
                21 => 12,  // Shipyard
                22 => 8,   // Research Lab
                31 => 5,   // Missile Silo
                41 => 2,   // Metal Storage
                42 => 2,   // Crystal Storage
                43 => 2    // Deuterium Tank
            ),
            
            // Attack decision weights
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 40,
                    'weak_defense' => 30,
                    'profitable' => 25,
                    'defended' => 5
                ),
                'min_profit_ratio' => 1.5,
                'max_fleet_percentage' => 80
            )
        ),
        
        'smasher' => array(
            'name' => 'Smasher Fleeter',
            'description' => 'Heavy combat ships, devastating attacks',
            
            'building_weights' => array(
                1 => 28,   // Metal Mine
                2 => 25,   // Crystal Mine
                3 => 22,   // Deuterium Synthesizer
                4 => 25,   // Solar Plant
                12 => 12,  // Fusion Reactor
                14 => 20,  // Robotics Factory
                15 => 15,  // Nanite Factory
                21 => 32,  // Shipyard
                22 => 18,  // Research Lab
                31 => 8,   // Missile Silo
                41 => 4,   // Metal Storage
                42 => 4,   // Crystal Storage
                43 => 5    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 10, 108 => 12, 109 => 30, 110 => 28, 111 => 26, 113 => 15,
                114 => 20, 115 => 22, 117 => 24, 118 => 28, 120 => 8, 121 => 10,
                122 => 12, 123 => 15, 124 => 5
            ),
            
            'ship_weights' => array(
                202 => 10, 203 => 12, 204 => 15, 205 => 20, 206 => 22, 207 => 35,
                208 => 3, 209 => 5, 210 => 8, 211 => 12, 212 => 1, 213 => 25,
                214 => 8, 215 => 30
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 450, 'variance' => 180, 'idle_probability' => 25,
                'action_weights' => array('BUILD' => 25, 'RESEARCH' => 25, 'BUILD_FLEET' => 30, 'ATTACK' => 20)
            ),
            
            // FIXED: Consistent with building_weights
            'building_caps' => array(
                1 => 30,   // Metal Mine
                2 => 25,   // Crystal Mine
                3 => 25,   // Deuterium Synthesizer
                4 => 30,   // Solar Plant
                12 => 12,  // Fusion Reactor
                14 => 12,  // Robotics Factory
                15 => 8,   // Nanite Factory
                21 => 15,  // Shipyard
                22 => 10,  // Research Lab
                31 => 8,   // Missile Silo
                41 => 3,   // Metal Storage
                42 => 3,   // Crystal Storage
                43 => 3    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'defended' => 35, 'high_value' => 30, 'strategic' => 20, 'inactive' => 15
                ),
                'min_profit_ratio' => 2.0, 'max_fleet_percentage' => 90
            )
        ),
        
        'balanced' => array(
            'name' => 'Balanced Fleeter',
            'description' => 'Versatile combat approach',
            
            'building_weights' => array(
                1 => 24,   // Metal Mine
                2 => 22,   // Crystal Mine
                3 => 20,   // Deuterium Synthesizer
                4 => 23,   // Solar Plant
                12 => 10,  // Fusion Reactor
                14 => 18,  // Robotics Factory
                15 => 12,  // Nanite Factory
                21 => 28,  // Shipyard
                22 => 16,  // Research Lab
                31 => 6,   // Missile Silo
                41 => 3,   // Metal Storage
                42 => 3,   // Crystal Storage
                43 => 4    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 12, 108 => 14, 109 => 22, 110 => 20, 111 => 18, 113 => 14,
                114 => 16, 115 => 24, 117 => 22, 118 => 20, 120 => 8, 121 => 8,
                122 => 14, 123 => 10, 124 => 4
            ),
            
            'ship_weights' => array(
                202 => 12, 203 => 10, 204 => 25, 205 => 22, 206 => 20, 207 => 18,
                208 => 4, 209 => 6, 210 => 10, 211 => 8, 212 => 2, 213 => 15,
                214 => 3, 215 => 20
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 360, 'variance' => 150, 'idle_probability' => 20,
                'action_weights' => array('BUILD' => 28, 'RESEARCH' => 22, 'BUILD_FLEET' => 32, 'ATTACK' => 18)
            ),
            
            // FIXED: Consistent with building_weights
            'building_caps' => array(
                1 => 28,   // Metal Mine
                2 => 23,   // Crystal Mine
                3 => 23,   // Deuterium Synthesizer
                4 => 28,   // Solar Plant
                12 => 10,  // Fusion Reactor
                14 => 11,  // Robotics Factory
                15 => 6,   // Nanite Factory
                21 => 13,  // Shipyard
                22 => 9,   // Research Lab
                31 => 6,   // Missile Silo
                41 => 2,   // Metal Storage
                42 => 2,   // Crystal Storage
                43 => 2    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 30, 'profitable' => 25, 'weak_defense' => 25, 'defended' => 20
                ),
                'min_profit_ratio' => 1.8, 'max_fleet_percentage' => 75
            )
        ),
        
        'swarm' => array(
            'name' => 'Swarm Fleeter',
            'description' => 'Large numbers of light ships',
            
            'building_weights' => array(
                1 => 22,   // Metal Mine
                2 => 18,   // Crystal Mine
                3 => 12,   // Deuterium Synthesizer
                4 => 20,   // Solar Plant
                12 => 6,   // Fusion Reactor
                14 => 16,  // Robotics Factory
                15 => 8,   // Nanite Factory
                21 => 35,  // Shipyard
                22 => 12,  // Research Lab
                31 => 3,   // Missile Silo
                41 => 2,   // Metal Storage
                42 => 2,   // Crystal Storage
                43 => 2    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 8, 108 => 10, 109 => 20, 110 => 18, 111 => 16, 113 => 10,
                114 => 12, 115 => 30, 117 => 25, 118 => 15, 120 => 6, 121 => 5,
                122 => 10, 123 => 5, 124 => 2
            ),
            
            'ship_weights' => array(
                202 => 8, 203 => 5, 204 => 45, 205 => 30, 206 => 10, 207 => 5,
                208 => 2, 209 => 3, 210 => 15, 211 => 3, 212 => 1, 213 => 2,
                214 => 1, 215 => 3
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 240, 'variance' => 90, 'idle_probability' => 10,
                'action_weights' => array('BUILD' => 20, 'RESEARCH' => 15, 'BUILD_FLEET' => 50, 'ATTACK' => 15)
            ),
            
            // FIXED: Consistent with building_weights
            'building_caps' => array(
                1 => 22,   // Metal Mine
                2 => 18,   // Crystal Mine
                3 => 15,   // Deuterium Synthesizer
                4 => 22,   // Solar Plant
                12 => 6,   // Fusion Reactor
                14 => 8,   // Robotics Factory
                15 => 4,   // Nanite Factory
                21 => 10,  // Shipyard
                22 => 7,   // Research Lab
                31 => 3,   // Missile Silo
                41 => 1,   // Metal Storage
                42 => 1,   // Crystal Storage
                43 => 1    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 50, 'undefended' => 30, 'quick_raids' => 15, 'defended' => 5
                ),
                'min_profit_ratio' => 1.2, 'max_fleet_percentage' => 60
            )
        )
    )
),
    
    // MINER PERSONALITY - Resource-focused weighted decisions
    'miner' => array(
    'name' => 'Miner',
    'description' => 'Resource-focused bot with production-oriented decisions',
    'default_subtype' => 'balanced',
    
    'subtypes' => array(
        'pure' => array(
            'name' => 'Pure Miner',
            'description' => 'Mining optimization, heavy defense',
            
            'building_weights' => array(
                1 => 35,   // Metal Mine - highest
                2 => 30,   // Crystal Mine - very high
                3 => 30,   // Deuterium Synthesizer - very high
                4 => 32,   // Solar Plant - very high
                12 => 20,  // Fusion Reactor - high
                14 => 25,  // Robotics Factory - high for building speed
                15 => 18,  // Nanite Factory - moderate-high
                21 => 5,   // Shipyard - very low
                22 => 12,  // Research Lab - moderate
                31 => 22,  // Missile Silo - high for defense
                41 => 15,  // Metal Storage - moderate-high
                42 => 15,  // Crystal Storage - moderate-high
                43 => 15   // Deuterium Tank - moderate-high
            ),
            
            'research_weights' => array(
                106 => 15, 108 => 18, 109 => 5, 110 => 8, 111 => 10, 113 => 30,
                114 => 8, 115 => 6, 117 => 5, 118 => 4, 120 => 12, 121 => 15,
                122 => 25, 123 => 20, 124 => 22
            ),
            
            'ship_weights' => array(
                202 => 25, 203 => 30, 204 => 5, 205 => 3, 206 => 2, 207 => 1,
                208 => 8, 209 => 15, 210 => 20, 211 => 1, 212 => 10, 213 => 1,
                214 => 1, 215 => 1
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 900, 'variance' => 300, 'idle_probability' => 40,
                'action_weights' => array('BUILD' => 50, 'RESEARCH' => 30, 'BUILD_FLEET' => 15, 'ATTACK' => 5)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 40,   // Metal Mine - highest cap for pure mining
                2 => 35,   // Crystal Mine
                3 => 35,   // Deuterium Synthesizer
                4 => 40,   // Solar Plant
                12 => 15,  // Fusion Reactor
                14 => 18,  // Robotics Factory
                15 => 12,  // Nanite Factory
                21 => 3,   // Shipyard - very low cap
                22 => 10,  // Research Lab
                31 => 12,  // Missile Silo
                41 => 10,  // Metal Storage
                42 => 10,  // Crystal Storage
                43 => 10   // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array('never' => 100),
                'min_profit_ratio' => 999, 'max_fleet_percentage' => 0
            )
        ),
        
        'balanced' => array(
            'name' => 'Balanced Miner',
            'description' => 'Good production with some fleet capability',
            
            'building_weights' => array(
                1 => 30,   // Metal Mine
                2 => 25,   // Crystal Mine
                3 => 25,   // Deuterium Synthesizer
                4 => 28,   // Solar Plant
                12 => 15,  // Fusion Reactor
                14 => 20,  // Robotics Factory
                15 => 12,  // Nanite Factory
                21 => 12,  // Shipyard
                22 => 15,  // Research Lab
                31 => 10,  // Missile Silo
                41 => 8,   // Metal Storage
                42 => 8,   // Crystal Storage
                43 => 8    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 12, 108 => 15, 109 => 12, 110 => 15, 111 => 14, 113 => 25,
                114 => 10, 115 => 10, 117 => 8, 118 => 6, 120 => 10, 121 => 12,
                122 => 20, 123 => 15, 124 => 18
            ),
            
            'ship_weights' => array(
                202 => 20, 203 => 25, 204 => 15, 205 => 12, 206 => 8, 207 => 5,
                208 => 6, 209 => 12, 210 => 18, 211 => 3, 212 => 8, 213 => 2,
                214 => 1, 215 => 3
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 600, 'variance' => 240, 'idle_probability' => 30,
                'action_weights' => array('BUILD' => 40, 'RESEARCH' => 25, 'BUILD_FLEET' => 20, 'ATTACK' => 15)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 35,   // Metal Mine
                2 => 30,   // Crystal Mine
                3 => 30,   // Deuterium Synthesizer
                4 => 35,   // Solar Plant
                12 => 12,  // Fusion Reactor
                14 => 15,  // Robotics Factory
                15 => 8,   // Nanite Factory
                21 => 8,   // Shipyard
                22 => 12,  // Research Lab
                31 => 8,   // Missile Silo
                41 => 6,   // Metal Storage
                42 => 6,   // Crystal Storage
                43 => 6    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 60, 'undefended' => 30, 'profitable' => 10
                ),
                'min_profit_ratio' => 2.5, 'max_fleet_percentage' => 40
            )
        )
    )
),
    
    // TURTLE PERSONALITY - Defense and research focused
    'turtle' => array(
    'name' => 'Turtle',
    'description' => 'Defensive bot focusing on research and fortification',
    'default_subtype' => 'research',
    
    'subtypes' => array(
        'research' => array(
            'name' => 'Research Turtle',
            'description' => 'Heavy defense, research focus, predictable patterns',
            
            'building_weights' => array(
                1 => 20,   // Metal Mine - moderate
                2 => 18,   // Crystal Mine - moderate
                3 => 18,   // Deuterium Synthesizer - moderate
                4 => 22,   // Solar Plant - moderate-high
                12 => 8,   // Fusion Reactor - low
                14 => 15,  // Robotics Factory - moderate
                15 => 10,  // Nanite Factory - low-moderate
                21 => 5,   // Shipyard - very low
                22 => 35,  // Research Lab - highest priority
                31 => 25,  // Missile Silo - high for defense
                41 => 6,   // Metal Storage - low
                42 => 6,   // Crystal Storage - low
                43 => 6    // Deuterium Tank - low
            ),
            
            'research_weights' => array(
                106 => 25, 108 => 30, 109 => 15, 110 => 18, 111 => 16, 113 => 20,
                114 => 22, 115 => 8, 117 => 6, 118 => 10, 120 => 18, 121 => 20,
                122 => 28, 123 => 25, 124 => 35
            ),
            
            'ship_weights' => array(
                202 => 15, 203 => 20, 204 => 5, 205 => 3, 206 => 2, 207 => 1,
                208 => 10, 209 => 8, 210 => 25, 211 => 1, 212 => 12, 213 => 1,
                214 => 1, 215 => 1
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 720, 'variance' => 60, 'idle_probability' => 35,
                'action_weights' => array('BUILD' => 30, 'RESEARCH' => 60, 'BUILD_FLEET' => 8, 'ATTACK' => 2)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 25,   // Metal Mine
                2 => 20,   // Crystal Mine
                3 => 20,   // Deuterium Synthesizer
                4 => 25,   // Solar Plant
                12 => 6,   // Fusion Reactor
                14 => 10,  // Robotics Factory
                15 => 5,   // Nanite Factory
                21 => 3,   // Shipyard - very low cap
                22 => 15,  // Research Lab - high cap
                31 => 15,  // Missile Silo - high for defense
                41 => 4,   // Metal Storage
                42 => 4,   // Crystal Storage
                43 => 4    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array('never' => 100),
                'min_profit_ratio' => 999, 'max_fleet_percentage' => 0
            )
        ),
        
        'fortress' => array(
            'name' => 'Fortress Turtle',
            'description' => 'Maximum defense, minimal activity',
            
            'building_weights' => array(
                1 => 18,   // Metal Mine
                2 => 15,   // Crystal Mine
                3 => 15,   // Deuterium Synthesizer
                4 => 18,   // Solar Plant
                12 => 5,   // Fusion Reactor
                14 => 12,  // Robotics Factory
                15 => 6,   // Nanite Factory
                21 => 2,   // Shipyard - very low
                22 => 15,  // Research Lab
                31 => 40,  // Missile Silo - highest priority for defense
                41 => 4,   // Metal Storage
                42 => 4,   // Crystal Storage
                43 => 4    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 20, 108 => 25, 109 => 20, 110 => 25, 111 => 22, 113 => 18,
                114 => 15, 115 => 5, 117 => 3, 118 => 5, 120 => 22, 121 => 25,
                122 => 20, 123 => 18, 124 => 15
            ),
            
            'ship_weights' => array(
                202 => 8, 203 => 10, 204 => 2, 205 => 1, 206 => 1, 207 => 1,
                208 => 5, 209 => 5, 210 => 30, 211 => 1, 212 => 8, 213 => 1,
                214 => 1, 215 => 1
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 1200, 'variance' => 180, 'idle_probability' => 50,
                'action_weights' => array('BUILD' => 40, 'RESEARCH' => 50, 'BUILD_FLEET' => 8, 'ATTACK' => 2)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 20,   // Metal Mine
                2 => 15,   // Crystal Mine
                3 => 15,   // Deuterium Synthesizer
                4 => 20,   // Solar Plant
                12 => 3,   // Fusion Reactor
                14 => 8,   // Robotics Factory
                15 => 3,   // Nanite Factory
                21 => 1,   // Shipyard - minimal cap
                22 => 8,   // Research Lab
                31 => 20,  // Missile Silo - very high cap for defense
                41 => 2,   // Metal Storage
                42 => 2,   // Crystal Storage
                43 => 2    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array('never' => 100),
                'min_profit_ratio' => 999, 'max_fleet_percentage' => 0
            )
        )
    )
),
    
    // TRADER PERSONALITY - Economy focused
    'trader' => array(
    'name' => 'Trader',
    'description' => 'Economy-focused bot specializing in resource management',
    'default_subtype' => 'merchant',
    
    'subtypes' => array(
        'merchant' => array(
            'name' => 'Merchant Trader',
            'description' => 'Resource trading, diplomatic, moderate activity',
            
            'building_weights' => array(
                1 => 25,   // Metal Mine
                2 => 22,   // Crystal Mine
                3 => 22,   // Deuterium Synthesizer
                4 => 25,   // Solar Plant
                12 => 12,  // Fusion Reactor
                14 => 16,  // Robotics Factory
                15 => 10,  // Nanite Factory
                21 => 12,  // Shipyard
                22 => 14,  // Research Lab
                31 => 5,   // Missile Silo
                41 => 18,  // Metal Storage - high for trading
                42 => 18,  // Crystal Storage - high for trading
                43 => 18   // Deuterium Tank - high for trading
            ),
            
            'research_weights' => array(
                106 => 18, 108 => 20, 109 => 8, 110 => 10, 111 => 8, 113 => 25,
                114 => 15, 115 => 15, 117 => 12, 118 => 12, 120 => 10, 121 => 8,
                122 => 22, 123 => 12, 124 => 18
            ),
            
            'ship_weights' => array(
                202 => 30, 203 => 35, 204 => 10, 205 => 8, 206 => 5, 207 => 3,
                208 => 8, 209 => 12, 210 => 15, 211 => 2, 212 => 8, 213 => 2,
                214 => 1, 215 => 2
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 540, 'variance' => 200, 'idle_probability' => 28,
                'action_weights' => array('BUILD' => 35, 'RESEARCH' => 20, 'BUILD_FLEET' => 30, 'ATTACK' => 15)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 28,   // Metal Mine
                2 => 23,   // Crystal Mine
                3 => 23,   // Deuterium Synthesizer
                4 => 28,   // Solar Plant
                12 => 8,   // Fusion Reactor
                14 => 11,  // Robotics Factory
                15 => 6,   // Nanite Factory
                21 => 8,   // Shipyard
                22 => 8,   // Research Lab
                31 => 3,   // Missile Silo
                41 => 10,  // Metal Storage - high cap for trading
                42 => 10,  // Crystal Storage - high cap for trading
                43 => 10   // Deuterium Tank - high cap for trading
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'profitable' => 50, 'inactive' => 40, 'undefended' => 10
                ),
                'min_profit_ratio' => 2.8, 'max_fleet_percentage' => 35
            )
        ),
        
        'industrialist' => array(
            'name' => 'Industrialist Trader',
            'description' => 'Heavy production focus, massive storage',
            
            'building_weights' => array(
                1 => 30,   // Metal Mine - highest
                2 => 28,   // Crystal Mine - very high
                3 => 25,   // Deuterium Synthesizer - high
                4 => 28,   // Solar Plant - very high
                12 => 18,  // Fusion Reactor - moderate-high
                14 => 22,  // Robotics Factory - high
                15 => 15,  // Nanite Factory - moderate-high
                21 => 8,   // Shipyard - low
                22 => 16,  // Research Lab - moderate-high
                31 => 3,   // Missile Silo - very low
                41 => 25,  // Metal Storage - very high
                42 => 25,  // Crystal Storage - very high
                43 => 25   // Deuterium Tank - very high
            ),
            
            'research_weights' => array(
                106 => 15, 108 => 18, 109 => 5, 110 => 8, 111 => 6, 113 => 30,
                114 => 12, 115 => 10, 117 => 8, 118 => 6, 120 => 8, 121 => 10,
                122 => 25, 123 => 18, 124 => 22
            ),
            
            'ship_weights' => array(
                202 => 35, 203 => 40, 204 => 5, 205 => 3, 206 => 2, 207 => 1,
                208 => 6, 209 => 15, 210 => 12, 211 => 1, 212 => 10, 213 => 1,
                214 => 1, 215 => 1
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 720, 'variance' => 180, 'idle_probability' => 35,
                'action_weights' => array('BUILD' => 45, 'RESEARCH' => 25, 'BUILD_FLEET' => 25, 'ATTACK' => 5)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 35,   // Metal Mine
                2 => 32,   // Crystal Mine
                3 => 28,   // Deuterium Synthesizer
                4 => 35,   // Solar Plant
                12 => 15,  // Fusion Reactor
                14 => 18,  // Robotics Factory
                15 => 10,  // Nanite Factory
                21 => 5,   // Shipyard
                22 => 12,  // Research Lab
                31 => 2,   // Missile Silo
                41 => 15,  // Metal Storage - very high cap
                42 => 15,  // Crystal Storage - very high cap
                43 => 15   // Deuterium Tank - very high cap
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 80, 'undefended' => 20
                ),
                'min_profit_ratio' => 3.5, 'max_fleet_percentage' => 25
            )
        )
    )
),
    
    // RAIDER PERSONALITY - Opportunistic raids
    'raider' => array(
    'name' => 'Raider',
    'description' => 'Opportunistic bot specializing in raids and resource acquisition',
    'default_subtype' => 'opportunist',
    
    'subtypes' => array(
        'opportunist' => array(
            'name' => 'Opportunist Raider',
            'description' => 'Cargo ships, targets inactive planets',
            
            'building_weights' => array(
                1 => 22,   // Metal Mine
                2 => 18,   // Crystal Mine
                3 => 18,   // Deuterium Synthesizer
                4 => 22,   // Solar Plant
                12 => 8,   // Fusion Reactor
                14 => 15,  // Robotics Factory
                15 => 8,   // Nanite Factory
                21 => 20,  // Shipyard - high for cargo ships
                22 => 12,  // Research Lab
                31 => 6,   // Missile Silo
                41 => 5,   // Metal Storage
                42 => 5,   // Crystal Storage
                43 => 5    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 15, 108 => 12, 109 => 15, 110 => 12, 111 => 10, 113 => 12,
                114 => 15, 115 => 25, 117 => 22, 118 => 20, 120 => 8, 121 => 6,
                122 => 12, 123 => 8, 124 => 5
            ),
            
            'ship_weights' => array(
                202 => 25, 203 => 30, 204 => 18, 205 => 15, 206 => 8, 207 => 5,
                208 => 3, 209 => 8, 210 => 12, 211 => 3, 212 => 2, 213 => 3,
                214 => 1, 215 => 5
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 420, 'variance' => 160, 'idle_probability' => 22,
                'action_weights' => array('BUILD' => 25, 'RESEARCH' => 15, 'BUILD_FLEET' => 35, 'ATTACK' => 25)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 26,   // Metal Mine
                2 => 21,   // Crystal Mine
                3 => 21,   // Deuterium Synthesizer
                4 => 26,   // Solar Plant
                12 => 6,   // Fusion Reactor
                14 => 10,  // Robotics Factory
                15 => 5,   // Nanite Factory
                21 => 10,  // Shipyard
                22 => 8,   // Research Lab
                31 => 4,   // Missile Silo
                41 => 3,   // Metal Storage
                42 => 3,   // Crystal Storage
                43 => 3    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'inactive' => 40, 'undefended' => 30, 'profitable' => 25, 'defended' => 5
                ),
                'min_profit_ratio' => 1.8, 'max_fleet_percentage' => 70
            )
        ),
        
        'pirate' => array(
            'name' => 'Pirate Raider',
            'description' => 'Aggressive raiding, high activity',
            
            'building_weights' => array(
                1 => 20,   // Metal Mine
                2 => 16,   // Crystal Mine
                3 => 14,   // Deuterium Synthesizer
                4 => 20,   // Solar Plant
                12 => 6,   // Fusion Reactor
                14 => 12,  // Robotics Factory
                15 => 6,   // Nanite Factory
                21 => 25,  // Shipyard - highest for aggressive fleet building
                22 => 10,  // Research Lab
                31 => 4,   // Missile Silo
                41 => 3,   // Metal Storage
                42 => 3,   // Crystal Storage
                43 => 3    // Deuterium Tank
            ),
            
            'research_weights' => array(
                106 => 12, 108 => 10, 109 => 20, 110 => 18, 111 => 15, 113 => 10,
                114 => 12, 115 => 25, 117 => 22, 118 => 18, 120 => 8, 121 => 6,
                122 => 10, 123 => 8, 124 => 4
            ),
            
            'ship_weights' => array(
                202 => 20, 203 => 25, 204 => 25, 205 => 20, 206 => 15, 207 => 8,
                208 => 2, 209 => 5, 210 => 8, 211 => 5, 212 => 1, 213 => 8,
                214 => 2, 215 => 10
            ),
            
            'activity_pattern' => array(
                'base_frequency' => 300, 'variance' => 120, 'idle_probability' => 18,
                'action_weights' => array('BUILD' => 20, 'RESEARCH' => 10, 'BUILD_FLEET' => 35, 'ATTACK' => 35)
            ),
            
            // FIXED: Consistent with building_weights - all IDs present
            'building_caps' => array(
                1 => 24,   // Metal Mine
                2 => 19,   // Crystal Mine
                3 => 16,   // Deuterium Synthesizer
                4 => 24,   // Solar Plant
                12 => 4,   // Fusion Reactor
                14 => 9,   // Robotics Factory
                15 => 4,   // Nanite Factory
                21 => 12,  // Shipyard - high cap for aggressive building
                22 => 7,   // Research Lab
                31 => 2,   // Missile Silo
                41 => 2,   // Metal Storage
                42 => 2,   // Crystal Storage
                43 => 2    // Deuterium Tank
            ),
            
            'attack_preferences' => array(
                'target_type_weights' => array(
                    'any' => 30, 'profitable' => 25, 'weak_defense' => 25, 'inactive' => 20
                ),
                'min_profit_ratio' => 1.4, 'max_fleet_percentage' => 85
            )
        )
    )
)

/**
 * Get personality configuration for weighted decision making
 * 
 * @param string $personality The personality name
 * @param string $subtype The subtype name (optional)
 * @return array|null Configuration array or null if not found
 */
function GetPersonalityConfig($personality, $subtype = null) {
    global $PERSONALITIES;
    
    if (!isset($PERSONALITIES[$personality])) {
        Debug("GetPersonalityConfig: Unknown personality '$personality'");
        return null;
    }
    
    $personality_config = $PERSONALITIES[$personality];
    
    if ($subtype === null) {
        $subtype = $personality_config['default_subtype'];
    }
    
    if (!isset($personality_config['subtypes'][$subtype])) {
        Debug("GetPersonalityConfig: Unknown subtype '$subtype' for personality '$personality', using default");
        $subtype = $personality_config['default_subtype'];
        if (!isset($personality_config['subtypes'][$subtype])) {
            Debug("GetPersonalityConfig: Default subtype also not found");
            return null;
        }
    }
    
    $config = array_merge(
        array(
            'personality' => $personality,
            'subtype' => $subtype,
            'name' => $personality_config['name'],
            'description' => $personality_config['description']
        ),
        $personality_config['subtypes'][$subtype]
    );
    
    return $config;
}

/**
 * Make weighted decision for building selection
 * Uses personality weights to dynamically choose what to build
 * 
 * @param array $config Personality configuration
 * @return int|false Building ID to build or false if none available
 */
function GetWeightedBuildingChoice($config){
    if (empty($config['building_weights'])) {
        Debug("GetWeightedBuildingChoice: No building weights in config");
        return false;
    }
    
    $available_buildings = array();
    $total_weight = 0;
    
    // Check each building and calculate available weight
    foreach ($config['building_weights'] as $building_id => $weight) {
        $current_level = BotGetBuild($building_id);
        $cap = $config['building_caps'][$building_id] ?? 999;
        
        // Skip if at or above cap
        if ($current_level >= $cap) {
            continue;
        }
        
        // Check if building is available to build
        if (!BotCanBuild($building_id)) {
            continue;
        }
        
        if ($cap == 0) {
            // If cap is 0, building should never be built
            $cap_factor = 0;
        } else {
            // Calculate diminishing returns based on current level vs cap
            $cap_factor = 1.0 - ($current_level / $cap);
        }
        
        // Apply cap factor to weight (buildings closer to cap get lower priority)
        $adjusted_weight = $weight * max(0.1, $cap_factor); // Minimum 10% weight
        
        if ($adjusted_weight > 0) {
            $available_buildings[$building_id] = $adjusted_weight;
            $total_weight += $adjusted_weight;
        }
    }
    
    if ($total_weight == 0) {
        Debug("GetWeightedBuildingChoice: No buildable buildings available");
        return false;
    }
    
    // Weighted random selection
    $random = rand(1, $total_weight * 100) / 100;
    $current_weight = 0;
    
    foreach ($available_buildings as $building_id => $weight) {
        $current_weight += $weight;
        if ($random <= $current_weight) {
            Debug("GetWeightedBuildingChoice: Selected building $building_id with weight $weight");
            return $building_id;
        }
    }
    
    // Fallback to first available building
    $building_id = array_keys($available_buildings)[0];
    Debug("GetWeightedBuildingChoice: Fallback to building $building_id");
    return $building_id;
}

?>