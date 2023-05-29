<?php 
ob_start();
session_start();
include ("../_init.php");

// Comprobar, si el usuario ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, aparecerá un mensaje de alerta
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, un mensaje de alerta
if (user_group_id() != 1 && !has_permission('access', 'send_sms')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

// Get Poeples
if ($request->server['REQUEST_METHOD'] == 'GET' && isset($request->get['action_type']) && $request->get['action_type'] == 'PEOPLE')
{
  try {

    // Comprobar el permiso de actualización
    if (user_group_id() != 1 && !has_permission('access', 'send_sms')) {
      throw new Exception(trans('error_update_permission'));
    }

    $people_type = $request->get['people_type'];

    $peoples = '';

    switch ($people_type) {
      case 'all_customer':
        $statement = db()->prepare("SELECT `c`.`customer_id` AS `id`, `c`.`customer_name` AS `name`, `c`.`customer_mobile` AS `mobile` FROM `customers` c LEFT JOIN `customer_to_store` c2s ON (`c`.`customer_id` = `c2s`.`customer_id`) WHERE `c2s`.`store_id` = ? AND `status` = ?");
        $statement->execute(array(store_id(), 1));
        $peoples = $statement->fetchAll(PDO::FETCH_ASSOC);
        break;

      case 'all_user':
        $statement = db()->prepare("SELECT `u`.`id`, `u`.`username` AS `name`, `u`.`mobile` FROM `users` u LEFT JOIN `user_to_store` u2s ON (`u`.`id` = `u2s`.`user_id`) WHERE `u2s`.`store_id` = ? AND `status` = ?");
        $statement->execute(array(store_id(), 1));
        $peoples = $statement->fetchAll(PDO::FETCH_ASSOC);
        break;
      
      default:
        $statement = db()->prepare("SELECT `u`.`id`, `u`.`username` AS `name`, `u`.`mobile` FROM `users` u LEFT JOIN `user_to_store` u2s ON (`u`.`id` = `u2s`.`user_id`) WHERE `u2s`.`store_id` = ? AND `status` = ? AND `u`.`group_id` = ?");
        $statement->execute(array(store_id(), 1, $people_type));
        $peoples = $statement->fetchAll(PDO::FETCH_ASSOC);
        break;
    }

    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_update_success'), 'peoples' => $peoples));
    exit();

  } catch (Exception $e) { 
    
    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
} 