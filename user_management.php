<?php
// This file is part of the Huygens Remote Manager
// Copyright and license notice: see license.txt

use hrm\Mail;
use hrm\Nav;
use hrm\user\UserConstants;
use hrm\user\UserManager;
use hrm\user\proxy\ProxyFactory;
use hrm\user\UserV2;
use hrm\Util;
use hrm\Validator;

require_once dirname(__FILE__) . '/inc/bootstrap.php';

global $hrm_url;
global $email_sender;
global $email_admin;
global $image_host;
global $image_folder;
global $image_source;
global $userManagerScript;
global $authenticateAgainst;

session_start();

/*
 *
 * SANITIZE INPUT
 *   We check the relevant contents of $_POST for validity and store them in
 *   a new array $clean that we will use in the rest of the code.
 *
 *   After this step, only the $clean array and no longer the $_POST array
 *   should be used!
 *
 */

// Here we store the cleaned variables
$clean = array(
    "username" => "",
    "email" => '',
    "group" => "");

// Email
if (isset($_POST["username"])) {
    if (Validator::isUserNameValid($_POST["username"])) {
        $clean["username"] = $_POST["username"];
    }
}

// Email
if (isset($_POST["email"])) {
    if (Validator::isEmailValid($_POST["email"])) {
        $clean["email"] = $_POST["email"];
    }
}

// Group name
if (isset($_POST["group"])) {
    if (Validator::isGroupNameValid($_POST["group"])) {
        $clean["group"] = $_POST["group"];
    }
}

// Is admin flag
if (isset($_POST["role_change"])) {
    if (isset($_POST["is_admin"])) {
        UserManager::setRole($_POST["role_change"], UserConstants::ROLE_ADMIN);
    } else {
        UserManager::setRole($_POST["role_change"], UserConstants::ROLE_USER);
    }
}

/*
 *
 * END OF SANITIZE INPUT
 *
 */

if (isset($_GET['home'])) {
    header("Location: " . "home.php");
    exit();
}

// Check if a user is logged on. If not, go to the login page and store the seed
// if there is one.
if (!isset($_SESSION['user'])) {
    if (isset($_GET['seed'])) {
        $req = $_SERVER['REQUEST_URI'];
        $_SESSION['request'] = $req;
    }
    header("Location: " . "login.php");
    exit();
}

// Make sure that the user is the admin
if (!$_SESSION['user']->isAdmin()) {
    header("Location: " . "login.php");
    exit();
}

if (isset($_GET['seed'])) {
    if (! UserManager::existsUserRegistrationRequestWithSeed($_GET['seed'])) {
        header("Location: " . "login.php");
        exit();
    }
}

if (isset($_SERVER['HTTP_REFERER']) &&
    !strstr($_SERVER['HTTP_REFERER'], 'admin') &&
    !strstr($_SERVER['HTTP_REFERER'], 'account')
) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}

// TODO refactor
if (isset($_SESSION['admin_referer'])) {
    $_SESSION['referer'] = $_SESSION['admin_referer'];
    unset($_SESSION['admin_referer']);
}

if (!isset($_SESSION['index'])) {
    $_SESSION['index'] = "";
} else if (isset($_GET['index'])) {
    $_SESSION['index'] = $_GET['index'];
}

// Check if there is a message from the account page that we need to
// display
$message = "";
if (isset($_SESSION['account_update_message'])) {
    $message = $_SESSION['account_update_message'];
    unset($_SESSION['account_update_message']);
}

if (isset($_POST['accept'])) {
    $result = UserManager::acceptUser($clean['username']);
    // TODO refactor
    if ($result) {
        $accepted_user = new UserV2($clean['username']);
        $email = $accepted_user->emailAddress();
        $text = "Your account has been activated:\n\n";
        $text .= "\t      Username: " . $clean['username'] . "\n";
        $text .= "\tE-mail address: " . $email . "\n\n";
        $text .= "Login here\n";
        $text .= $hrm_url . "\n\n";
        $folder = $image_folder . "/" . $clean['username'];
        $text .= "Source and destination folders for your images are located " .
            "on server " . $image_host . " under " . $folder . ".";
        $mail = new Mail($email_sender);
        $mail->setReceiver($email);
        $mail->setSubject("HRM account activated");
        $mail->setMessage($text);
        $mail->send();
        shell_exec("$userManagerScript create \"" . $clean['username'] . "\"");
    } else {
        $message = "Could not accept the user! Please inform the administrator.";
    }
} else if (isset($_POST['reject'])) {
    $user_to_reject = new UserV2($clean['username']);
    $email = $user_to_reject->emailAddress();
    $result = UserManager::deleteUser($user_to_reject->name());
    // TODO refactor
    if (!$result) {
        $message = "There was an error rejecting the new user request! Please contact the administrator.";
    }
    $text = "Your request for an HRM account has been rejected. Please " .
        "contact " . $email_admin . " for any enquiries.\n";
    $mail = new Mail($email_sender);
    $mail->setReceiver($email);
    $mail->setSubject("Request for an HRM account rejected");
    $mail->setMessage($text);
    $mail->send();
} else if (isset($_POST['annihilate']) && $_POST['annihilate'] == "yes") {
    $user_to_delete = new UserV2($clean['username']);
    if (! ($user_to_delete->isSuperAdmin())) {
        $result = UserManager::deleteUser($clean['username']);
        if (!$result) {
            $message = "The user could not be deleted! " .
                "Please make sure that there are no jobs in the queue for this user.";
        }
    } else {
        $message = "Cannot delete the super administrator!";
    }
} else if (isset($_POST['edit'])) {
    $_SESSION['account_user'] = new UserV2($clean['username']);
    if (isset($c) || isset($_GET['c']) || isset($_POST['c'])) {
        if (isset($_GET['c'])) $_SESSION['c'] = $_GET['c'];
        else if (isset($_POST['c'])) $_SESSION['c'] = $_POST['c'];
    }
    header("Location: " . "account.php");
    exit();
} else if (isset($_POST['enable'])) {
    $result = UserManager::enableUser($clean['username']);
} else if (isset($_POST['disable'])) {
    $result = UserManager::disableUser($clean['username']);
} else if (isset($_POST['action'])) {
    if ($_POST['action'] == "disable") {
        $result = UserManager::disableAllUsers();
    } else if ($_POST['action'] == "enable") {
        $result = UserManager::enableAllUsers();
    }
}
// TODO refactor to here

$script = "admin.js";

include("header.inc.php");

?>

<div id="nav">
    <div id="navleft">
        <ul>
            <?php
            echo(Nav::linkWikiPage('HuygensRemoteManagerHelpUserManagement'));
            ?>
        </ul>
    </div>
    <div id="navright">
        <ul>
            <?php
            echo(Nav::textUser($_SESSION['user']->name()));
            echo(Nav::linkHome(Util::getThisPageName()));
            ?>
        </ul>
    </div>
    <div class="clear"></div>
</div>

<div id="content">

    <h3><img alt="ManageUsers" src="./images/users.png"
             width="40"/>&nbsp;&nbsp;Manage users</h3>

    <?php

    // GEt list of users with pending requests
    $rows = UserManager::getAllPendingUserDBRows();

    $i = 0;
    foreach ($rows as $row) {
        $name = $row["name"];
        $email = $row["email"];
        $group = $row["research_group"];
        $creation_date = date("j M Y, G:i", strtotime($row["creation_date"]));
        $status = $row["status"];

            ?>
            <form method="post" action="">
                <div>
                    <fieldset>
                        <legend>pending request</legend>
                        <table>
                            <tr class="upline">
                                <td class="name">
                                <span class="title">
                                    <?php echo $name ?>
                                </span>
                                </td>
                                <td class="group">
                                    <?php echo $group ?>
                                </td>
                                <td class="email">
                                    <a href="mailto:<?php echo $email ?>"
                                       class="normal">
                                        <?php echo $email ?>
                                    </a>
                                </td>
                            </tr>
                            <tr class="bottomline">
                                <td colspan="2" class="date">
                                    request
                                    date: <?php echo $creation_date . "\n" ?>
                                </td>
                                <td class="operations">
                                    <div>
                                        <input type="hidden"
                                               name="username"
                                               value="<?php echo $name ?>"/>
                                        <input type="submit"
                                               name="accept"
                                               value="accept"/>
                                        <input type="submit"
                                               name="reject"
                                               value="reject"/>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </div>
            </form>
            <br/>
            <?php

            $i++;
    }

    if ($i == 0) {

        ?>
        <p>There are no pending requests.</p>
        <?php

    }

    ?>

        <div id="listusers">
        <fieldset>
            <?php

            // All users (independent of their status), including the administrator
            $count = UserManager::getTotalNumberOfUsers();

            // Active users
            $rows = UserManager::getAllActiveUserDBRows();
            $emails = array();
            foreach ($rows as $row) {
                $e = trim($row['email']);
                if (strlen($e) > 0) {
                    array_push($emails, $e);
                }
            }
            $emails = array_unique($emails);
            sort($emails);

            ?>
            <legend>
                Existing users (<?php echo $count - 1; // Ignore admin ?>)
            </legend>
            <p class="menu">
                <a href="javascript:openPopup('add_user')">
                    add new user
                </a> |
                <a href="mailto:<?php echo $email_admin; ?>?bcc=
                    <?php echo implode($email_list_separator, $emails); ?>">
                    distribution list
                </a>
                <br/>
                <a href="javascript:disableUsers()">
                    disable
                </a>/
                <a href="javascript:enableUsers()">
                    enable
                </a> all users
            </p>

            <?php

            /* Get the number of users with names starting with each of the letters
            of the alphabet. */
            $counts = UserManager::getNumberCountPerInitialLetter();
            $letters = array_keys($counts);
            ?>

            <form method="post" action="" id="user_management">
                <div><input type="hidden" name="action"/></div>
            </form>
            <table>
                <tr>
                    <td colspan="3" class="menu">
                        <div class="line">
                            <?php

                            $style = "filled";
                            if ($_SESSION['index'] == "all") {
                                $style = "selected";
                            }
                            ?>

                            [<a href="?index=all"
                                class="<?php echo($style); ?>">
                                &nbsp;all&nbsp;</a>]&nbsp;[

                            <?php
                            for ($i = 0; $i < count($counts); $i++) {
                                $c = $letters[$i];
                                if ($_SESSION['index'] == $c) {
                                    $style = "selected";
                                } else if ($counts[$c] == 0) {
                                    $style = "empty";
                                } else {
                                    $style = "filled";
                                }

                                echo "<a href=\"?index=$c\" class=\"$style\">&nbsp;" .
                                    strtoupper($c) . "&nbsp;</a>";
                            }
                            ?>
                            ]
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <?php

                if ($_SESSION['index'] != "") {
                    if ($_SESSION['index'] != "all") {
                        $rows = UserManager::getAllUserDBRowsByInitialLetter($_SESSION['index']);
                    } else {
                        $rows = UserManager::getAllUserDBRows();
                    }
                    $i = 0;
                    foreach ($rows as $row) {
                        if ($row['name'] != "admin") {
                            $name = $row['name'];
                            $email = $row['email'];
                            $group = $row['research_group'];
                            $is_admin = ($row['role'] == UserConstants::ROLE_ADMIN);
                            if ($row['last_access_date'] === null) {
                                $last_access_date = "never";
                            } else {
                            $last_access_date = date("j M Y, G:i",
                                strtotime($row['last_access_date']));
                            }
                            $status = $row['status'];
                            if ($status == "a" || $status == "d" || $status == "o") {
                                if ($i > 0) {
                                    echo "                    " .
                                        "<tr><td colspan=\"3\" class=\"hr\">&nbsp;</td></tr>\n";
                                }
                                ?>
                                <tr class="upline<?php
                                if ($status == "d") {
                                    echo " disabled";
                                }
                                ?>">
                                    <td class="name">
                            <span class="title">
                                <?php echo $name ?>
                            </span>
                                    </td>
                                    <td class="group">
                                        <?php echo $group ?>
                                    </td>
                                    <td class="email">
                                        <a href="mailto:<?php echo $email ?>"
                                           class="normal"><?php echo $email ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr class="middleline<?php
                                if ($status == "d") {
                                    echo " disabled";
                                }
                                ?>">
                                <td colspan="1" class="auth">
                                    authentication
                                </td>
                                <td colspan="1" class="auth">
                                    <a href="authentication_mode.php?name=<?php echo($name); ?>">
                                    <?php
                                    echo(ProxyFactory::getProxy($name)->friendlyName());
                                    ?>
                                    </a>
                                </td>
                                <td colspan="1" class="auth">
                                    <?php
                                    if ($is_admin) {
                                        $checked = " checked";
                                    } else {
                                        $checked = "";
                                    }
                                    ?>
                                    <form method="post" action="">
                                        <input type="hidden"
                                               name="role_change"
                                               value="<?php echo $name ?>">
                                        <label id="users_isadmin_label">
                                            <input type="checkbox"
                                                   name="is_admin"
                                                   value="1" <?php echo($checked); ?>
                                                   onChange="this.form.submit()"> admin
                                        </label>
                                    </form>
                                </td>
                                </tr>
                                <tr class="bottomline<?php
                                if ($status == "d") {
                                    echo " disabled";
                                }
                                ?>">
                                    <td colspan="1" class="date">
                                        last access
                                    </td>
                                    <td colspan="1" class="date">
                                        <?php echo $last_access_date . "\n" ?>
                                    </td>

                                    <td class="operations">
                                        <form method="post" action="">
                                            <div>
                                                <input type="hidden"
                                                       name="username"
                                                       value="<?php echo $name ?>"/>
                                                <input type="hidden"
                                                       name="email"
                                                       value="<?php echo $email ?>"/>
                                                <input type="hidden"
                                                       name="group"
                                                       value="<?php echo $group ?>"/>
                                                <input type="submit"
                                                       name="edit"
                                                       value="edit"
                                                       class="submit"/>
                                                <?php

                                                if ($_SESSION['user']->isAdmin()) {
                                                    if ($status == "d") {

                                                        ?>
                                                        <input type="submit"
                                                               name="enable"
                                                               value="enable"
                                                               class="submit"/>
                                                        <?php

                                                    } else {

                                                        ?>
                                                        <input type="submit"
                                                               name="disable"
                                                               value="disable"
                                                               class="submit"/>
                                                        <?php

                                                    }

                                                    ?>

                                                    <input type="hidden"
                                                           name="annihilate"/>
                                                    <input type="button"
                                                           name="delete"
                                                           value="delete"
                                                           onclick="warn(this.form,
                                           'Do you really want to delete this user?')"
                                                           class="submit"/>
                                                    <?php

                                                }

                                                ?>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php

                                $i++;
                            }
                        }
                    }
                    if (!$i) {

                        ?>
                        <tr>
                            <td colspan="3" class="notice">
                                n/a
                            </td>
                        </tr>
                        <?php

                    }
                }

                ?>
            </table>
        </fieldset>
    </div>

</div> <!-- content -->

<div id="rightpanel">
    <div id="info">
        <h3>Quick help</h3>

        <p>You can add new users, accept or reject pending registration
            requests, and manage existing users.</p>
    </div>
    <div id="message">
        <?php

        echo "<p>$message</p>";

        ?>
    </div>
</div>  <!-- rightpanel -->

<?php

include("footer.inc.php");

?>
