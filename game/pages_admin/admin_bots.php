<?php

// Admin Area: Bot control

function Admin_Bots ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $result = "";

    // POST request processing.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        if (BotStrategyExists("_start")) {
            if ( AddBot ( $_POST['name'] ) ) $result = "<font color=lime>".loca("ADM_BOTS_ADDED")."</font>";
            else $result = "<font color=red>".loca("ADM_BOTS_USER_NOT_FOUND")."</font>";
        }
        else {
            $result = "<font color=red>".loca("ADM_BOTS_NO_START")."</font>";
        }
    }

    // GET request processing.
    if ( method () === "GET" && key_exists('id', $_GET) && $GlobalUser['admin'] >= 2 )
    {
        StopBot ( intval ($_GET['id']) );
        $result = "<font color=lime>".loca("ADM_BOTS_STOPPED")."</font>";
    }

?>

<?=AdminPanel();?>

<?php
    if ( $GlobalUser['admin'] < 2) {

        echo "<font color=red>".loca("ADM_BOTS_FORBIDDEN")."</font>";
        return;
    }
?>

<div style="text-align: center;"><?=$result;?></div>

<h2><?=loca("ADM_BOTS_LIST");?></h2>

<?php

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' GROUP BY owner_id";
    $result = dbquery ( $query );
    $rowss = $rows = dbrows ($result);
    if ( $rows == 0 ) echo loca("ADM_BOTS_NOT_FOUND") . "<br>";
    else {
        echo "<table>\n";
        echo "<tr><td class=c>ID</td><td class=c>".loca("ADM_BOTS_NAME")."</td><td class=c>".loca("ADM_BOTS_HOMEPLANET")."</td><td class=c>".loca("ADM_BOTS_ACTION")."</td></tr>\n";
    }
    while ($rows--) {
    $queue = dbarray($result);
    if (!is_array($queue) || !isset($queue['owner_id'])) {
        continue;
    }
    $user = LoadUser($queue['owner_id']);
    
    if (!is_array($user) || !isset($user['hplanetid'], $user['player_id'])) {
        continue;
    }
    $planet = GetPlanet($user['hplanetid']);

    if (!is_array($planet) || !isset($planet['planet_id'])) {
        continue;
    }

    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['player_id']) . "</td>";
    echo "<td>" . AdminUserName($user) . "</td>";
    echo "<td>" . AdminPlanetName($planet['planet_id']) . " " . AdminPlanetCoord($planet) . "</td>";
    echo "<td><a href=\"index.php?page=admin&session=$session&mode=Bots&action=stop&id=" . urlencode($user['player_id']) . "\">" . loca("ADM_BOTS_STOP") . "</a></td>";
    echo "</tr>\n";
}
?>

<h2><?=loca("ADM_BOTS_ADD");?></h2>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Bots" method="POST">
<table>
<tr><th scope="col"><?=loca("ADM_BOTS_NAME");?></th></tr>
<tr><td><input type=text size=10 name="name" /> <input type=submit value="<?=loca("ADM_BOTS_SUBMIT");?>" /></td></tr>
</table>
</form>

<?php
}
?>
