<?php
namespace Concrete\Package\ListFiles\Controller\Dialog;

use Block;
use Core;
use Controller;
use Database;
use File;
use Request;
use URL;

defined('C5_EXECUTE') or die("Access Denied.");

class FileProperties extends Controller
{

    protected $viewPath = '/dialogs/file_properties';

    public function view($fID, $bID)
    {
        $b = is_numeric($bID) ? Block::getByID($bID) : null;
        $f = is_numeric($fID) ? File::getByID($fID) : null;
        
        $fileAttributes = array();
        
        $error = t('Invalid file entered.');
        if (is_object($b) && is_object($f)) {
            $db = Database::getActiveConnection();
            $res = $db->GetAll("SELECT * FROM btListFilesData WHERE bID =? AND fID =?", arraY($b->getBlockID(), $f->getFileID()));
            if (is_array($res) && sizeof($res) > 0) {
                $row = array_shift($res);
                $data = @unserialize($row['attributesData']);
                if (is_array($data)) {
                    foreach ($data as $valueA) {
                        $value = $valueA['value'];
                        if ($valueA['store'] == 'serialized') {
                            $value = unserialize($value);
                        }
                        if ($valueA['type'] == 'field') {
                            $fileAttributes[$valueA['name']] = $value;
                        } else if ($valueA['type'] == 'attribute') {
                            $fileAttributes[$valueA['akID']] = $value;
                        }
                    }
                }
            }
            $this->set('fileAttributes', $fileAttributes);
        } else if (!is_object($b)) {
            $error = t('Invalid block.');
            $f = null;
        }
        $this->set('error', $error);
        $this->set('f', $f);
        $this->set('submitURL', URL::to('/ccm/list_files/dialogs/file_properties/save'));
    }

    public function save()
    {
        $bID = $this->post('bID');
        $fID = $this->post('fID');
        $b = is_numeric($bID) ? Block::getByID($bID) : null;
        $f = is_numeric($fID) ? File::getByID($fID) : null;
        
        $ret = array('success' => 0);
        if (is_object($b) && is_object($f)) {
            $fvr = $f->getRecentVersion();
            $fva = $f->getApprovedVersion();
            // We'll create a temp version to get the attribute values to be
            // stored for this block.
            $fv = $fvr->duplicate();
            if ($fv->isApproved()) {
                $fva->approve();
            }
            $fv->deny();
            $ret['success'] = 1;

            $fields = array();

            if (strlen($title = $this->post('fvTitle'))) {
                $fields[] = array('type' => 'field', 'name' => 'title', 'value' => $title);
            }
            
            if (strlen($description = $this->post('fvDescription'))) {
                $fields[] = array('type' => 'field', 'name' => 'description', 'value' => $description);
            }

            foreach ($attributeKeys as $key) {
                $key->saveAttributeForm($fv);
                $value = $fv->getAttribute($key);
                $type = 'text';
                if (is_object($value)) {
                    $ser = @serialize($value);
                    $arr = (array)$value;
                    if (is_object(@unserialize($ser))) {
                        $value = $ser;
                        $type = 'serialized';
                    } else if (is_array($arr)) {
                        $value = serialize($arr);
                        $type = 'serialized';
                    } else if (method_exists($value, 'toString')) {
                        $value = $value->toString();
                    } else if (method_exists($value, '__toString')) {
                        $value = $value->__toString();
                    } else {
                        $value = "";
                        $type = 'invalid';
                    }
                }
                $fields[] = array('type' => 'attribute', 'akID' => $key->getAttributeKeyID(), 'value' => $value, 'store' => $type);
            }

            // Delete the temp file version
            $fv->delete();

            // Save the data
            $db->Replace('btListFilesData', array(
                'bID' => $b->getBlockID(),
                'fID' => $f->getFileID(),
                'attributesData' => serialize($fields),
            ), array('bID', 'fID'), true);
        }

        Core::make('helper/ajax')->sendResult($ret);
    }

}