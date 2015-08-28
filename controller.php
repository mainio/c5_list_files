<?php 
namespace Concrete\Package\ListFiles;

use Concrete\Package\ListFiles\Src\PackageRouteProvider;
use BlockType;
use Package;

defined('C5_EXECUTE') or die("Access Denied.");

class Controller extends Package
{

    protected $pkgHandle = 'list_files';
    protected $appVersionRequired = '5.7.1';
    protected $pkgVersion = '0.8.0';

    public function getPackageName()
    {
        return t("List Files");
    }

    public function getPackageDescription()
    {
        return t("List selected files inside a block.");
    }

    public function on_start()
    {
        PackageRouteProvider::registerRoutes();
    }

    public function install()
    {
        $pkg = parent::install();
        BlockType::installBlockTypeFromPackage('list_files', $pkg);
    }

}