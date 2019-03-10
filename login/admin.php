<?php
include "includes/header.php";
include "includes/nav.php";
?>

    <div class="jumbotron">
        <h1 class="text-center">
            <?php

            if (logged_in()) {
                echo "You are logged in!";
            } else {
                redirect("login.php");
            }

            ?>
        </h1>
    </div>

<?php
include "includes/footer.php";
?>