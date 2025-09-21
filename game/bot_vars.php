<?php

// Bot variable management functions

/**
 * Get a bot variable (legacy mode and extended mode).
 */
function BotGetVar() {
    $args = func_get_args();
    $num_args = func_num_args();
    
    if ($num_args >= 2 && is_numeric($args[0]) && $args[0] > 0) {
        $owner_id = $args[0];
        $var_name = $args[1];
        $default_value = $args[2] ?? null;
        return BotGetVarNew($owner_id, $var_name, $default_value);
    } else {
        //use legacy
        global $BotID;
        $var = $args[0];
        $def_value = $args[1] ?? null;
        
        if (!isset($BotID)) {
            return $def_value;
        }
        
        return BotGetVarNew($BotID, $var, $def_value);
    }
}

/**
 * Set a bot variable (legacy mode and extended mode).
 */
function BotSetVar() {
    $args = func_get_args();
    $num_args = func_num_args();
    
    if ($num_args >= 2 && is_numeric($args[0]) && $args[0] > 0) {
        $owner_id = $args[0];
        $var_name = $args[1];
        $value = $args[2];
        return BotSetVarNew($owner_id, $var_name, $value);
    } else { 
        //use legacy
        global $BotID;
        $var = $args[0];
        $value = $args[1];
        
        if (!isset($BotID)) {
            return false;
        }
        
        return BotSetVarNew($BotID, $var, $value);
    }
}

/**
 * Enhanced wrapper for BotGetVar that adds validation while using existing function.
 */
function BotGetVarNew($owner_id, $var_name, $default_value = null) {
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetVar: Invalid owner_id provided: " . $owner_id);
        return $default_value;
    }
    
    if (empty($var_name)) {
        Debug("BotGetVar: Empty var_name provided for owner_id: " . $owner_id);
        return $default_value;
    }
    
    // Use existing GetVar function
    return GetVar($owner_id, $var_name, $default_value);
}

/**
 * Enhanced wrapper for BotSetVar that adds validation while using existing function.
 */
function BotSetVarNew($owner_id, $var_name, $value) {
    // Input validation
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotSetVar: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotSetVar: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    
    if (is_array($value) || is_object($value)) {
        $value = serialize($value);
    } elseif (is_bool($value)) {
        $value = $value ? '1' : '0';
    }
    SetVar($owner_id, $var_name, $value);
    
    Debug("BotSetVar: Set variable '$var_name' for owner_id: $owner_id");
    return true;
}

/**
 * Delete a single variable by name for a bot.
 */
function BotDeleteVar($owner_id, $var_name) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotDeleteVar: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotDeleteVar: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    $var_escaped = mysqli_real_escape_string($GLOBALS['db_connect'], $var_name);
    $query = "DELETE FROM " . $db_prefix . "botvars WHERE var = '" . $var_escaped . "' AND owner_id = " . intval($owner_id);
    
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotDeleteVar: Failed to delete variable '$var_name' for owner_id: $owner_id");
        return false;
    }
    Debug("BotDeleteVar: Successfully deleted variable '$var_name' for owner_id: $owner_id");
    return true;
}

/**
 * Get all variables for a specific bot as an associative array.
 */
function BotGetAllVars($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetAllVars: Invalid owner_id provided: " . $owner_id);
        return array();
    }
    $query = "SELECT var, value FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotGetAllVars: Failed to query variables for owner_id: $owner_id");
        return array();
    }
    $variables = array();
    
    if (dbrows($result) > 0) {
        while ($row = dbarray($result)) {
            if ($row !== false) {
                $variables[$row['var']] = $row['value'];
            }
        }
    }
    
    Debug("BotGetAllVars: Retrieved " . count($variables) . " variables for owner_id: $owner_id");
    return $variables;
}

/**
 * Check if a bot variable exists.
 */
function BotVarExists($owner_id, $var_name) {
    global $db_prefix;
    
    // Input validation
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotVarExists: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    if (empty($var_name)) {
        Debug("BotVarExists: Empty var_name provided for owner_id: " . $owner_id);
        return false;
    }
    
    $var_escaped = mysqli_real_escape_string($GLOBALS['db_connect'], $var_name);
    $query = "SELECT id FROM " . $db_prefix . "botvars WHERE var = '" . $var_escaped . "' AND owner_id = " . intval($owner_id) . " LIMIT 1";
    
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotVarExists: Failed to check variable '$var_name' for owner_id: $owner_id");
        return false;
    }
    
    return dbrows($result) > 0;
}

/**
 * Get count of variables for a specific bot.
 */
function BotGetVarCount($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotGetVarCount: Invalid owner_id provided: " . $owner_id);
        return -1;
    }
    
    $query = "SELECT COUNT(*) as var_count FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotGetVarCount: Failed to count variables for owner_id: $owner_id");
        return -1;
    }
    
    if (dbrows($result) > 0) {
        $row = dbarray($result);
        if ($row !== false) {
            return intval($row['var_count']);
        }
    }
    
    return 0;
}

/**
 * Delete all variables for a specific bot.
 */
function BotDeleteAllVars($owner_id) {
    global $db_prefix;
    
    if (!is_numeric($owner_id) || $owner_id <= 0) {
        Debug("BotDeleteAllVars: Invalid owner_id provided: " . $owner_id);
        return false;
    }
    
    $query = "DELETE FROM " . $db_prefix . "botvars WHERE owner_id = " . intval($owner_id);
    $result = dbquery($query);
    
    if ($result === false) {
        Debug("BotDeleteAllVars: Failed to delete all variables for owner_id: $owner_id");
        return false;
    }
    
    Debug("BotDeleteAllVars: Successfully deleted all variables for owner_id: $owner_id");
    return true;
}

/**
 * Copy all variables from one bot to another.
 */
function BotCopyVars($source_owner_id, $target_owner_id, $overwrite = false) {
    // Input validation
    if (!is_numeric($source_owner_id) || $source_owner_id <= 0) {
        Debug("BotCopyVars: Invalid source_owner_id provided: " . $source_owner_id);
        return false;
    }
    
    if (!is_numeric($target_owner_id) || $target_owner_id <= 0) {
        Debug("BotCopyVars: Invalid target_owner_id provided: " . $target_owner_id);
        return false;
    }
    
    // Get all variables from source bot
    $source_vars = BotGetAllVars($source_owner_id);
    
    if (empty($source_vars)) {
        Debug("BotCopyVars: No variables found for source owner_id: $source_owner_id");
        return true; // Not an error, just nothing to copy
    }
    
    $copied_count = 0;
    
    // Copy each variable to target bot
    foreach ($source_vars as $var_name => $value) {
        // Check if variable exists in target if not overwriting
        if (!$overwrite && BotVarExists($target_owner_id, $var_name)) {
            Debug("BotCopyVars: Skipping existing variable '$var_name' for target owner_id: $target_owner_id");
            continue;
        }
        
        // Use existing SetVar function
        SetVar($target_owner_id, $var_name, $value);
        $copied_count++;
    }
    
    Debug("BotCopyVars: Copied $copied_count variables from owner_id $source_owner_id to $target_owner_id");
    return true;
}

/**
 * Validate that required bot variables exist.
 */
function BotValidateRequiredVars($owner_id, $required_vars) {
    if (!is_array($required_vars)) {
        Debug("BotValidateRequiredVars: required_vars must be an array");
        return array();
    }
    
    $missing_vars = array();
    
    foreach ($required_vars as $var_name) {
        if (!BotVarExists($owner_id, $var_name)) {
            $missing_vars[] = $var_name;
        }
    }
    
    if (!empty($missing_vars)) {
        Debug("BotValidateRequiredVars: Missing variables for owner_id $owner_id: " . implode(', ', $missing_vars));
    }
    
    return $missing_vars;
}


