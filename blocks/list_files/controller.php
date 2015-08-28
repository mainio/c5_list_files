<?php
namespace Concrete\Package\ListFiles\Block\ListFiles;

use Concrete\Core\Attribute\Set as AttributeSet;
use Concrete\Core\Block\BlockController;
use Concrete\Core\File\Set\SetList as FileSetList;
use Concrete\Package\ListFiles\Src\File\Proxy as FileProxy;
use Database;
use File;

defined('C5_EXECUTE') or die("Access Denied.");

class Controller extends BlockController
{

    protected $btTable = 'btListFiles';
    protected $btTableFiles = 'btListFilesSelected';
    protected $btTableFilesData = 'btListFilesData';
    protected $btWrapperClass = 'ccm-ui';

    protected $btInterfaceWidth = "500";
    protected $btInterfaceHeight = "450";

    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;

    public function getBlockTypeName()
    {
        return t("List Files");
    }

    public function getBlockTypeDescription()
    {
        return t("Select files from file manager to display on your site.");
    }

    public function getJavaScriptStrings()
    {
        return array(
            'add-files' => t('You must add at least one file to the list.'),
            'file-properties' => t('File Properties')
        );  
    }

    public function view()
    {
        $this->loadFiles();
        $this->set('fileSetMode', $this->fsID > 0);
    }

    public function add()
    {
        $this->requireAsset('core/file-manager');

        $this->loadFileSets();
        $this->set('fileIDs', array());
        $this->set('fsID', 0);
        $this->set('fileSetMode', false);
    }

    public function edit()
    {
        $this->requireAsset('core/file-manager');

        $this->loadFileSets();
        $this->loadFiles();
        $this->set('fileSetMode', $this->fsID > 0);
    }

    public function duplicate($nbID)
    {
        parent::duplicate($nbID);
        $this->loadFiles();
        $files = $this->get('files');
        if (is_array($files)) {
            $db = Database::getActiveConnection();
            foreach ($files as $obj) {
                $db->query('INSERT INTO ' . $this->btTableFiles . ' (bID,fID) VALUES (?,?)', array($nbID, $obj->file->getFileID()));
            }
            foreach ($db->GetAll('SELECT * FROM ' . $this->btTableFilesData . ' WHERE bID =?', array($this->bID)) as $row) {
                $row['bID'] = $nbID;
                $db->Replace($this->btTableFilesData, $row, array('bID', 'fID'), true);
            }
        }
    }

    public function save($data)
    {
        $args = array();
        $args['randomOrder'] = "0";
        $args['fsID'] = $data['list_type'] == 'set' ? $data['fsID'] : "0";
        parent::save($args);

        $db = Database::getActiveConnection();
        $db->query('DELETE FROM ' . $this->btTableFiles . ' WHERE bID =?', array($this->bID));
        $prio = 0;
        foreach ($data['fID'] as $fID) {
            $db->query('INSERT INTO ' . $this->btTableFiles . ' (bID,fID,priority) VALUES (?,?,?)', array($this->bID, $fID, $prio));
            $prio++;
        }
    }

    protected function loadFileSets()
    {
        $fl = new FileSetList();
        $sets = array();
        foreach ($fl->get() as $fs) {
            // TODO: Check permissions for the file set
            $sets[$fs->getFileSetID()] = $fs->getFileSetName();
        }
        $this->set('fileSets', $sets);
    }

    protected function loadFiles()
    {
        if ($this->bID > 0) {
            $db = Database::getActiveConnection();

            $files = array();
            $fileIDs = array();
            $ids = $db->GetCol('SELECT fID FROM ' . $this->btTableFiles . ' WHERE bID =? ORDER BY priority,fID', array($this->bID));

            $attributeSet = AttributeSet::getByHandle('list_files_attributes');
            $attributeKeys = is_object($attributeSet) ? $attributeSet->getAttributeKeys() : array();

            $attributesData = array();
            $data = $db->GetAll('SELECT * FROM ' . $this->btTableFilesData . ' WHERE bID =?', array($this->bID));
            foreach ($data as $row) {
                $adata = @unserialize($row['attributesData']);
                if (is_array($adata)) {
                    foreach ($adata as $valueA) {
                        $value = $valueA['value'];
                        if ($valueA['store'] == 'serialized') {
                            $value = unserialize($value);
                        }
                        if (!is_array($attributesData[$row['fID']])) {
                            $attributesData[$row['fID']] = array();
                        }
                        $key = null;
                        if ($valueA['type'] == 'field') {
                            $key = $valueA['name'];
                        } else if ($valueA['type'] == 'attribute') {
                            $key = $valueA['akID'];
                        }
                        if ($key !== null) {
                            $attributesData[$row['fID']][$key] = $value;
                        }
                    }
                }
            }

            // Turn the attributes to the values to be stored to the file proxy
            // object. This cuts out all the un-existing attributes from the
            // list generated above. In addition, this turns attribute key ID
            // references to attribute handle references.
            $attributes = array();
            foreach ($ids as $fID) {
                $data = $attributesData[$fID];
                if (is_array($data)) {
                    $attributes[$fID] = array();
                    if (isset($data['title'])) {
                        $attributes[$fID]['title'] = $data['title'];
                    }
                    if (isset($data['description'])) {
                        $attributes[$fID]['description'] = $data['description'];
                    }
                    foreach ($attributeKeys as $key) {
                        if (isset($data[$key->getAttributeKeyID()])) {
                            $attributes[$fID][$key->getAttributeKeyHandle()] = $data[$key->getAttributeKeyID()];
                        }
                    }
                }
            }

            // Check whether we're in the file set mode and then go through all the file set files
            // and compare them to the specified ids for this block.
            $fileSetFiles = null;
            if ($this->fsID > 0) {
                $fileSetFiles = $db->GetCol('SELECT fID FROM FileSetFiles WHERE fsID =? ORDER BY fsDisplayOrder,fsfID', array($this->fsID));
            }
            if (is_array($fileSetFiles)) {
                foreach ($ids as $k => $fID) {
                    $fsk = array_search($fID, $fileSetFiles);
                    if ($fsi !== false) {
                        // The file exists in the specified set
                        unset($fileSetFiles[$fsk]);
                    } else {
                        // This file is no longer part of the specified file set
                        unset($ids[$k]);
                    }
                }
                // Finally add all the missing file ids to the end of the list
                $ids = array_merge($ids, $fileSetFiles);
            }
            // Finally get and load the files
            foreach ($ids as $fID) {
                $f = File::getByID($fID);
                if (is_object($f) && !$f->isError()) {
                    $obj = new FileProxy($f);
                    if (isset($attributes[$fID])) {
                        $obj->attributesData = $attributes[$fID];
                    }
                    $files[] = $obj;
                    $fileIDs[] = $f->getFileID();
                }
            }
            $this->set('files', $files);
            $this->set('fileIDs', $fileIDs);
        }
    }

}

