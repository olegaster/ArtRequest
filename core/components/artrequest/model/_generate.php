<?php
$package = 'artrequest';

// ---------------------------------------------------------------------------------------------------------------------
// run from wwwroot in browser
// ---------------------------------------------------------------------------------------------------------------------

require_once 'core/config/config.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();

$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('HTML');

$generator = $modx->getManager()->getGenerator();

$path = 'core/components/' . $package . '/model/';
$schema = $path . 'schema/' . $package . '.mysql.schema.xml';

$generator->parseSchema($schema, $path);
