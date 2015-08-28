<?php
namespace Concrete\Package\ListFiles\Controller\Backend;

use Block;
use Concrete\Core\File\Set\Set as FileSet;
use Core;
use Controller;
use Database;
use Exception;
use File;
use Request;

defined('C5_EXECUTE') or die("Access Denied.");

class ListFiles extends Controller
{

    public function fileDetails($fID, $bID)
    {
        if (is_object($f = File::getByID($fID))) {
            // TODO: Check permissions for the File
            $db = Database::getActiveConnection();
            $data = $db->GetAll('SELECT * FROM btListFilesData WHERE fID =? AND bID =?', array($f->getFileID(), $bID));
            if (is_array($data) && sizeof($data) > 0) {
                $row = array_shift($data);
                $valueA = @unserialize($row['attributesData']);
                if (is_array($valueA)) {
                    $title = $f->getTitle();
                    $description = $f->getDescription();
                    foreach ($valueA as $val) {
                        if ($val['name'] == 'title') {
                            $title = $val['value'];
                        } else if ($val['name'] == 'description') {
                            $description = $val['value'];
                        }
                    }
                    Core::make('helper/ajax')->sendResult(array(
                        'fID' => $f->getFileID(),
                        'title' => $title,
                        'description' => $description,
                    ));
                }
            } else {
                Core::make('helper/ajax')->sendResult(array(
                    'fID' => $f->getFileID(),
                    'title' => $f->getTitle(),
                    'description' => $f->getDescription(),
                ));
            }
        } else {
            throw new Exception(t('Invalid file.'));
        }
    }

    public function fileSetDetails($fsID)
    {
        if (is_object($fs = FileSet::getByID($fsID))) {
            // TODO: Check permissions for the File Set
            $db = Database::getActiveConnection();
            $fileIDs = $db->GetCol('SELECT fID FROM FileSetFiles WHERE fsID =? ORDER BY fsDisplayOrder,fsfID', array($fs->getFileSetID()));
            
            Core::make('helper/ajax')->sendResult(array(
                'fsID' => $fs->getFileSetID(),
                'fsName' => $fs->getFileSetName(),
                'uID' => $fs->getFileSetUserID(),
                'fsType' => $fs->getFileSetType(),
                'files' => $fileIDs
            ));
        } else {
            throw new Exception(t('Invalid file set.'));
        }
    }

}