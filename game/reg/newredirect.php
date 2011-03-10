<?php

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

require_once "../config.php";
require_once "../db.php";

require_once "../bbcode.php";
require_once "../msg.php";
require_once "../planet.php";
require_once "../user.php";

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

// Проверить регистрационные данные.

function isValidEmail($email){
	return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
}

if ( $_SERVER['REQUEST_METHOD'] === "POST" )
{
    if ( $_POST['agb'] !== 'on' ) $AGB = 0;
    else $AGB = 1;

    if ( strlen ($_POST['password']) < 8 ) $RegError = 107;
    else if ( mb_strlen ($_POST['character']) < 3 || mb_strlen ($_POST['character']) > 20 ) $RegError = 103;
    else if ( IsUserExist ( $_POST['character'])) $RegError = 101;
    else if ( !isValidEmail ($_POST['email']) ) $RegError = 104;
    else if ( IsEmailExist ( $_POST['email'])) $RegError = 102;
    else $RegError = 0;

    // Если все параметры верные - создать нового пользователя и войти в игру.
    if ($RegError == 0 && $AGB)
    {
        CreateUser ( $_POST['character'], $_POST['password'], $_POST['email'] );
        //Login ( $_POST['character'], $_POST['password'] );
        exit ();
    }

    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage/register.php?errorCode=$RegError&agb=$AGB&character=".$_POST['character']."&email=".$_POST['email']."&universe=".$_POST['universe']."' /></head><body></body></html>";
    exit ();
}

// Открыть new.php
echo "<html><head><meta http-equiv='refresh' content='0;url=new.php' /></head><body></body></html>";

?>