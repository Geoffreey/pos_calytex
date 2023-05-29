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
// Si el usuario no tiene permiso de lectura, un mensaje de alerta
if (user_group_id() != 1 && !has_permission('access', 'read_currency')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

// MODELO DE MONEDA DE CARGA
$currency_model = registry()->get('loader')->model('currency');

// Validar datos de publicación
function validate_request_data($request) 
{
  // Validar título
  if(!validateString($request->post['title'])) {
    throw new Exception(trans('error_currency_title'));
  }

  // Validar código
  if(!validateString($request->post['code'])) {
    throw new Exception(trans('error_currency_code'));
  }

  // Validar el símbolo de moneda izquierda/derecha
  if(!validateString($request->post['symbol_left']) && !validateString($request->post['symbol_right'])) {
    throw new Exception(trans('error_currency_symbol'));
  }

  // Validar el lugar decimal
  if(!validateInteger($request->post['decimal_place'])) {
    throw new Exception(trans('error_currency_decimal_place'));
  }

  // Validar el almacén de divisas
  if (!isset($request->post['currency_store']) || empty($request->post['currency_store'])) {
    throw new Exception(trans('error_store'));
  }

  // Validar el estado
  if (!is_numeric($request->post['status'])) {
    throw new Exception(trans('error_status'));
  }

  // Validación del orden de clasificación
  if (!is_numeric($request->post['sort_order'])) {
    throw new Exception(trans('error_sort_order'));
  }
}

// Comprobar la existencia de moneda por id
function validate_existance($request, $id = 0)
{
  

  // Verifique el título de la moneda, ¿existe??
  $statement = db()->prepare("SELECT * FROM `currency` WHERE (`title` = ? OR `code` = ?) AND `currency_id` != ?");
  $statement->execute(array($request->post['title'], $request->post['code'], $id));
  if ($statement->rowCount() > 0) {
    throw new Exception(trans('error_payment_code_or_title_exist'));
  }
}

// Crear moneda
if ($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'CREATE')
{
  try {

    // Crear comprobación de permisos
    if (user_group_id() != 1 && !has_permission('access', 'create_currency')) {
      throw new Exception(trans('error_read_permission'));
    }
    
    // Validar datos de publicación
    validate_request_data($request);

    // Validar la existencia
    validate_existance($request);

    $Hooks->do_action('Before_Create_Currency', $request);

    // Insertar moneda en la base de datos    
    $currency_id = $currency_model->addCurrency($request->post);

    // Obtener moneda
    $currency = $currency_model->getCurrency($currency_id);

    $Hooks->do_action('After_Create_Currency', $currency);

    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_success'), 'id' => $currency_id, 'currency' => $currency));
    exit();

  } catch(Exception $e) { 

    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
} 

// Actualizar moneda
if($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'UPDATE')
{
  try {

    // Comprobar el permiso de actualización
    if (user_group_id() != 1 && !has_permission('access', 'update_currency')) {
      throw new Exception(trans('error_update_permission'));
    }

    // Validar el ID de moneda
    if (empty($request->post['currency_id'])) {
      throw new Exception(trans('error_currency_id'));
    }

    $id = $request->post['currency_id'];

    if (DEMO && $id == 1) {
      throw new Exception(trans('error_update_permission'));
    }

    // Validar datos de publicación
    validate_request_data($request);

    // Validar la existencia
    validate_existance($request, $id);

    $Hooks->do_action('Before_Update_Currency', $request);
    
    // Editar moneda        
    $currency = $currency_model->editCurrency($id, $request->post);

    $Hooks->do_action('After_Update_Currency', $currency);

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

// Eliminar moneda
if($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'DELETE') 
{
  try {

    // Comprobar el permiso de eliminación
    if (user_group_id() != 1 && !has_permission('access', 'delete_currency')) {
      throw new Exception(trans('error_delete_permission'));
    }

    // Validar ID de moneda
    if (empty($request->post['currency_id'])) {
      throw new Exception(trans('error_currency_id'));
    }

    $id = $request->post['currency_id'];

    if (DEMO && $id == 1) {
      throw new Exception(trans('error_delete_permission'));
    }

    // La moneda activa no se puede eliminar
    if ($id == currency_id()) {
      throw new Exception(trans('error_delete_active_currency'));
    }

    $Hooks->do_action('Before_Delete_Currency', $request);

    // Eliminar moneda
    $currency = $currency_model->deleteCurrency($id);

    $Hooks->do_action('After_Delete_Currency', $currency);
    
    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_delete_success')));
    exit();

  } catch (Exception $e) { 

    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
}

// Formulario de edición de moneda
if (isset($request->get['currency_id']) && isset($request->get['action_type']) && $request->get['action_type'] == 'EDIT') {
    
    $currency_id = (int)$request->get['currency_id'];
    // Obtener información de moneda
    $currency = $currency_model->getCurrency($currency_id);
    include 'template/currency_form.php';
    exit();
}


/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$Hooks->do_action('Before_Showing_Currency_List');
 
$where_query = 'c2s.store_id = '.store_id();
 
// Tabla de base de datos que se va a utilizar
$table = "(SELECT currency.*, c2s.status, c2s.sort_order FROM currency 
  LEFT JOIN currency_to_store c2s ON (currency.currency_id = c2s.currency_id) 
  WHERE $where_query GROUP by currency.currency_id
  ) as currency";
 
//  Llave principal de la tabla
$primaryKey = 'currency_id';

$columns = array(
  array(
      'db' => 'currency_id',
      'dt' => 'DT_RowId',
      'formatter' => function( $d, $row ) {
          return 'row_'.$d;
      }
  ),
  array( 'db' => 'currency_id', 'dt' => 'currency_id' ),
  array( 
    'db' => 'title',   
    'dt' => 'title' ,
    'formatter' => function($d, $row) {
        return ucfirst($row['title']);
    }
  ),
  array( 'db' => 'code',  'dt' => 'code' ),
  array( 'db' => 'symbol_left',  'dt' => 'symbol_left' ),
  array( 'db' => 'symbol_right',  'dt' => 'symbol_right' ),
  array( 'db' => 'decimal_place',  'dt' => 'decimal_place' ),
  array( 
    'db' => 'status',   
    'dt' => 'status' ,
    'formatter' => function($d, $row) {
        return $row['status'] == 1 ? '<span class="label label-info">'.trans('text_enabled').'</span>' : '<span class="label label-warning">'.trans('text_disabled').'</span>';
    }
  ),
  array( 
    'db' => 'status',   
    'dt' => 'btn_edit' ,
    'formatter' => function($d, $row) {
      if (DEMO && $row['currency_id'] == 1) {          
        return'<button class="btn btn-sm btn-block btn-default" type="button" disabled><i class="fa fa-pencil"></i></button>';
      }
      return '<button id="edit-currency" class="btn btn-sm btn-block btn-primary" type="button" title="'.trans('button_edit').'"><i class="fa fa-fw fa-pencil"></i></button>';
    }
  ),
  array( 
    'db' => 'status',   
    'dt' => 'btn_delete' ,
    'formatter' => function($d, $row) {
      if (DEMO && $row['currency_id'] == 1) {          
        return'<button class="btn btn-sm btn-block btn-default" type="button" disabled><i class="fa fa-trash"></i></button>';
      }
      if ($row['currency_id'] == currency_id()) {
        return '<button id="delete-currency" class="btn btn-sm btn-block btn-default" type="button" disabled><i class="fa fa-fw fa-trash" title="'.trans('button_delete').'"></i></button>';
      }

      return '<button id="delete-currency" class="btn btn-sm btn-block btn-danger" type="button"><i class="fa fa-fw fa-trash" title="'.trans('button_delete').'"></i></button>';
    }
  ),
  array( 
    'db' => 'code',   
    'dt' => 'btn_activate' ,
    'formatter' => function($d, $row) use($currency) {
        $button = "";
        if ($row['status'] == 1) {
            if ($currency->getCode() == $row['code']) {
                $button = '<button class="btn btn-sm  btn-block btn-info" type="button" disabled><i class="fa fa-fw fa-check"></i>'.trans('button_activated').'</button>';
            } else {
                $button = '<button  type="button" class="btn btn-sm btn-block btn-success currency-change" data-code="'.$row['code'].'" data-loading-text="Applying..."><i class="fa fa-fw fa-check"></i>'.trans('button_activate').'</button>';
            }
        }
        return $button;
    }
  )
); 

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

$Hooks->do_action('After_Showing_Currency_List');

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */