<?php
require_once __DIR__ . '/session.php';

class Lang {
    private static $translations = [];
    private static $loaded = false;
    private static $defaultLang = 'en';
    
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        $translations = require __DIR__ . '/lang_data.php';
        self::$translations = $translations;
        self::$loaded = true;
    }
    
    public static function setLang($lang) {
        self::init();
        if (in_array($lang, ['en', 'np'])) {
            $_SESSION['lang'] = $lang;
        }
    }
    
private static function init() {
        self::load();
    }

public static function getLang() {
        return $_SESSION['lang'] ?? self::$defaultLang;
    }
    
    public static function get($key, $lang = null) {
        self::load();
        
        $lang = $lang ?? self::getLang();
        
        if (isset(self::$translations[$lang][$key])) {
            return self::$translations[$lang][$key];
        }
        
        if (isset(self::$translations['en'][$key])) {
            return self::$translations['en'][$key];
        }
        
        return $key;
    }
    
    public static function getAll($lang = null) {
        self::load();
        $lang = $lang ?? self::getLang();
        return self::$translations[$lang] ?? self::$translations['en'];
    }
    
    public static function getAvailableLangs() {
        return [
            'en' => 'English',
            'np' => 'नेपाली'
        ];
    }
}

function lang($key, $lang = null) {
    return Lang::get($key, $lang);
}

function getLang() {
    return Lang::getLang();
}

function setLang($lang) {
    Lang::setLang($lang);
}