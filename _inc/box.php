<?php 
ob_start();
session_start();
include ("../_init.php");

// Comprobar, si el usuario ha iniciado sesión o no
// Si el usuario no ha iniciado sesión, devuelve el error
if (!is_loggedin()) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_login')));
  exit();
}

// Verifique, si el usuario tiene permiso de lectura o no
// Si el usuario no tiene permiso de lectura, devuelve el error
if (user_group_id() != 1 && !has_permission('access', 'read_box')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

// MODELO DE CARGA DE CAJA 
$box_model = registry()->get('loader')->model('box');

// Validar datos de publicación
function validate_request_data($request) {

  // Validación del nombre de caja
  if (!validateString($request->post['box_name'])) {
      throw new Exception(trans('error_box_name'));
  }

  // Validación del nombre del código de caja
  if (!validateString($request->post['code_name'])) {
      throw new Exception(trans('error_code_name'));
  }

  // Validación de la tienda
  if (!isset($request->post['box_store']) || empty($request->post['box_store'])) {
    throw new Exception(trans('error_store'));
  }

  // Validación de estado
  if (!is_numeric($request->post['status'])) {
    throw new Exception(trans('error_status'));
  }

  // Validación del orden de clasificación
  if (!is_numeric($request->post['sort_order'])) {
    throw new Exception(trans('error_sort_order'));
  }
}

// Casilla de verificación existencia por id
function validate_existance($request, $id = 0)
{
  

  // Comprobar, si el nombre de la caja existe o no
  $statement = db()->prepare("SELECT * FROM `boxes` WHERE `box_name` = ? AND `box_id` != ?");
  $statement->execute(array($request->post['box_name'], $id));
  if ($statement->rowCount() > 0) {
    throw new Exception(trans('error_box_name_exist'));
  }
}

// Crear caja
if ($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'CREATE')
{
  try {

    // Comprobar el permiso de creación
    if (user_group_id() != 1 && !has_permission('access', 'create_box')) {
      throw new Exception(trans('error_create_permission'));
    }

    // Validar datos de publicación
    validate_request_data($request);

    // Validar la existencia
    validate_existance($request);

    $Hooks->do_action('Before_Create_Box');

    // Agregar caja
    $box_id = $box_model->addBox($request->post);

    // Fetch the box info
    $box = $box_model->getBox($box_id);

    $Hooks->do_action('After_Create_Box', $box);

    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_success'), 'id' => $box_id, 'box' => $box));
    exit();

  } catch (Exception $e) { 
    
    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
}

// Actualización de caja
if ($request->server['REQUEST_METHOD'] == 'POST' AND isset($request->post['action_type']) && $request->post['action_type'] == 'UPDATE')
{
  try {

    // Comprobar el permiso de actualización
    if (user_group_id() != 1 && !has_permission('access', 'update_box')) {
      throw new Exception(trans('error_update_permission'));
    }

    // Validar el ID de caja
    if (empty($request->post['box_id'])) {
      throw new Exception(trans('error_box_id'));
    }

    $id = $request->post['box_id'];

    // Validar datos de publicación
    validate_request_data($request);

    // Validar la existencia
    validate_existance($request, $id);

    $Hooks->do_action('Before_Update_Box', $request);
    
    // Editar caja
    $box = $box_model->editBox($id, $request->post);

    $Hooks->do_action('After_Update_Box', $box);
    
    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_update_success'), 'id' => $id));
    exit();

  } catch (Exception $e) { 

    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
} 

// Eliminar caja
if ($request->server['REQUEST_METHOD'] == 'POST' AND isset($request->post['action_type']) && $request->post['action_type'] == 'DELETE') {
  try {

    // Comprobar el permiso de eliminación
    if (user_group_id() != 1 && !has_permission('access', 'delete_box')) {
      throw new Exception(trans('error_delete_permission'));
    }

    // Validar el ID de caja
    if (empty($request->post['box_id'])) {
      throw new Exception(trans('error_box_id'));
    }

    $id = $request->post['box_id'];
    $new_box_id = $request->post['new_box_id'];

    if (DEMO && $id == 1) {
      throw new Exception(trans('error_delete_permission'));
    }

    // Validar la acción de eliminación
    if (empty($request->post['delete_action'])) {
      throw new Exception(trans('error_delete_action'));
    }

    if ($request->post['delete_action'] == 'insert_to' && empty($new_box_id)) {
      throw new Exception(trans('error_delete_box_name'));
    }

    $Hooks->do_action('Before_Delete_Box', $request);

    $belongs_stores = $box_model->getBelongsStore($id);
    foreach ($belongs_stores as $the_store) {

      //  Comprobar si la relación existe o no
      $statement = db()->prepare("SELECT * FROM `box_to_store` WHERE `box_id` = ? AND `store_id` = ?");
      $statement->execute(array($new_box_id, $the_store['store_id']));
      if ($statement->rowCount() > 0) continue;

      //  Crear relación
      $statement = db()->prepare("INSERT INTO `box_to_store` SET `box_id` = ?, `store_id` = ?");
      $statement->execute(array($new_box_id, $the_store['store_id']));
    }

    // Actualización de caja id for product
    $statement = db()->prepare("UPDATE `product_to_store` SET `box_id` = ? WHERE `box_id` = ?");
    $statement->execute(array($new_box_id, $id));

    // Eliminar la caja
    $box = $box_model->deleteBox($id);

    $Hooks->do_action('After_Delete_Box', $box);
    
    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_delete_success')));
    exit();

  } catch(Exception $e) { 
    
    $error_message = $e->getMessage();
    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $error_message));
    exit();
  }
}

// Formulario de crear caja
if (isset($request->get['action_type']) && $request->get['action_type'] == 'CREATE') 
{
  include 'template/box_create_form.php';
  exit();
}

// Formulario de edicion de caja
if (isset($request->get['box_id']) AND isset($request->get['action_type']) && $request->get['action_type'] == 'EDIT') 
{
  // Información de la caja de búsqueda
  $box = $box_model->getBox($request->get['box_id']);
  include 'template/box_form.php';
  exit();
}


// box delete form
if (isset($request->get['box_id']) AND isset($request->get['action_type']) && $request->get['action_type'] == 'DELETE') 
{
  // Información de la caja de búsqueda
  $box = $box_model->getBox($request->get['box_id']);
  $Hooks->do_action('Before_Box_Delete_Form', $box);
  include 'template/box_del_form.php';
  $Hooks->do_action('After_Box_Delete_Form', $box);
  exit();
}

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$Hooks->do_action('Before_Showing_Box_List');

$where_query = 'b2s.store_id = ' . store_id();
 
// Tabla de base de datos que se va a utilizar
$table = "(SELECT boxes.*, b2s.status, b2s.sort_order FROM boxes 
  LEFT JOIN box_to_store b2s ON (boxes.box_id = b2s.box_id) 
  WHERE $where_query GROUP by boxes.box_id
  ) as boxes";
 
//  Llave principal de la tabla
$primaryKey = 'box_id';
$columns = array(
  array(
      'db' => 'box_id',
      'dt' => 'DT_RowId',
      'formatter' => function( $d, $row ) {
          return 'row_'.$d;
      }
  ),
  array( 'db' => 'box_id', 'dt' => 'box_id' ),
  array( 
    'db' => 'box_name',   
    'dt' => 'box_name' ,
    'formatter' => function($d, $row) {
        return ucfirst($row['box_name']);
    }
  ),
  array( 
    'db' => 'box_id',   
    'dt' => 'total_product' ,
    'formatter' => function($d, $row) use($box_model) {
        return $box_model->totalProduct($row['box_id']);
    }
  ),
  array( 'db' => 'box_details',  'dt' => 'box_details' ),
  array( 
    'db' => 'status',   
    'dt' => 'status',
    'formatter' => function($d, $row) {
      return $row['status'] 
        ? '<span class="label label-success">'.trans('text_active').'</span>' 
        : '<span class="label label-warning">' .trans('text_inactive').'</span>';
    }
  ),
  array(
    'db'        => 'box_id',
    'dt'        => 'btn_edit',
    'formatter' => function( $d, $row ) {
      if (DEMO && $row['box_id'] == 1) {          
        return'<button class="btn btn-sm btn-block btn-default" type="button" disabled><i class="fa fa-pencil"></i></button>';
      }
      return '<button id="edit-box" class="btn btn-sm btn-block btn-primary" type="button" title="'.trans('button_edit').'"><i class="fa fa-fw fa-pencil"></i></button>';
    }
  ),
  array(
    'db'        => 'box_id',
    'dt'        => 'btn_delete',
    'formatter' => function( $d, $row ) {
      if ($row['box_id'] == 1) {
        return '<button class="btn btn-sm btn-block btn-default" type="button" disabled><i class="fa fa-fw fa-trash"></i></button>';
      }
      return '<button id="delete-box" class="btn btn-sm btn-block btn-danger" type="button" title="'.trans('button_delete').'"><i class="fa fa-fw fa-trash"></i></button>';
    }
  )
); 

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

$Hooks->do_action('After_Showing_Box_List');

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */