<?php
namespace Project\Core;

use Project\Controllers\PagesController;
use  Project\Core\{Response,Request,Controller};
use Project\Core\Exceptions\NotFoundException;

class Router {
    private array $controllers;
    public Request $request;
    public Response $response;
    protected array $routes = [];

    public function __construct(array $url_accessible_controllers){
        $this->request = new Request();
        $this->response = new Response();
        $this->controllers = $url_accessible_controllers;
    }

    public function get($path, $callback){
        $this->routes['get'][$path] = $callback;
    }
    public function post($path, $callback){
        $this->routes['post'][$path] = $callback;
    }
    public function resolve(){
        $controller = Application::$app->controller;
        $path = $this->request::getUrl();
        $method = $this->request->method();
        //Check if there is a defined callback for this request
        $callback = $this->callbackForPath($method,$path);
        
        //If not defined
        if($callback === false){
            throw new NotFoundException();
        }
        if (is_string($callback) || is_object($callback)){
            //Check if a valid controller is passed by th url
            if(preg_match_all('/\/([^\/]*)/',$path,$sections)){
                if(in_array($sections[1][0],array_keys($this->controllers))){
                    $callbackArray[0] = $this->controllers[$sections[1][0]];
                }
                if (is_string($callback)) {
                    $callbackArray[1] = $callback;
                }else{
                    $callbackArray[1] = $callback->action;
                    $param = $callback->param;  
                }


                $callback = $callbackArray;
            }
        }
        if (is_array($callback)){
            //create a callable Controller instance from the called class and register it as current cotroller for the app;
            $controller = new $callback[0]();
            $controller->action = $callback[1];
            $callback[0] = $controller;
            // check if authorized
            foreach($controller->getMiddlewares() as $middleware){
                $middleware->execute();
            }
        }
        // run callback
        return call_user_func($callback, $this->request, $param??0);
    }
    private function callbackForPath(string $method, string $path){
        if(!isset($this->routes[$method][$path])){
            // Check if the given path fits dynamic path
            if(preg_match('/\/(\d+)/',$path,$numParam)){
                $path = str_replace('/','\/',$path);
                $dynamicPattern = str_replace($numParam[1],"{int\s(\\$[a-zA-z_]*)}","/^$path/");
                foreach($this->routes[$method] as $key => $value) {
                    if(preg_match($dynamicPattern,$key,$match)){
                        return (object)[
                            'action' => $value,
                            'param' => $numParam[1]
                        ];
                    }
                }
            }
            //Given path does not exist
            return false; 
        }
        // Given path is static
        return $this->routes[$method][$path];

    }
    
}
