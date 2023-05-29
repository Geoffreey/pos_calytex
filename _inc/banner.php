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
if (user_group_id() != 1 && !has_permission('access', 'read_banner')) {
  header('HTTP/1.1 422 Unprocessable Entity');
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(array('errorMsg' => trans('error_read_permission')));
  exit();
}

// MODELO DE CATEGORÍA DE CARGA
$banner_model = registry()->get('loader')->model('banner');

// Validar datos de publicación
function validate_request_data($request) 
{
  // Validate banner name
  if (!validateString($request->post['name'])) {
    throw new Exception(trans('error_name'));
  }

  // Validate banner designation
  if (!validateString($request->post['slug'])) {
    throw new Exception(trans('error_slug'));
  }

  // Validación de ID de tienda
  if (!isset($request->post['banner_store']) || empty($request->post['banner_store'])) {
    throw new Exception(trans('error_store'));
  }

  // Validación del orden de clasificación
  if (!is_numeric($request->post['status'])) {
    throw new Exception(trans('error_status'));
  }

  // Validación del orden de clasificación
  if (!is_numeric($request->post['sort_order'])) {
    throw new Exception(trans('error_sort_order'));
  }
}

// Check banner existance by id
function validate_existance($request, $id = 0)
{
  if (!empty($request->post['slug'])) {
    $statement = db()->prepare("SELECT * FROM `banners` WHERE `slug` = ? AND `id` != ?");
    $statement->execute(array($request->post['slug'], $id));
    if ($statement->rowCount() > 0) {
      throw new Exception(trans('error_banner_exist'));
    }
  }
}

// Crear banner
if ($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'CREATE')
{
  try {

    // Comprobar el permiso
    if (user_group_id() != 1 && !has_permission('access', 'create_banner')) {
      throw new Exception(trans('error_create_permission'));
    }

    // Validar datos de publicación
    validate_request_data($request);
    
    // validar existencia
    validate_existance($request);

    $Hooks->do_action('Before_Create_Banner', $request);

    // Insert new banner into databtase
    $id = $banner_model->addBanner($request->post);

    // Fetch banner info
    $banner = $banner_model->getBanner($id);

    $Hooks->do_action('After_Create_Banner', $banner);

    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_success'), 'id' => $id, 'banner' => $banner));
    exit();

  } catch (Exception $e) { 

    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
}

// Update banner
if ($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'UPDATE')
{
  try {

    // Comprobar el permiso de actualización
    if (user_group_id() != 1 && !has_permission('access', 'update_banner')) {
      throw new Exception(trans('error_update_permission'));
    }

    // Validar el identificador del producto
    if (empty($request->post['id'])) {
      throw new Exception(trans('error_id'));
    }

    $id = $request->post['id'];

    // Validar datos de publicación
    validate_request_data($request);

    // validar existencia
    validate_existance($request, $id);

    $Hooks->do_action('Before_Update_Banner', $request);
    
    // Edit banner
    $banner = $banner_model->editBanner($id, $request->post);

    $Hooks->do_action('After_Update_Banner', $banner);

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

// Delete banner
if ($request->server['REQUEST_METHOD'] == 'POST' && isset($request->post['action_type']) && $request->post['action_type'] == 'DELETE') 
{
  try {

    // Comprobar el permiso de eliminación
    if (user_group_id() != 1 && !has_permission('access', 'delete_banner')) {
      throw new Exception(trans('error_delete_permission'));
    }

    // Validate banner id
    if (empty($request->post['id'])) {
      throw new Exception(trans('error_id'));
    }

    $id = $request->post['id'];
    $the_banner = $banner_model->getBanner($id);

    if (!$the_banner) {
      throw new Exception(trans('error_id'));
    }

    $Hooks->do_action('Before_Delete_Banner', $request);

    // Delete banner
    $banner = $banner_model->deleteBanner($id);

    $Hooks->do_action('After_Delete_Banner', $banner);

    header('Content-Type: application/json');
    echo json_encode(array('msg' => trans('text_delete_success'), 'id' => $id));
    exit();

  } catch (Exception $e) {

    header('HTTP/1.1 422 Unprocessable Entity');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array('errorMsg' => $e->getMessage()));
    exit();
  }
}

// View invoice details
if (isset($request->get['action_type']) AND $request->get['action_type'] == 'INVOICEDETAILS') {

    try {

      $id = isset($request->get['id']) ? $request->get['id'] : null;
      $where_query = "((`selling_info`.`invoice_type` = 'sell' AND `selling_info`.`edit_count` < 1) OR `selling_info`.`invoice_type` = 'sell_edit')  AND `selling_item`.`id` = ?  AND `invoice_status` = ?";
      $from = from() ? from() : date('Y-m-d');
      $to = to() ? to() : date('Y-m-d');
      $where_query .= date_range_filter($from, $to);

      $statement = db()->prepare("SELECT `selling_info`.*, `selling_item`.`id`, SUM(`selling_item`.`item_total_price`) AS `item_total_price`, SUM(`selling_item`.`item_discount`) AS `item_discount` FROM `selling_item` 
          LEFT JOIN `selling_info` ON (`selling_item`.`invoice_id` = `selling_info`.`invoice_id`)
          WHERE $where_query GROUP BY `selling_item`.`invoice_id`");
      $statement->execute(array($id, 1));
      $the_invoices = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (!$statement->rowCount() > 0) {
          throw new Exception(trans('error_not_found'));
      }

      $invoices = array();
      $from = date('Y-m-d H:i:s', strtotime($from.' '.'00:00:00')); 
      $to = date('Y-m-d H:i:s', strtotime($to.' '.'23:59:59'));
      foreach ($the_invoices as $invoice) {
        if (!$invoice['ref_invoice_id']) {
          $invoices[$invoice['invoice_id']] = $invoice;
          continue;
        }
        $ref_invoice = get_the_invoice($invoice['ref_invoice_id']);
        if ($from == $to) {
            if (date('Y-m-d', strtotime($ref_invoice['created_at'])) == date('Y-m-d')) {
                $invoices[$ref_invoice['invoice_id']] = $invoice;
            }
        } elseif ((date('Y-m-d H:i:s', strtotime($ref_invoice['created_at'])) >= $from) && (date('Y-m-d H:i:s', strtotime($ref_invoice['created_at'])) <= $to)) {
            $invoices[$ref_invoice['invoice_id']] = $invoice;
        }
      }

      include('template/banner_invoice_details.php');
      exit();
        
    } catch (Exception $e) { 

      header('HTTP/1.1 422 Unprocessable Entity');
      header('Content-Type: application/json; charset=UTF-8');
      echo json_encode(array('errorMsg' => $e->getMessage()));
      exit();
    }
}

// Banner create form
if (isset($request->get['action_type']) && $request->get['action_type'] == 'CREATE') 
{
  include 'template/banner_create_form.php';
  exit();
}

// Banner edit form
if (isset($request->get['id']) AND isset($request->get['action_type']) && $request->get['action_type'] == 'EDIT') 
{
  $banner = $banner_model->getBanner($request->get['id']);
  include 'template/banner_edit_form.php';
  exit();
}

// Banner delete form
if (isset($request->get['id']) AND isset($request->get['action_type']) && $request->get['action_type'] == 'DELETE') 
{
  $banner = $banner_model->getBanner($request->get['id']);
  include 'template/banner_delete_form.php';
  exit();
}

/**
 *===================
 **INICIAR TABLA DE DATOS
 *===================
 */

$Hooks->do_action('Before_Showing_Banner_List');

$where_query = 'b2s.store_id = ' . store_id();
 
// Tabla de base de datos que se va a utilizar
$table = "(SELECT banners.*, b2s.status, b2s.sort_order FROM banners 
  LEFT JOIN banner_to_store b2s ON (banners.id = b2s.banner_id) 
  WHERE $where_query GROUP by banners.id
  ) as banners";
 
//  Llave principal de la tabla
$primaryKey = 'id';

$columns = array(
  array(
      'db' => 'id',
      'dt' => 'DT_RowId',
      'formatter' => function( $d, $row ) {
          return 'row_'.$d;
      }
  ),
  array( 'db' => 'id', 'dt' => 'id' ),
  array( 
    'db' => 'name',   
    'dt' => 'name',
    'formatter' => function($d, $row) {
      return $row['name'];
    }
  ),
  array( 'db' => 'slug', 'dt' => 'slug' ),
  array( 
    'db' => 'sort_order',   
    'dt' => 'sort_order',
    'formatter' => function($d, $row) {
      return $row['sort_order'];
    }
  ),
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
    'db' => 'created_at',   
    'dt' => 'created_at' ,
    'formatter' => function($d, $row) {
        return $row['created_at'];
    }
  ),
  array(
    'db'        => 'id',
    'dt'        => 'btn_edit',
    'formatter' => function( $d, $row ) {
      return '<button id="edit-banner" class="btn btn-sm btn-block btn-primary" type="button" title="'.trans('button_edit').'"><i class="fa fa-fw fa-pencil"></i></button>';
    }
  ),
  array(
    'db'        => 'id',
    'dt'        => 'btn_delete',
    'formatter' => function( $d, $row ) {
      return '<button id="delete-banner" class="btn btn-sm btn-block btn-danger" type="button" title="'.trans('button_delete').'"><i class="fa fa-fw fa-trash"></i></button>';
    }
  )
); 

echo json_encode(
    SSP::simple($request->get, $sql_details, $table, $primaryKey, $columns)
);

$Hooks->do_action('After_Showing_Banner_List');

/**
 *===================
 * FIN DE LA TABLA DE DATOS
 *===================
 */