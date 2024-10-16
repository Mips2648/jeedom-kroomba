<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6c9124a25aeada9064704f86d5c25a73
{
    public static $prefixesPsr0 = array (
        'M' => 
        array (
            'MipsEqLogicTrait' => 
            array (
                0 => __DIR__ . '/..' . '/mips/jeedom-tools/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'MipsEqLogicTrait' => __DIR__ . '/..' . '/mips/jeedom-tools/src/MipsEqLogicTrait.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit6c9124a25aeada9064704f86d5c25a73::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit6c9124a25aeada9064704f86d5c25a73::$classMap;

        }, null, ClassLoader::class);
    }
}
