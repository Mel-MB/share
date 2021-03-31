<?php
use Project\Controllers\{PagesController,AuthController};
use Project\Core\Application;

require_once(__DIR__.'/../vendor/autoload.php');
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$config =[
    'db' => [
        'dsn' =>$_ENV['DB_DSN'],
        'user' =>$_ENV['DB_USER'],
        'password' =>$_ENV['DB_PASSWORD'],
    ]
];
$app = new Application(dirname(__DIR__),$config);


// Pages
$app->router->get('/',[PagesController::class, 'home']);
$app->router->get('/se-connecter', [PagesController::class, 'login']);
$app->router->get('/s-inscrire', [PagesController::class, 'register']);

// Connected user restricted access pages
$app->router->get('/mon-compte', [AuthController::class, 'profile']);
$app->router->get('/modifier-mon-compte', [AuthController::class, 'accoutEdit']);
$app->router->get('/post/publier', "create()");
$app->router->get('/mes-posts', [PostController::class, 'userPublishedPosts']);
$app->router->get('/post/modifier/{int $id}', "update(int $id)");

// Actions
$app->router->post('/se-connecter', [PagesController::class, 'login']);
$app->router->post('/s-inscrire', [PagesController::class, 'register']);

//  Connected user restricted actions
$app->router->post('/modifier-mon-compte', [AuthController::class, 'accountEdit']);
$app->router->post('/supprimer-mon-compte', [AuthController::class, 'accountDelete']);
$app->router->post('/se-deconnecter', [AuthController::class, 'logout']);
$app->router->post('/post/publier', [AuthController::class, 'postCreate']);
$app->router->post('/post/modifier/{?int id}', "update(int $id = null)");
$app->router->post('/post/supprimer/{int id}', "delete(int $id)");

$app->run();