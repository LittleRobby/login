<?php
/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.02.2019
 * Time: 16:44
 */


/**********************  Helper Funktionen **************************/

function clean($string)
{
    return htmlentities($string);
}

function redirect($location)
{
    return header("Location: {$location}");
}

function set_message($message)
{
        $_SESSION["message"] = $message;
}

function display_message()
{
    if (isset($_SESSION["message"])) {
        echo $_SESSION["message"];
        unset($_SESSION["message"]);
    }
}

function token_generator()
{
    $token = $_SESSION["token"] = md5(uniqid(mt_rand(), true));

    return $token;
}

function display_errors($error_messages)
{
    $error_messages = <<<BORDER
                    <div class="alert alert-danger" role="alert">
                    $error_messages
                    </div>
BORDER;

    return $error_messages;
}

function check_exists($field, $value)
{
    $sql = "SELECT id FROM users WHERE $field = '$value';";

    $result = query($sql);

    if (row_count($result) >= 1) {
        return true;
    }
    return false;
}

function send_email($email, $subject, $message, $headers)
{
    return mail($email, $subject, $message, $headers);
}


/******************************  Validierungs-Funktionen   **********************************/


function validate_user_registration()
{
    $errors = [];
    $user_data = [];

    $min = 4;
    $max = 30;

    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        $first_name = clean($_POST['first_name']);
        $last_name = clean($_POST['last_name']);
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);

        $user_data = ["Firstname" => $first_name, "Lastname" => $last_name, "Username" => $username, "Email" => $email];

        foreach ($user_data as $key => $value) {
            $errors[] = check_length($min, $max, $key, $value);
        }

        if (check_exists("username", $username)) {
            $errors[] = "Der Benutzername existiert bereits.";
        }
        if (check_exists("email", $email)) {
            $errors[] = "Die Email-Adresse existiert bereits.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwort und Passwortbestätigungs stimmen nicht überein.";
        }

        $error_counter = 0;
        if (!empty($errors)) {
            foreach ($errors as $error) {
                if ($error != "") {
                    $error_counter++;
                    echo display_errors($error);
                }
            }
        }
        if ($error_counter == 0) {
            register_user($first_name, $last_name, $username, $email, $password);
        }
    }
}

function check_length($min, $max, $key, $value)
{
    if (strlen($value) > $max) {
        return "$key darf höchstens $max Stellen lang sein.<br>";
    } elseif (strlen($value) < $min) {
        return "$key muss mindestens $min Stellen lang sein.<br>";
    }
}

function register_user($first_name, $last_name, $username, $email, $password)
{
    $userdata = [$first_name, $last_name, $username, $email, $password];
    foreach ($userdata as $data) {
        $data = escape($data);
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $validation = md5($username . microtime());
    $sql = "INSERT INTO users (first_name, last_name, username, email, password, validation_code, active) VALUES 
    ('$first_name' , '$last_name', '$username', '$email', '$hashed_password', '$validation', 0);";
    $result = query($sql);
    confirm($result);

    $subject = "Zugang aktivieren";
    $message = "Bitte klicken sie auf den unten aufgeführten Link um ihre Registrierung abzuschließen 
    und ihren Zugang zu aktivieren. 
    
    http://localhost/login/activate.php?email=$email&code=$validation
    
    ";
    $headers = "From: noreply@login.de";

    send_email($email, $subject, $message, $headers);

    $_SESSION["message"] = "Registrierung erfolgreich. Bitte prüfen sie ihre Emails.";
    redirect("index.php");
}


/******************************  Aktivierungs-Funktionen   **********************************/

function activate_user()
{
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        if (isset($_GET['email'])) {
            $email = escape(clean($_GET['email']));
            $validation_code = escape(clean($_GET['code']));

            $sql = "SELECT id FROM users WHERE email = '$email' AND validation_code = '$validation_code'";
            $result = query($sql);
            confirm($result);

            if (row_count($result) == 1) {
                $row = $result->fetch_assoc();
                $sql = "UPDATE users SET active = 1, validation_code = '0' WHERE id = " . $row["id"] . ";";
                $result = query($sql);
                confirm($result);
                $_SESSION["message"] = "<p class=\"alert alert-success text-center\">Benutzer erfolgreich aktiviert.</p>";
                redirect("login.php");

            } else {
                $_SESSION["message"] = "<p class=\"alert alert-danger text-center\">Aktivierung fehlgeschlagen.</p>";
                redirect("login.php");
            }


        }
    }
}

/******************************  Validate Login-Funktion   **********************************/

function validate_login_data()
{
    $errors = [];


    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $email = escape(clean($_POST['email']));
        $password = escape(clean($_POST['password']));
        $remember = isset($_POST['remember']);

        if (empty($email)) {
            $errors[] = "Email can not be empty.";
        }

        if (empty($password)) {
            $errors[] = "Password can not be empty.";
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo display_errors($error);
            }
        } else {
            if (login_user($email, $password, $remember)) {
                redirect("admin.php");
            } else {
                echo display_errors("Username or password wrong!");
            }
        }
    }
}

/******************************  Login-Funktion   **********************************/

function login_user($email, $password, $remember)
{
    $sql = "SELECT * FROM users WHERE email = '$email' AND active = 1;";
    $result = query($sql);
    confirm($result);

    if (row_count($result) == 1) {
        $row = fetch_array($result);
        $hashed_password = $row['password'];

        if (password_verify($password, $hashed_password)) {
            $_SESSION['email'] = $email;
            if ($remember == "on") {
                setcookie('email', $email, time() + 86400);
            }

            return true;

        } else {

            return false;
        }

    } else {
        return false;
    }
}

/******************************  Logged-In-Funktion   **********************************/

function logged_in()
{
    if (isset($_SESSION['email']) || isset($_COOKIE['email'])) {
        return true;
    } else {
        return false;
    }
}


/******************************  Recover-Password-Funktion   **********************************/

function recover_password()
{
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']) {

            $email = $_SESSION['recover_mail'] = escape(clean($_POST['email']));
            $validation_code = md5($email . microtime());
            setcookie('temp_access_code', $validation_code, time() + 300);


            if (check_exists("email", $email)) {

                $message = <<<DELIMIT
                 
                Enter your password recover code: $validation_code or click on the link below<br>
                http://localhost/login/code.php?email=$email&code=$validation_code

DELIMIT;
                $headers = "From: noreply@login.de";

                if (send_email($email, 'Recover Password', $message, $headers)) {
                    $sql = "UPDATE users SET validation_code = '" . escape($validation_code) . "' where email = '" . escape($email) . "'";
                    $result = query($sql);
                    confirm($result);

                    set_message("Please check your emails vor validation code<br>");

                    redirect("index.php");
                } else {
                    echo display_errors("Email could not be send");
                };

            } else {
                echo display_errors("Email doesn't exist");
            }
        } else {
            redirect("login.php");
        }
    }
}

/******************************  Validierung für Code-Eingabe-Seite   **********************************/

function check_validation()
{
    if (isset($_COOKIE["temp_access_code"])) {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            if (isset($_GET['email']) &&
                !empty($_GET['email']) &&
                isset($_GET['code']) &&
                !empty($_GET['code'])) {

                return true;
            }
        }
    }
    return false;
}

function validate_code()
{
    if (check_validation()) {

        if(isset($_POST['code'])) {
            $validation_code = escape(clean($_POST['code']));
            $sql = "SELECT id FROM users WHERE validation_code = '{$validation_code}';";
            $result = query($sql);
            confirm($result);
            if(row_count($result) == 1) {
                $row = fetch_array($result);
                print_r($result);
                print_r($row);
                echo "User gefunden: " . $row['id'];
            }
        }

    } else {
        set_message("Your validation has expired or you are using incorrect data.<br>Please ask for new code.<br>");
        redirect("recover.php");
    }
}