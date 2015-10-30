<?php defined('C5_EXECUTE') or die("Access Denied.");

$interface = Core::make('helper/concrete/ui');
$bt = BlockType::getByHandle('list_files');

?>
<script type="text/javascript">
var CCM_LIST_FILES_PROPERTIES_URL = '<?php echo URL::to('/ccm/list_files/dialogs/file_properties') ?>';
var CCM_LIST_FILES_FILE_DETAILS_URL = '<?php echo URL::to('/ccm/list_files/details/file') ?>';
var CCM_LIST_FILES_FILESET_DETAILS_URL = '<?php echo URL::to('/ccm/list_files/details/file_set') ?>';
var CCM_LIST_FILES_BID = '<?php echo strlen($bID) ? $bID : 'x' ?>';
</script>
<style type="text/css">
#ccm-files-container .file-row .mover,
#ccm-files-container .file-row .remover,
#ccm-files-container .file-row .editor {
    float: right;
    margin-left: 10px;
    width: 16px;
    height: 16px;
    font-size: 1.1em;
    cursor: pointer;
}
#ccm-files-container .file-row .mover {
    cursor: move;
}
#ccm-files-container .file-row {
    padding: 5px;
    background: #ffffff;
    margin-bottom: 10px;
    border: 1px solid #efefef;
    -webkit-border-radius: 3px;
    border-radius: 3px;
}
#ccm-files-container .file-row .file-preview {
    float: left;
    width: 45px;
}
#ccm-files-container .file-row .file-name {
    margin-left: 70px;
}
</style>
<fieldset>
    <?php if (sizeof($fileSets) > 0) : ?>
        <div style="padding-top:5px;">
            <?php echo $form->select('list_type', array(
                'single' => t('Single Files'),
                'set' => t('File Set'),
            ), $fileSetMode ? 'set' : 'single'); ?>
        </div>
    <?php else : ?>
        <input type="hidden" name="list_type" value="single" />
    <?php endif; ?>

    <div id="file-selectors" style="padding-top:5px;">
        <div class="file-selector file-selector-single"<?php if ($fileSetMode) echo ' style="display:none;"' ?>>
            <?php echo $interface->button('<span class="fa fa-plus-circle"></span> ' . t('Add File'), '#add-file', 'right', 'btn-success', array('id' => 'file-adder')); ?>
            <div id="list-files-file-manager-filters" class="filters" style="display:none;">
                <?php if (false) : // this is how to limit the file type to a specific one ?>
                <input type="hidden" class="ccm-file-manager-filter" name="fType" value="<?php echo FileType::T_IMAGE ?>" />
                <?php endif; ?>
            </div>
        </div>
        <div class="file-selector file-selector-set"<?php if (!$fileSetMode) echo ' style="display:none;"' ?>>
            <?php if (sizeof($fileSets) > 0) : ?>
                <?php echo $form->select('fsID', (array(0 => t('** Choose Set')) + $fileSets), $fsID); ?>
            <?php else : ?>
                <input type="hidden" name="fsID" value="0" />
            <?php endif; ?>
        </div>
    </div>
</fieldset>

<fieldset>
    <legend><?php echo t('Files') ?></legend>
    <div class="ccm-files-section" id="ccm-files-container">
        <input type="hidden" name="selectedFileIDs" value="<?php echo implode(',', $fileIDs) ?>" />
    </div>
</fieldset>

<script type="text/javascript">
$(function() {
    ListFilesBlockHelper.init();
});
</script>
