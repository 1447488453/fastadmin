<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:99:"C:\phpstudy\PHPTutorial\WWW\fastadmin\public/../application/admin\view\user\audit\detail_audit.html";i:1540535184;s:80:"C:\phpstudy\PHPTutorial\WWW\fastadmin\application\admin\view\layout\default.html";i:1534473449;s:77:"C:\phpstudy\PHPTutorial\WWW\fastadmin\application\admin\view\common\meta.html";i:1534473449;s:79:"C:\phpstudy\PHPTutorial\WWW\fastadmin\application\admin\view\common\script.html";i:1534473449;}*/ ?>
<!DOCTYPE html>
<html lang="<?php echo $config['language']; ?>">
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">

<link rel="shortcut icon" href="/assets/img/favicon.ico" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config); ?>
    };
</script>
    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !$config['fastadmin']['multiplenav']): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <form id="edit-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="../../audit_status/ids/<?php echo $row['id']; ?>">

    <div class="form-group">
        <label for="c-username" class="control-label col-xs-12 col-sm-2"><?php echo __('真实姓名'); ?>:</label>
        <div class="col-xs-12 col-sm-4">
            <input readonly="readonly" id="c-username" data-rule="required" class="form-control" name="row[real_name]" type="text" value="<?php echo $row['real_name']; ?>">
        </div>
    </div>

       <div class="form-group">
        <label for="c-username" class="control-label col-xs-12 col-sm-2"><?php echo __('身份证号码'); ?>:</label>
        <div class="col-xs-12 col-sm-4">
            <input readonly="readonly" id="c-username" data-rule="required" class="form-control" name="row[id_card]" type="text" value="<?php echo $row['id_card']; ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="c-avatar" class="control-label col-xs-12 col-sm-2"><?php echo __('身份证正面照'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="input-group">
               <img  name="row[sfz_front_img]" src="<?php echo $row['sfz_front_img']; ?>">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="c-avatar" class="control-label col-xs-12 col-sm-2"><?php echo __('身份证背面照'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="input-group">
               <img name="row[sfz_back_img]" src="<?php echo $row['sfz_back_img']; ?>">
            </div>
        </div>
    </div>

   <div class="form-group">
        <label for="c-username" class="control-label col-xs-12 col-sm-2"><?php echo __('备注'); ?>:</label>
        <div class="col-xs-12 col-sm-4">
           <textarea  id="c-username" data-rule="required" class="form-control" name="row[remark]" value=""><?php echo $row['remark']; ?></textarea>
        </div>
    </div>
   
    <div class="form-group">
        <label for="content" class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <?php echo build_radios('row[status]', [1=>__('通过'), 2=>__('驳回')], $row['status']); ?>
        </div>
    </div>
    <div class="form-group layer-footer">
        <label class="control-label col-xs-12 col-sm-2"></label>
        <div class="col-xs-12 col-sm-8">
            <button type="submit" class="btn btn-success btn-embossed "><?php echo __('OK'); ?></button>
            <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo $site['version']; ?>"></script>
    </body>
</html>