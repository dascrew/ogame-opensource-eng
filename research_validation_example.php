<?php
/**
 * Example Usage of Research Validation Functions
 * 
 * This demonstrates how the research validation functions work together
 * in the OGame codebase.
 */

// Example: Check if a player can research Impulse Drive (ID 115)
function demonstrateResearchValidation($player_id, $planet_id, $research_id = 115) {
    // Load player and planet data (similar to what BotCanResearch does)
    $user = LoadUser($player_id);
    $planet = GetPlanet($planet_id);
    
    // Calculate the next level of research
    $level = $user['r' . $research_id] + 1;
    
    echo "=== Research Validation Demo ===\n";
    echo "Player: {$user['oname']}\n";
    echo "Research: Impulse Drive (ID: $research_id)\n";
    echo "Current Level: " . $user['r' . $research_id] . "\n";
    echo "Target Level: $level\n\n";
    
    // Step 1: Check technology prerequisites
    echo "1. Checking prerequisites with ResearchMeetRequirement()...\n";
    $prereq_ok = ResearchMeetRequirement($user, $planet, $research_id);
    if ($prereq_ok) {
        echo "   ✓ Prerequisites met\n";
    } else {
        echo "   ✗ Prerequisites NOT met\n";
        echo "   Required: Energy Tech level 1, Research Lab level 2\n";
        return false;
    }
    
    // Step 2: Full validation with CanResearch
    echo "\n2. Full validation with CanResearch()...\n";
    $validation_result = CanResearch($user, $planet, $research_id, $level);
    
    if ($validation_result === '') {
        echo "   ✓ Research can be started!\n";
        
        // Step 3: Get research cost and duration
        $cost = ResearchPrice($research_id, $level);
        $reslab = ResearchNetwork($planet['planet_id'], $research_id);
        $duration = ResearchDuration($research_id, $level, $reslab, 1.0);
        
        echo "\n3. Research Details:\n";
        echo "   Cost: {$cost['m']} Metal, {$cost['k']} Crystal, {$cost['d']} Deuterium\n";
        echo "   Duration: " . gmdate("H:i:s", $duration) . "\n";
        echo "   Research Network Level: $reslab\n";
        
        return true;
    } else {
        echo "   ✗ Cannot start research\n";
        echo "   Reason: $validation_result\n";
        return false;
    }
}

// Example: Bot API wrapper
function botApiExample($research_id = 115) {
    echo "\n=== Bot API Example ===\n";
    
    // This is what BotCanResearch does internally
    global $BotID, $BotNow;
    $user = LoadUser($BotID);
    $aktplanet = GetPlanet($user['aktplanet']);
    ProdResources($aktplanet, $aktplanet['lastpeek'], $BotNow);
    $level = $aktplanet['r' . $research_id] + 1;
    $text = CanResearch($user, $aktplanet, $research_id, $level);
    $can_research = ($text === '');
    
    echo "BotCanResearch($research_id) = " . ($can_research ? "true" : "false") . "\n";
    
    if ($can_research) {
        echo "Bot can proceed to start research using BotResearch($research_id)\n";
    } else {
        echo "Bot should wait or handle the issue: $text\n";
    }
    
    return $can_research;
}

// Example usage in web interface
function webInterfaceExample() {
    echo "\n=== Web Interface Logic ===\n";
    echo "In buildings.php, the research interface:\n\n";
    
    echo "1. Filters research list:\n";
    echo "   foreach (\$resmap as \$id) {\n";
    echo "       if (!ResearchMeetRequirement(\$user, \$planet, \$id)) continue;\n";
    echo "       // Only show research that meets prerequisites\n";
    echo "   }\n\n";
    
    echo "2. Shows green/red links based on resources:\n";
    echo "   if (IsEnoughResources(\$planet, \$m, \$k, \$d, \$e)) {\n";
    echo "       echo 'green clickable link';\n";
    echo "   } else {\n";
    echo "       echo 'red text (not enough resources)';\n";
    echo "   }\n\n";
    
    echo "3. When user clicks research button:\n";
    echo "   StartResearch(\$player_id, \$planet_id, \$research_id, \$now);\n";
    echo "   // This calls CanResearch() internally for final validation\n";
}

?>