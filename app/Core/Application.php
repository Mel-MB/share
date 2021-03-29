<?php
namespace Project\Core;
use  Project\Core\{Response,Request,Router,Database,Manager,Entity};
use Project\Entities\User;

class Application{
    public static string $ROOT_DIR;
    public static ?Application $app;
    public Session $session;
    public Controller $controller;
    public Router $router;
    public Database $db;

    public function __construct(string $rootPath,array $config){
        self::$ROOT_DIR     = $rootPath;
        self::$app          = $this; //allows to call actual instance of class app anywhere
        $this->router       = new Router();
        $this->db           = new Database($config['db']);
        $this->session      = new Session();
    }

    // Instantiation methods    
    public function setController(Controller $controller): object{
        return $this->controller = $controller;
    }

    // Router exceptions handdling
    public function run(){
        try{
            echo $this->router->resolve();
        } catch( \Exception $e) {
            if($e->getCode() === 404){
                $this->router->response->setStatusCode($e->getCode());
                $this->setController(new Controller());
                echo $this->controller->render('error', ['exception' => $e]);
                exit;
            }
            Application::$app->session->setFlash('error',$e->getMessage());
            header("Location: ".$e->redirectLink);
        }
    }

    //Session related method
    public static function isGuest(): bool{
        return !self::$app->session->get('id');
    }
}