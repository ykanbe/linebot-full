<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd60279f70b7e6ae6a4c02f8d80e5a832
{
    public static $prefixesPsr0 = array (
        'U' => 
        array (
            'Unirest' => 
            array (
                0 => __DIR__ . '/..' . '/mashape/unirest-php/lib',
            ),
        ),
        'S' => 
        array (
            'Smtpapi' => 
            array (
                0 => __DIR__ . '/..' . '/sendgrid/smtpapi/lib',
            ),
            'SendGrid' => 
            array (
                0 => __DIR__ . '/..' . '/sendgrid/sendgrid/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInitd60279f70b7e6ae6a4c02f8d80e5a832::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
