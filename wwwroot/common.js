
function changeAction(type) {
    if(type != "register" && document.loginForm.universe.value == '') {
        alert('<?php echo loca("LOGIN_NOTCHOSEN");?>');
    }
    else {
        if(type == "login") {
            // Always use a relative path for login
            document.loginForm.action = "/game/reg/login2.php";
        }
        else if (type=="getpw") {
            // Always use a relative path for password recovery
            document.loginForm.action = "/game/reg/mail.php";
            document.loginForm.submit();
        }
        else if(type == "register") {
            // Always use a relative path for registration
            document.registerForm.action = "/game/reg/newredirect.php";
        }
    }
}

function printMessage(code, div) {
    var textclass = "";

    if (div == null) {
        div = "statustext";
    }
    switch (code) {
        case "0":
            text = "<?php echo loca("ERROR_0");?>";
            textclass = "fine";
            break;
        case "101":
            text = "<?php echo loca("ERROR_101");?>";
            textclass = "warning";
            break;
        case "102":
            text = "<?php echo loca("ERROR_102");?>";
            textclass = "warning";
            break;
        case "103":
            text = "<?php echo loca("ERROR_103");?>";
            textclass = "warning";
            break;
        case "104":
            text = "<?php echo loca("ERROR_104");?>";
            textclass = "warning";
            break;
        case "105":
            text = "<?php echo loca("ERROR_105");?>";
            textclass = "fine";
            break;
        case "106":
            text = "<?php echo loca("ERROR_106");?>";
            textclass = "fine";
            break;
        case "107":
            text = "<?php echo loca("ERROR_107");?>";
            textclass = "warning";
            break;
        case "108":
            text = "<?php echo loca("ERROR_108");?>";
            textclass = "warning";
            break;
        case "109":
            text = "<?php echo loca("ERROR_109");?>";
            textclass = "warning";
            break;
        case "201":
            text = "<?php echo loca("TIP_201");?>";
            break;
        case "202":
            text = "<?php echo loca("TIP_202");?>";
            break;
        case "203":
            text = "<?php echo loca("TIP_203");?>";
            break;
        case "204":
            text = "<?php echo loca("TIP_204");?>";
            break;
        case "205":
            text = "<?php echo loca("TIP_205");?>";
            break;
        default:
            text = code;
            break;
    }

    if (textclass != "") {
        text = "<span class='" + textclass + "'>" + text + "</span>";
    }
    document.getElementById(div).innerHTML = text;
}
