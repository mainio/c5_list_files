<?php  
namespace Concrete\Package\ListFiles\Src;

use Route;

defined('C5_EXECUTE') or die("Access Denied.");

class PackageRouteProvider
{
    public static function registerRoutes()
    {
        Route::register('/ccm/list_files/details/file/{fID}/{bID}', '\Concrete\Package\ListFiles\Controller\Backend\ListFiles::fileDetails');
        Route::register('/ccm/list_files/details/file_set/{fsID}', '\Concrete\Package\ListFiles\Controller\Backend\ListFiles::fileSetDetails');

        Route::register('/ccm/list_files/dialogs/file_properties/{fID}/{bID}', '\Concrete\Package\ListFiles\Controller\Dialog\FileProperties::view');
        Route::register('/ccm/list_files/dialogs/file_properties/save', '\Concrete\Package\ListFiles\Controller\Dialog\FileProperties::save');
    }
}