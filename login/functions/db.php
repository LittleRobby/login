<?php
/**
 * Created by PhpStorm.
 * User: Sascha
 * Date: 23.02.2019
 * Time: 16:45
 */

$con = mysqli_connect('localhost', 'root', '', 'login_db');


function row_count($result)
{
    return mysqli_num_rows($result);
}


function escape($string)
{

    global $con;

    return mysqli_real_escape_string($con, $string);

}

function query($query)
{

    global $con;

    return mysqli_query($con, $query);
}

function confirm($result)
{
    global $con;

    if (!$result) {
        die("Abfrage fehlgeschlagen" . mysqli_error($con));
    }
}

function fetch_array($result)
{
    global $con;

    return mysqli_fetch_array($result);
}