<?php 
ob_start();
session_start();
include ("../_init.php");

//Redirigir, si el usuario no ha iniciado sesión
if (!is_loggedin()) {
  redirect(root_url() . '/index.php?redirect_to=' . url());
}

// Redirigir, si el usuario no tiene permiso de lectura
if (user_group_id() != 1 && !has_permission('access', 'read_sell_tax_report')) {
  redirect(root_url() . '/'.ADMINDIRNAME.'/dashboard.php');
}

$invoice_model = registry()->get('loader')->model('invoice');

// Establecer título del documento
$document->setTitle(trans('title_sell_tax_report'));

// Agregar script
$document->addScript('../assets/itsolution24/angular/controllers/ReportSellTaxController.js');

// AGREGAR CLASE DE CUERPO
$document->setBodyClass('sidebar-collapse');

// Incluir encabezado y pie de página
include("header.php"); 
include ("left_sidebar.php") ;
?>

<!--Inicio del contenedor de contenido-->
<div class="content-wrapper" ng-controller="ReportSellTaxController">

  <!--Inicio del encabezado de contenido-->
  <section class="content-header">
    <?php include ("../_inc/template/partials/apply_filter.php"); ?>
    <h1>
      <?php echo trans('text_sell_tax_report_title'); ?>
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
      <li class="active">
        <?php echo trans('text_sell_tax_report_title'); ?>
      </li>
    </ol>
  </section>
  <!--Fin del encabezado de contenido-->

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
      <div class="col-sm-12 tax-values">
        <div class="row">
          <div class="<?php echo get_preference('invoice_view') == 'indian_gst' ? 'col-sm-6' : 'col-sm-12';?>">
            <div class="row">
              <div class="col-sm-4">
                <div class="small-box bred">
                <h4><?php echo currency_format(selling_price(from(), to()));?></h4>
                <p><?php echo trans('text_sell_amount'); ?></p>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="small-box bpurple">
                  <h4><?php echo currency_format(get_tax('item_tax', from(), to()));?></h4>
                  <p><?php echo trans('text_product_tax_amount'); ?></p>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="small-box bblue">
                  <h4><?php echo currency_format(get_tax('order_tax', from(), to()));?></h4>
                  <p><?php echo trans('text_order_tax_amount'); ?></p>
                </div>
              </div>
            </div>
          </div>
          <?php if (get_preference('invoice_view') == 'indian_gst') : ?>
          <div class="col-sm-6">
            <div class="small-box col-sm-4 borange">
              <h4><?php echo currency_format(get_tax('igst', from(), to()));?></h4>
              <p><?php echo trans('text_igst'); ?></p>
            </div>
            <div class="small-box col-sm-4 bolive">
              <h4><?php echo currency_format(get_tax('cgst', from(), to()));?></h4>
              <p><?php echo trans('text_cgst'); ?></p>
            </div>
            <div class="small-box col-sm-4 borange">
              <h4><?php echo currency_format(get_tax('sgst', from(), to()));?></h4>
              <p><?php echo trans('text_sgst'); ?></p>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="clearfix"></div>
      </div>

      <div class="col-xs-12">
        <div class="box box-success">
          <div class="box-header">
            <h3 class="box-title">
              <?php echo trans('text_tax_report_sub_title'); ?>
            </h3>
          </div>
          <div class="box-body">
            <div class="table-responsive">  
              <?php
                  $print_columns = '0,1,2,3,4';
                  $hide_colums = "";
                ?>
              <table id="report-report-list" class="table table-bordered table-striped table-hover" data-hide-colums="<?php echo $hide_colums; ?>" data-print-columns="<?php echo $print_columns;?>">
                <thead>
                  <tr class="bg-gray">
                    <th class="w-25">
                      <?php echo trans('label_created_at'); ?>
                    </th>
                    <th class="w-25">
                      <?php echo trans('label_invoice_id'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_product_tax'); ?>
                    </th>
                    <th class="w-15">
                      <?php echo trans('label_order_tax'); ?>
                    </th>
                    <th class="w-20">
                      <?php echo trans('label_total_amount'); ?>
                    </th>
                  </tr>
                </thead>
                <tfoot>
                  <tr class="bg-gray">
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
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