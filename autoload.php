<?php
//   This is my sexy autoloader
//　　██░▀██████████████▀░██
//　　█▌▒▒░████████████░▒▒▐█
//　　█░▒▒▒░██████████░▒▒▒░█
//　　▌░▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒░▐
//　　░▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒░
//　 ███▀▀▀██▄▒▒▒▒▒▒▒▄██▀▀▀██
//　 ██░░░▐█░▀█▒▒▒▒▒█▀░█▌░░░█
//　 ▐▌░░░▐▄▌░▐▌▒▒▒▐▌░▐▄▌░░▐▌
//　　█░░░▐█▌░░▌▒▒▒▐░░▐█▌░░█
//　　▒▀▄▄▄█▄▄▄▌░▄░▐▄▄▄█▄▄▀▒
//　　░░░░░░░░░░└┴┘░░░░░░░░░
//　　██▄▄░░░░░░░░░░░░░░▄▄██
//　　████████▒▒▒▒▒▒████████
//　　█▀░░███▒▒░░▒░░▒▀██████
//　　█▒░███▒▒╖░░╥░░╓▒▐█████
//　　█▒░▀▀▀░░║░░║░░║░░█████
//　　██▄▄▄▄▀▀┴┴╚╧╧╝╧╧╝┴┴███
//　　██████████████████████
spl_autoload_register(function ($class) {
    $prefix = 'Leedch\\Trakt';

    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    
    //Does it match the prefix?
    if(strncmp($prefix, $class, $len) !== 0){
        //nope, fuck off and use a different autoloader
        return;
    }

    $relative_class = substr($class, $len);

    //Replace namespace with directory
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    //if exists, require
    if (file_exists($file)) {
        require $file;
    }
});