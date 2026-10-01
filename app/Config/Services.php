<?php

namespace Config;

use App\Libraries\Vista;
use CodeIgniter\Config\BaseService;
use Config\View as ViewConfig;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    /**
     * El dibujador de vistas: el de CodeIgniter con la carga automática del
     * CSS de cada vista (ver app/Libraries/Vista.php).
     *
     * Es una copia de renderer() de system/Config/Services.php con un solo
     * cambio: devuelve un Vista en lugar de un View.
     */
    public static function renderer(?string $viewPath = null, ?ViewConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('renderer', $viewPath, $config);
        }

        $viewPath = in_array($viewPath, [null, '', '0'], true) ? (new Paths())->viewDirectory : $viewPath;
        $config ??= config(ViewConfig::class);

        return new Vista($config, $viewPath, static::locator(), CI_DEBUG, static::logger());
    }
}
