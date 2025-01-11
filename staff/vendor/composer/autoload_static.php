<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1bae9e80265f85709f7235955fd3024a
{
    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Twilio\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Twilio\\' => 
        array (
            0 => __DIR__ . '/..' . '/twilio/sdk/src/Twilio',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1bae9e80265f85709f7235955fd3024a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1bae9e80265f85709f7235955fd3024a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1bae9e80265f85709f7235955fd3024a::$classMap;

        }, null, ClassLoader::class);
    }
}
