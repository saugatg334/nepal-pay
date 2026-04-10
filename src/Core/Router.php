<?php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $currentRoute;

    public function __construct() {
        $this->routes = [
            'GET' => [],
            'POST' => []
        ];
    }

    public function get($path
