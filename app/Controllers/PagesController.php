<?php

namespace Project\Controllers;

use Project\Core\{Application, Controller, Request};
use Project\Entities\{Post, User};
use Project\Models\Tag;

class PagesController extends Controller{
    public function home(){
        // Research bar
        $mostUsedTags = Tag::selectTop5();
        //Retrieve all posts
        $posts= Post::all();
        // Page data
         $data = [
            'tags' => $mostUsedTags,
            'posts' => $posts
         ];
         return self::render('front/home',$data);
    }


    public function login(Request $request){
        $user = new User;
        $user->rules = [
            'username' => [User::RULE_REQUIRED, [User::RULE_MIN, 'min'=>3], [User::RULE_MAX, 'max'=>18]],
            'password' => [User::RULE_REQUIRED, [User::RULE_MIN, 'min'=>8]]
        ];

        if ($request->isPost()){
            $user->populate($request->getData());
 
            if($user->validate()){
                if($user->connect()){
                    Application::$app->session->setFlash('success', "Vous êtes connecté");
                    header('Location: /');
                    exit;
                }
                Application::$app->session->setFlash('error', "Pseudo ou mot de passe incorrect");
            }
        }    
        
        // Page data
        $data = [
            'title' => 'Se connecter',
            'description' => "Accès à l'espace personnel de partage et classification de ressources de étudiants de Kercode",
            'user' => $user,
        ];
        
        return self::render('front/login',$data);
    }
    public function register(Request $request){
        $user = new User;
        $user->rules = [
            'username' => [User::RULE_REQUIRED, User::RULE_ALPHANUM, [User::RULE_MIN, 'min'=>3], [User::RULE_MAX, 'max'=>18], User::RULE_UNIQUE],
            'email' => [User::RULE_REQUIRED, User::RULE_EMAIL, User::RULE_UNIQUE],
            '' => [User::RULE_REQUIRED, User::RULE_YEAR],
            'password' => [User::RULE_REQUIRED, [User::RULE_MIN, 'min'=>8]],
            'password_confirm' => [User::RULE_REQUIRED, [User::RULE_MATCH, 'match'=> 'password']]
        ];
        
        if($request->isPost()){
            $user->populate($request->getData());

            if ($user->validate()){
                if($user->create()){
                    Application::$app->session->setFlash('success', 'Vous êtes inscrit');
                    header('Location: /se-connecter');
                    exit;
                }
                Application::$app->session->setFlash('error', "Une erreur s'est produite, veuillez réessayer plus tard");
            }           
        }

        // Page data
        $data = [
            'title' => "S'inscrire",
            'description' => "Accès à l'espace personnel de partage et classification de ressources de étudiants de Kercode",
            'user' => $user,
        ];

        return self::render('front/register',$data);
    }
}