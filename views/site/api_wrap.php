<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|Source+Code+Pro:300,600|Titillium+Web:400,600,700" rel="stylesheet">
<link rel="icon" type="image/png" href="/swagger/favicon-32x32.png" sizes="32x32" />
<link rel="icon" type="image/png" href="/swagger/favicon-16x16.png" sizes="16x16" />
<style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }

    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    body
    {
        margin:0;
        background: #fafafa;
    }

    .swagger-ui .info .title small
    {
        display: none;
    }
</style>
<div id="swagger-ui"></div>

<?php

    $host = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"];

    $js = <<< SCRIPT

            const ui = SwaggerUIBundle({
                url: "$host/swagger/swagger.json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
            });
SCRIPT;

    $this->registerJs($js, \yii\web\View::POS_READY);
?>

<?= $this->registerCssFile('@web/swagger/swagger-ui.css'); ?>
<?= $this->registerJsFile('@web/swagger/swagger-ui-bundle.js'); ?>
