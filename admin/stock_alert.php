<?php 
ob_start();
session_start();
include ("../_init.php");

//Redirigir, si el usuario no ha iniciado sesión
if (!is_loggedin()) {
  redirect(root_url() . '/index.php?redirect_to=' . url());
}

// Redirigir, si el usuario no tiene permiso de lectura
if (user_group_id() != 1 && !has_permission('access', 'read_stock_alert')) {
  redirect(root_url() . '/'.ADMINDIRNAME.'/dashboard.php');
}

// Establecer título del documento
$document->setTitle(trans('title_stock_alert'));

// Agregar script
// $document->addScript('../assets/itsolution24/angular/modals/PurchaseProductModal.js');
$document->addScript('../assets/itsolution24/angular/controllers/StockAlertController.js');

// Incluir encabezado y pie de página
include ("header.php") ;
include ("left_sidebar.php") ; 
?>

<!--Inicio del contenedor de contenido-->
<div class="content-wrapper" ng-controller="StockAlertController">

  <!-- Header Content Start -->
  <section class="content-header">
    <?php include ("../_inc/template/partials/apply_filter.php"); ?>
    <h1>
      <?php echo trans('text_stock_alert_title'); ?>
      <small>
        <?php echo store('name'); ?>
      </small>
    </h1>
    <ol class="breadcrumb">
      <li>
        <a href="dashboard.php">
          <i class="fa fa-dashboard"></i> 
          <?php echo trans('text_dashboard'); ?>
        </a>
      </li>
      <li>
        <a href="product.php"><?php echo trans('text_products'); ?></a>  
      </li>
      <li class="active">
        <?php echo trans('text_stock_alert_title'); ?>
      </li>
    </ol>
  </section>
  <!-- Header Content End -->

  <!-- Inicio de contenido -->
  <section class="content">

    <?php if(DEMO) : ?>
    <div class="box">
      <div class="box-body">
        <div class="alert alert-info mb-0">
          <p><span class="fa fa-fw fa-info-circle"></span> <?php echo $demo_text; ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-warning">
          <div class="box-header">
            <h3 class="box-title">
              <?php echo trans('text_stock_alert_box_title'); ?>
            </h3>
          </div>
          <div class="box-body">
            <div class="table-responsive">  

              <?php
                $print_columns = '0,1,2,3,4,5';
                if (user_group_id() != 1) {
                  if (! has_permission('access', 'show_purchase_price')) {
                    $print_columns = str_replace('4,', '', $print_columns);
                  }
                }
                $hide_colums = "";
                if (user_group_id() != 1) {
                  if (! has_permission('access', 'create_purchase_invoice')) {
                    $hide_colums .= "6,";
                  }
                  if (! has_permission('access', 'view_purchase_price')) {
                    $hide_colums .= "4,";
                  }
                }
              ?> 

              <!-- Out of Product List Start -->
              <table id="product-product-list" class="table table-bordered table-striped table-hover" data-hide-colums="<?php echo $hide_colums; ?>" data-print-columns="<?php echo $print_columns;?>">
                <thead>
                  <tr class="bg-gray">
                    <th class="w-5">
                      <?php echo sprintf(trans('label_id'),null); ?>
                    </th>
                    <th class="w-25">
                      <?php echo sprintf(trans('label_name'), null); ?>
                    </th>
                    <th class="w-20">
                      <?php echo trans('label_supplier'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_mobile'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_purchase_price'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_stock'); ?>
                    </th>
                    <th class="w-10">
                      <?php echo trans('label_action'); ?>
                    </th>
                  </tr>
                </thead>
                <tfoot>
                  <tr class="bg-gray">
                    <th class="w-5">
                      <?php echo sprintf(trans('label_id'),null); ?>
                    </th>
                    <th class="w-25">
                      <?php echo sprintf(trans('label_name'), null); ?>
                    </th>
                    <th class="w-20">
                      <?php echo trans('label_supplier'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_mobile'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_purchase_price'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_stock'); ?>
                    </th>
                    <th class="w-10">
                      <?php echo trans('label_action'); ?>
                    </th>
                  </tr>
                </tfoot>
              </table>
              <!-- Out of Stock Product List End -->

            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Fin del contenido -->

</div>
<!-- Fin del contenedor de contenido -->

<?php include ("footer.php"); ?>