<?php

/* @var $this yii\web\View */

    $this->title = 'Report page';
?>

<style>
    .table_control button, .datetime_wrap {
        margin: 15px;
    }
    #layout {
        width: 100%;
        min-height: 420px;
    }
    #sum {
        display: none;
    }
    .datetime_wrap span {
        margin-right: 20px;
    }
</style>

<?php
    $this->registerCssFile('@web/css/w2ui-1.5.rc1.min.css');
    $this->registerJsFile('@web/js/w2ui-1.5.rc1.min.js', ['depends' => [yii\web\JqueryAsset::className()]]);
    $this->registerJsFile('@web/js/report.js', ['depends' => [yii\web\JqueryAsset::className()]]);
?>

<div id="layout"></div>
<div id="sum"></div>