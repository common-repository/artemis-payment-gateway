<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit618ac92bc5e20dcb3437e9ba165af421
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        '3109cb1a231dcd04bee1f9f620d46975' => __DIR__ . '/..' . '/paragonie/sodium_compat/autoload.php',
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'c' => 
        array (
            'chillerlan\\Settings\\' => 20,
            'chillerlan\\QRCode\\' => 18,
        ),
        'Y' => 
        array (
            'Yosymfony\\Toml\\' => 15,
            'Yosymfony\\ParserUtils\\' => 22,
        ),
        'S' => 
        array (
            'Soneso\\StellarSDK\\' => 18,
            'Soneso\\StellarSDKTests\\' => 23,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'E' => 
        array (
            'Endroid\\QrCode\\' => 15,
        ),
        'D' => 
        array (
            'DASPRiD\\Enum\\' => 13,
        ),
        'C' => 
        array (
            'Cryptopayment\\Cryptopayment\\' => 28,
        ),
        'B' => 
        array (
            'Base32\\' => 7,
            'BaconQrCode\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'chillerlan\\Settings\\' => 
        array (
            0 => __DIR__ . '/..' . '/chillerlan/php-settings-container/src',
        ),
        'chillerlan\\QRCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/chillerlan/php-qrcode/src',
        ),
        'Yosymfony\\Toml\\' => 
        array (
            0 => __DIR__ . '/..' . '/yosymfony/toml/src',
        ),
        'Yosymfony\\ParserUtils\\' => 
        array (
            0 => __DIR__ . '/..' . '/yosymfony/parser-utils/src',
        ),
        'Soneso\\StellarSDK\\' => 
        array (
            0 => __DIR__ . '/..' . '/soneso/stellar-php-sdk/Soneso/StellarSDK',
        ),
        'Soneso\\StellarSDKTests\\' => 
        array (
            0 => __DIR__ . '/..' . '/soneso/stellar-php-sdk/Soneso/StellarSDKTests',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-factory/src',
            1 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Endroid\\QrCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/endroid/qr-code/src',
        ),
        'DASPRiD\\Enum\\' => 
        array (
            0 => __DIR__ . '/..' . '/dasprid/enum/src',
        ),
        'Cryptopayment\\Cryptopayment\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Base32\\' => 
        array (
            0 => __DIR__ . '/..' . '/christian-riesen/base32/src',
        ),
        'BaconQrCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/bacon/bacon-qr-code/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit618ac92bc5e20dcb3437e9ba165af421::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit618ac92bc5e20dcb3437e9ba165af421::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit618ac92bc5e20dcb3437e9ba165af421::$classMap;

        }, null, ClassLoader::class);
    }
}
