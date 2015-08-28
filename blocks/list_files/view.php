<?php  defined('C5_EXECUTE') or die("Access Denied.");
$uh = Loader::helper('concrete/urls');
$c = Page::getCurrentPage();
?>
<div class="files-list-block">
<?php foreach ($files as $fobj) :
     $file = $fobj->file;
     $title = $file->getTitle(); // The file title set from the file manager
     $name = $file->getFileName(); // The actual file name for the file
     $downloadURL = View::url('/download_file', $file->getFileID(), $c->getCollectionID());
?>
    <div class="file">
        <div class="file-title"><a href="<?php echo $downloadURL ?>"><?php echo $title ?></a></div>
    </div>
<?php endforeach; ?>
</div>