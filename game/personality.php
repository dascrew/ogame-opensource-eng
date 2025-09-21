<?php
require_once 'id.php';

const TARGET_TYPES = ['inactive','weak_defense','undefended','profitable','high_value','strategic','defended','quick_raids','never'];

$SCHEMA_DEFAULTS = [
  'attack_preferences' => [
    'target_type' => [],
    'min_profit_ratio' => 0.0,
    'max_fleet_percentage' => 0
  ],
  'weights' => [
    'building_priority' => [],   // GID => float
    'research_priority' => [],   // GID => float
    'ship_ratio' => []           // GID => float (normalized by executor)
  ],
  'constraints' => [
    'min_levels' => [            // e.g., GID => int
      /* GID_R_ESPIONAGE => 2 */
    ],
    'max_levels' => []
  ]
];

$PERSONALITIES = [
  'fleeter' => [
    'name' => 'Fleeter',
    'description' => 'Combat-focused bot with dynamic fleet-oriented decisions',
    'default_subtype' => 'balanced',
    'building_caps' => [
      GID_B_METAL_MINE => 30, GID_B_CRYS_MINE => 25, GID_B_DEUT_SYNTH => 25,
      GID_B_SOLAR => 30, GID_B_FUSION => 12, GID_B_ROBOTS => 12, GID_B_NANITES => 8,
      GID_B_SHIPYARD => 15, GID_B_RES_LAB => 10, GID_B_MISS_SILO => 8,
      GID_B_METAL_STOR => 3, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 3
    ],
    'defaults' => [
      'weights' => [
        'research_priority' => [
          GID_R_COMPUTER => 1.0, GID_R_ESPIONAGE => 0.6, GID_R_WEAPON => 0.9,
          GID_R_SHIELD => 0.8, GID_R_ARMOUR => 0.7
        ]
      ]
    ],
    'subtypes' => [
      'speed' => [
        'name' => 'Speed Fleeter',
        'description' => 'Fast ships, hit-and-run tactics, frequent activity',
        'weights' => [
          'ship_ratio' => [GID_F_LF => 0.5, GID_F_CRUISER => 0.3, GID_F_BATTLECRUISER => 0.15, GID_F_SC => 0.05]
        ],
        'attack_preferences' => [
          'target_type' => ['inactive','weak_defense','profitable'],
          'min_profit_ratio' => 1.5,
          'max_fleet_percentage' => 80
        ]
      ],
      'smasher' => [
        'name' => 'Smasher Fleeter',
        'description' => 'Heavy combat ships, devastating attacks',
        'weights' => [
          'ship_ratio' => [GID_F_BATTLESHIP => 0.5, GID_F_DESTRO => 0.2, GID_F_BOMBER => 0.15, GID_F_HF => 0.1, GID_F_LC => 0.05]
        ],
        'attack_preferences' => [
          'target_type' => ['defended','high_value','strategic','inactive'],
          'min_profit_ratio' => 2.0,
          'max_fleet_percentage' => 90
        ]
      ],
      'balanced' => [
        'name' => 'Balanced Fleeter',
        'description' => 'Versatile combat approach',
        'weights' => [
          'ship_ratio' => [GID_F_LF => 0.35, GID_F_CRUISER => 0.25, GID_F_BATTLESHIP => 0.15, GID_F_BATTLECRUISER => 0.1, GID_F_HF => 0.05, GID_F_LC => 0.05, GID_F_SC => 0.05]
        ],
        'attack_preferences' => [
          'target_type' => ['inactive','profitable','weak_defense','defended'],
          'min_profit_ratio' => 1.8,
          'max_fleet_percentage' => 75
        ]
      ],
      'swarm' => [
        'name' => 'Swarm Fleeter',
        'description' => 'Large numbers of light ships',
        'weights' => [
          'ship_ratio' => [GID_F_LF => 0.8, GID_F_SC => 0.2]
        ],
        'attack_preferences' => [
          'target_type' => ['inactive','undefended','quick_raids','defended'],
          'min_profit_ratio' => 1.2,
          'max_fleet_percentage' => 60
        ]
      ]
    ]
  ],
  'miner' => [
    'name' => 'Miner',
    'description' => 'Resource-focused bot with production-oriented decisions',
    'default_subtype' => 'balanced',
    'building_caps' => [
        GID_B_METAL_MINE => 35,
        GID_B_CRYS_MINE  => 30,
        GID_B_DEUT_SYNTH => 30,
        GID_B_SOLAR      => 35,
        GID_B_FUSION     => 15,
        GID_B_ROBOTS     => 15,
        GID_B_NANITES    => 6,
        GID_B_SHIPYARD   => 8,
        GID_B_RES_LAB    => 12,
        GID_B_MISS_SILO  => 10,
        GID_B_METAL_STOR => 6,
        GID_B_CRYS_STOR  => 6,
        GID_B_DEUT_STOR  => 6,
    ],
    'defaults' => [
        'weights' => [
            'building_priority' => [
                GID_B_METAL_MINE  => 1.0,
                GID_B_CRYS_MINE   => 0.95,
                GID_B_DEUT_SYNTH  => 0.9,
                GID_B_SOLAR       => 0.7,
                GID_B_ROBOTS      => 0.5,
                GID_B_RES_LAB     => 0.3,
                GID_B_METAL_STOR  => 0.2,
                GID_B_CRYS_STOR   => 0.18,
                GID_B_DEUT_STOR   => 0.18,
            ],
            'research_priority' => [
                GID_R_ENERGY    => 1.0,
                GID_R_ARMOUR    => 0.2,
                GID_R_WEAPON    => 0.15,
                GID_R_SHIELD    => 0.12,
                GID_R_ESPIONAGE => 0.1,
            ],
            'ship_ratio' => [
                GID_F_SC => 0.8,
                GID_F_LC => 0.2,
            ],
        ],
        'constraints' => [
            'min_levels' => [GID_B_METAL_MINE => 15, GID_B_CRYS_MINE => 10, GID_B_DEUT_SYNTH => 8],
            'max_levels' => [],
        ],
        'attack_preferences' => [
            'target_type' => ['never'],
            'min_profit_ratio' => 999,
            'max_fleet_percentage' => 0,
        ],
    ],
    'subtypes' => [
        'pure' => [
            'name' => 'Pure Miner',
            'description' => 'Mining optimisation, heavy defense, minimal aggression.',
            'weights' => [
                'building_priority' => [
                    GID_B_METAL_MINE => 1.2,
                    GID_B_CRYS_MINE => 1.05,
                    GID_B_DEUT_SYNTH => 1.0,
                    GID_B_SOLAR => 0.85,
                    GID_B_METAL_STOR => 0.6,
                    GID_B_CRYS_STOR => 0.6,
                    GID_B_DEUT_STOR => 0.6,
                ],
                'ship_ratio' => [
                    GID_F_SC => 0.7,
                    GID_F_LC => 0.3,
                ],
            ],
            'constraints' => [
                'min_levels' => [GID_B_METAL_MINE => 20, GID_B_CRYS_MINE => 14, GID_B_DEUT_SYNTH => 12],
                'max_levels' => [],
            ],
            'attack_preferences' => [
                'target_type' => ['never'],
                'min_profit_ratio' => 999,
                'max_fleet_percentage' => 0,
            ],
        ],
        'balanced' => [
            'name' => 'Balanced Miner',
            'description' => 'Good production with some fleet capability and flexibility.',
            'weights' => [
                'ship_ratio' => [
                    GID_F_SC => 0.5,
                    GID_F_LC => 0.3,
                    GID_F_LF => 0.1,
                    GID_F_CRUISER => 0.05,
                    GID_F_BATTLESHIP => 0.05,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive', 'undefended', 'profitable'],
                'min_profit_ratio' => 2.5,
                'max_fleet_percentage' => 15,
            ],
        ],
    ],
  ],
  'turtle' => [
    'name' => 'Turtle',
    'description' => 'Defensive bot focusing on research and fortification.',
    'default_subtype' => 'research',
    'building_caps' => [
        GID_B_METAL_MINE => 26,
        GID_B_CRYS_MINE  => 21,
        GID_B_DEUT_SYNTH => 19,
        GID_B_SOLAR      => 26,
        GID_B_FUSION     => 6,
        GID_B_ROBOTS     => 10,
        GID_B_NANITES    => 7,
        GID_B_SHIPYARD   => 7,
        GID_B_RES_LAB    => 13,
        GID_B_MISS_SILO  => 15,
        GID_B_METAL_STOR => 5,
        GID_B_CRYS_STOR  => 5,
        GID_B_DEUT_STOR  => 5,
    ],
    'defaults' => [
        'weights' => [
            'building_priority' => [
                GID_B_MISS_SILO    => 1.4,
                GID_B_RES_LAB      => 1.0,
                GID_B_NANITES      => 0.9,
                GID_B_METAL_MINE   => 0.4,
                GID_B_CRYS_MINE    => 0.38,
                GID_B_DEUT_SYNTH   => 0.36,
                GID_B_SOLAR        => 0.3,
                GID_B_ROBOTS       => 0.25,
            ],
            'research_priority' => [
                GID_R_SHIELD   => 1.2,
                GID_R_ARMOUR   => 1.1,
                GID_R_WEAPON   => 1.0,
                GID_R_ENERGY   => 0.7,
                GID_R_ESPIONAGE => 0.2,
            ],
            'ship_ratio' => [
                GID_F_LC       => 0.7,
                GID_F_RECYCLER => 0.3,
            ],
        ],
        'constraints' => [
            'min_levels' => [GID_B_MISS_SILO => 8, GID_B_RES_LAB => 8],
            'max_levels' => [],
        ],
        'attack_preferences' => [
            'target_type' => ['never'],
            'min_profit_ratio' => 999,
            'max_fleet_percentage' => 0,
        ],
    ],
    'subtypes' => [
        'research' => [
            'name' => 'Research Turtle',
            'description' => 'Heavy defense, heavy research, slow but steady progress.',
            'weights' => [
                'building_priority' => [
                    GID_B_RES_LAB  => 1.3,
                    GID_B_MISS_SILO=> 1.0,
                ],
                'research_priority' => [
                    GID_R_SHIELD    => 1.5,
                    GID_R_ARMOUR    => 1.3,
                    GID_R_WEAPON    => 1.2,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['never'],
                'min_profit_ratio' => 999,
                'max_fleet_percentage' => 0,
            ],
        ],
        'fortress' => [
            'name' => 'Fortress Turtle',
            'description' => 'Maximum defense, minimal activity, safe from most attackers.',
            'weights' => [
                'building_priority' => [
                    GID_B_MISS_SILO => 2.0,
                    GID_B_NANITES   => 1.1,
                ],
                'ship_ratio' => [
                    GID_F_RECYCLER  => 1.0,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['never'],
                'min_profit_ratio' => 999,
                'max_fleet_percentage' => 0,
            ],
        ],
    ],
  ],
  'trader' => [
    'name' => 'Trader',
    'description' => 'Economy-focused bot specializing in resource management and trading.',
    'default_subtype' => 'merchant',
    'building_caps' => [
        GID_B_METAL_MINE => 28,
        GID_B_CRYS_MINE  => 24,
        GID_B_DEUT_SYNTH => 23,
        GID_B_SOLAR      => 25,
        GID_B_FUSION     => 8,
        GID_B_ROBOTS     => 11,
        GID_B_NANITES    => 6,
        GID_B_SHIPYARD   => 10,
        GID_B_RES_LAB    => 10,
        GID_B_MISS_SILO  => 5,
        GID_B_METAL_STOR => 7,
        GID_B_CRYS_STOR  => 7,
        GID_B_DEUT_STOR  => 7,
    ],
    'defaults' => [
        'weights' => [
            'building_priority' => [
                GID_B_METAL_MINE  => 1.1,
                GID_B_CRYS_MINE   => 1.0,
                GID_B_DEUT_SYNTH  => 0.9,
                GID_B_SOLAR       => 0.65,
                GID_B_ROBOTS      => 0.48,
                GID_B_RES_LAB     => 0.33,
                GID_B_METAL_STOR  => 0.5,
                GID_B_CRYS_STOR   => 0.45,
                GID_B_DEUT_STOR   => 0.45,
            ],
            'research_priority' => [
                GID_R_ESPIONAGE      => 0.7,
                GID_R_COMBUST_DRIVE  => 1.1,
                GID_R_IMPULSE_DRIVE  => 1.0,
                GID_R_HYPER_DRIVE    => 0.95,
                GID_R_COMPUTER       => 0.85,
            ],
            'ship_ratio' => [
                GID_F_SC       => 0.5,
                GID_F_LC       => 0.4,
                GID_F_RECYCLER => 0.1,
            ],
        ],
        'constraints' => [
            'min_levels' => [GID_B_METAL_MINE => 12, GID_B_CRYS_MINE => 10, GID_B_DEUT_SYNTH => 8],
            'max_levels' => [],
        ],
        'attack_preferences' => [
            'target_type' => ['inactive'],
            'min_profit_ratio' => 999,
            'max_fleet_percentage' => 0,
        ],
    ],
    'subtypes' => [
        'merchant' => [
            'name' => 'Merchant Trader',
            'description' => 'Trades resources, diplomatic, focuses on transport and market flexibility.',
            'weights' => [
                'ship_ratio' => [
                    GID_F_SC       => 0.55,
                    GID_F_LC       => 0.35,
                    GID_F_RECYCLER => 0.1,
                ],
                'building_priority' => [
                    GID_B_METAL_MINE => 1.2,
                    GID_B_CRYS_MINE  => 1.12,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive'],
                'min_profit_ratio' => 999,
                'max_fleet_percentage' => 0,
            ],
        ],
        'industrialist' => [
            'name' => 'Industrialist Trader',
            'description' => 'Heavy production focus, invests in massive storages for trading.',
            'weights' => [
                'building_priority' => [
                    GID_B_METAL_STOR => 1.15,
                    GID_B_CRYS_STOR  => 1.12,
                    GID_B_DEUT_STOR  => 1.12,
                ],
                'ship_ratio' => [
                    GID_F_SC => 0.45,
                    GID_F_LC => 0.50,
                    GID_F_RECYCLER => 0.05,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive'],
                'min_profit_ratio' => 3.5,
                'max_fleet_percentage' => 0,
            ],
        ],
        'hoarder' => [
            'name' => 'Hoarder Trader',
            'description' => 'Maximum resource accumulation, extreme storage and safety.',
            'weights' => [
                'building_priority' => [
                    GID_B_METAL_STOR => 1.4,
                    GID_B_CRYS_STOR  => 1.3,
                    GID_B_DEUT_STOR  => 1.25,
                ],
                'ship_ratio' => [
                    GID_F_LC => 0.8,
                    GID_F_RECYCLER => 0.2,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive'],
                'min_profit_ratio' => 3.5,
                'max_fleet_percentage' => 0,
            ],
        ],
    ],
  ],
  'raider' => [
    'name' => 'Raider',
    'description' => 'Opportunistic bot specializing in raids and resource acquisition.',
    'default_subtype' => 'opportunist',
    'building_caps' => [
        GID_B_METAL_MINE => 26,
        GID_B_CRYS_MINE  => 21,
        GID_B_DEUT_SYNTH => 21,
        GID_B_SOLAR      => 26,
        GID_B_FUSION     => 6,
        GID_B_ROBOTS     => 10,
        GID_B_NANITES    => 5,
        GID_B_SHIPYARD   => 10,
        GID_B_RES_LAB    => 8,
        GID_B_MISS_SILO  => 4,
        GID_B_METAL_STOR => 3,
        GID_B_CRYS_STOR  => 3,
        GID_B_DEUT_STOR  => 3,
    ],
    'defaults' => [
        'weights' => [
            'building_priority' => [
                GID_B_METAL_MINE  => 0.8,
                GID_B_CRYS_MINE   => 0.7,
                GID_B_DEUT_SYNTH  => 0.65,
                GID_B_SOLAR       => 0.6,
                GID_B_FUSION      => 0.3,
                GID_B_ROBOTS      => 0.4,
                GID_B_MISS_SILO   => 0.35,
                GID_B_METAL_STOR  => 0.3,
                GID_B_CRYS_STOR   => 0.3,
                GID_B_DEUT_STOR   => 0.3,
            ],
            'research_priority' => [
                GID_R_ESPIONAGE     => 1.1,
                GID_R_COMPUTER      => 1.0,
                GID_R_WEAPON        => 0.9,
                GID_R_SHIELD        => 0.8,
                GID_R_ARMOUR        => 0.8,
                GID_R_ENERGY        => 0.5,
                GID_R_COMBUST_DRIVE => 1.0,
                GID_R_IMPULSE_DRIVE => 1.0,
                GID_R_HYPER_DRIVE   => 0.95,
                GID_R_IGN           => 0.7,
            ],
            'ship_ratio' => [
                GID_F_SC       => 0.45,
                GID_F_LC       => 0.3,
                GID_F_CRUISER  => 0.15,
                GID_F_RECYCLER => 0.1,
            ],
        ],
        'constraints' => [
            'min_levels' => [GID_R_ESPIONAGE => 3, GID_R_COMPUTER => 2],
            'max_levels' => [],
        ],
        'attack_preferences' => [
            'target_type' => ['inactive'],
            'min_profit_ratio' => 1.8,
            'max_fleet_percentage' => 35,
        ],
    ],
    'subtypes' => [
        'opportunist' => [
            'name' => 'Opportunist Raider',
            'description' => 'Cargo ships, targets mostly inactive or profitable planets for easy raids.',
            'weights' => [
                'ship_ratio' => [
                    GID_F_SC       => 0.6,
                    GID_F_LC       => 0.25,
                    GID_F_CRUISER  => 0.1,
                    GID_F_RECYCLER => 0.05,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive'],
                'min_profit_ratio' => 1.8,
                'max_fleet_percentage' => 35,
            ],
        ],
        'pirate' => [
            'name' => 'Pirate Raider',
            'description' => 'Aggressive raiding, higher activity, goes after riskier and more defended targets.',
            'weights' => [
                'ship_ratio' => [
                    GID_F_LF      => 0.5,
                    GID_F_CRUISER => 0.15,
                    GID_F_SC      => 0.15,
                    GID_F_LC      => 0.15,
                    GID_F_RECYCLER=> 0.03,
                    GID_F_BOMBER  => 0.02,
                ],
            ],
            'attack_preferences' => [
                'target_type' => ['inactive', 'defended', 'profitable'],
                'min_profit_ratio' => 1.4,
                'max_fleet_percentage' => 70,
            ],
        ],
    ],
  ],
];
