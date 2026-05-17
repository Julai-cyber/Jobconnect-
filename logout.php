<?php
/**
 * JobConnect — logout.php
 * Encerra a sessão e redireciona para o login.
 */
session_start();
session_destroy();
header('Location: ../login.php');
exit;
