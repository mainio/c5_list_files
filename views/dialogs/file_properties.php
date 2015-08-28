<?php defined('C5_EXECUTE') or die("Access Denied.");

use BlockType;
use Core;

$form = Core::make('helper/form');
$ih = Core::make('helper/concrete/ui');

?>

<div class="ccm-ui">
<?php if (is_object($f)) :
    $fva = $f->getApprovedVersion();
    
    // Create a temp file version to show the properties correctly on the form
    // below.
    $fv = $fva->duplicate();
    if ($fv->isApproved()) {
        $fva->approve();
    }
    
    $title = $fv->getTitle();
    if (isset($fileAttributes['title'])) {
        $title = $fileAttributes['title'];
    }
    $description = $fv->getDescription();
    if (isset($fileAttributes['description'])) {
        $description = $fileAttributes['description'];
    }
    foreach ($attributeKeys as $key) {
        $akID = $key->getAttributeKeyID();
        if (isset($fileAttributes[$akID])) {
            // Use try & catch to prevent possible errors from attributes that
            // do not handle e.g. null or 0-case properly in the saveForm
            // method. None of the core attribute types throw an error here but
            // some custom attribute types might.
            try {
                $fv->setAttribute($key, $fileAttributes[$akID]);
            } catch (Exception $e) {
                
            }
        }
    }
    
    
    ?>
    <form class="file-attributes-form" method="POST" action="<?php echo $submitURL ?>">
        <input type="hidden" name="bID" value="<?php echo $b->getBlockID() ?>" />
        <input type="hidden" name="fID" value="<?php echo $f->getFileID() ?>" />
        <div class="fields">
            <div class="clearfix">
                <label><?php echo t('Title') ?></label>
                <div class="input">
                    <?php echo $form->text('fvTitle', $title) ?>
                </div>
            </div>
            <div class="clearfix">
                <label><?php echo t('Description') ?></label>
                <div class="input">
                    <?php echo $form->textarea('fvDescription', $description) ?>
                </div>
            </div>
            <?php foreach ($attributeKeys as $key) : 
                $value = $fv->getAttributeValueObject($key);
                ?>
            <div class="clearfix">
                <?php echo $key->render('label'); ?>
                <div class="input">
                    <?php echo $key->render('form', $value, true)?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="attributes-actions dialog-buttons">
            <?php echo $ih->button(t('Cancel'), '#close-dialog', 'left', 'cancel'); ?>
            <?php echo $ih->submit(t('Save'), 'submit', 'right', 'primary'); ?>
        </div>
    </form>
</div>
<?php
    // Delete the temp file version
    $fv->delete();
?>
<?php else : ?>
    <p><?php echo $error ?></p>
    <div class="attributes-actions dialog-buttons">
        <?php echo $ih->button(t('Close'), '#close-dialog', 'left', 'cancel'); ?>
    </div>
<?php endif; ?>
</div>
