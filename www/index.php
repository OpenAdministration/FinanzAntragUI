<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 22:19
 */

//auth ----------------------------------
include "../lib/inc.all.php";

// routing ------------------------------
$router = new Router();
$routeInfo = $router->route();

if (isset($routeInfo['auth']) && ($routeInfo['auth'] == 'Basic' || $routeInfo['auth'] == 'basic')){
    define('AUTH_HANDLER', 'AuthBasicHandler');
    (AUTH_HANDLER)::getInstance()->requireAuth();
    (AUTH_HANDLER)::getInstance()->requireGroup('basic');
	if (!isset($routeInfo['groups'])){
		$routeInfo['action'] = '403';
		$routeInfo['controller'] = 'error';
	}
} else {
    define('AUTH_HANDLER', 'AuthSamlHandler');
    (AUTH_HANDLER)::getInstance()->requireAuth();
}
if (isset($routeInfo['groups']) && !(AUTH_HANDLER)::getInstance()->hasGroup($routeInfo['groups'])){
	$routeInfo['action'] = '403';
	$routeInfo['controller'] = 'error';
}

//TODO ACL on route ? ----------------------------
$idebug = false;

// handle route -------------------------
$content = null;
$error = false;
//print_r($routeInfo);
$htmlRenderer = new HTMLPageRenderer($routeInfo);
$chatHandler = new ChatHandler($routeInfo);
switch ($routeInfo['controller']){
    case "menu":
        $menuRenderer = new MenuRenderer($routeInfo);
        $htmlRenderer->appendRendererContent($menuRenderer);
        $htmlRenderer->render();
        break;
    case "projekt":
        $projektRenderer = new ProjektHandler($routeInfo);
        $htmlRenderer->appendRendererContent($projektRenderer);
        $htmlRenderer->appendRendererContent($chatHandler);
        $htmlRenderer->render();
        break;
    case "auslagen":
        $auslagenHandler = new AuslagenHandler2($routeInfo);
        $htmlRenderer->appendRendererContent($auslagenHandler);
        $htmlRenderer->appendRendererContent($chatHandler);
        $htmlRenderer->render();
        break;
    case "hhp":
        $hhpHandler = new HHPHandler($routeInfo);
        $htmlRenderer->appendRendererContent($hhpHandler);
        $htmlRenderer->render();
        break;
    case "rest":
        $restHandler = new RestHandler();
        $restHandler->handlePost($routeInfo);
        break;
    case "files":
        $fileController = new FileController();
        $fileController->handle($routeInfo);
        break;
    case 'error':
    default:
        $errorHdl = new ErrorHandler($routeInfo);
        $htmlRenderer->appendRendererContent($errorHdl);
        $htmlRenderer->render();
        break;
}


