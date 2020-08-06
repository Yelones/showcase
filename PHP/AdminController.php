<?php

namespace App\Http\Controllers;

use Str;
use File;
use Auth;
// use Request;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManager as Image;

class AdminController extends Controller
{
    protected $model;
    protected $dir;

    public function __construct()
    {
        $this->dir = request()->segment(2);
        $modelName = Str::studly($this->dir);
        $this->model = 'App\Models\\'.$modelName;
        
        if (!class_exists($this->model)) {
            $this->model = 'App\Models\\'. Str::studly(Str::before(request()->segment(2), '-')) .'\\'.$modelName;
        }
    }

    public function index(Request $request)
    {
    	return view('admin.dashboard');
    }

    public function checkPermission($permission)
    {
        return Auth::user()->canDo($this->dir.' '.$permission);
    }

    public function error()
    {
        return view('admin.404');
    }

    public function routeManagement($url, Request $request)
    {
    	$url = explode('/', $url); // Path with no domain, no params (array)
    	$params = request()->input(); // GET parameters and its values (associative array)
    	$methodType = mb_strtolower(request()->method()); // Method of the request (GET or POST)
        $methodName = request()->segment(3);
    	$controller = '\App\Http\Controllers\Admin\\' . Str::studly(request()->segment(2)) . 'Controller';

        // If class (controller) doesn't exist, then return to not found page
        if (!class_exists($controller)) {
            return $this->error();
        }

        $object = new $controller;
    	// If there is no method name in url, then look for index else return to not found page
    	if (!$methodName) {
    		$method = $methodType . 'Index';
    		if (method_exists($object, $method) && $this->checkPermission('index')) {
    			return $object->$method($request);
    		} else {
                return $this->error();
    		}
    	// If there is method name in url, then look for it else return to not found page
    	} elseif ($methodName && ($this->checkPermission($methodName) || (request()->ajax() && Auth::user()->admin()))) {
    		$method = $methodType . Str::title($methodName);
    		if (method_exists($object, $method)) {
                if (request()->isMethod('post')) {
                    return $object->$method($request);
                }
    			return $object->$method();
    		} else {
    			return $this->error();
    		}
    	// Url is either too long or too short to identify the correct behavior so we just return to not found page
    	} else {
            return $this->error();
    	}
    }
}
