<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0a6c6705de6a2923ed3a492552062bc3
{
    public static $files = array (
        'a21e5240db73c2d137f8170129a25dce' => __DIR__ . '/../..' . '/helpers/helper.php',
    );

    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
        'D' => 
        array (
            'DirectoristAppToolkit\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
        'DirectoristAppToolkit\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0a6c6705de6a2923ed3a492552062bc3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0a6c6705de6a2923ed3a492552062bc3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0a6c6705de6a2923ed3a492552062bc3::$classMap;

        }, null, ClassLoader::class);
    }
}
