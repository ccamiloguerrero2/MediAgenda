<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit43d4efed67ca00d6a232a75f891f810d
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MediAgenda\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MediAgenda\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit43d4efed67ca00d6a232a75f891f810d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit43d4efed67ca00d6a232a75f891f810d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit43d4efed67ca00d6a232a75f891f810d::$classMap;

        }, null, ClassLoader::class);
    }
}
