<?php 
ob_start();
session_start();
include ("../_init.php");

// Verifique, si ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, devuelva un mensaje de alerta
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, devuelva un mensaje de alerta
if (user_group_id() != 1 && !has_permission('access', 'read_brand_profile') && !has_permission('access', 'read_brand')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}