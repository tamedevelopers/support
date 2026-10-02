<?php

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Capsule\FileBag;
use Tamedevelopers\Support\Tame;

include_once __DIR__  . "/../vendor/autoload.php";

Env::bootLogger();


$fileDocument = FileBag::collect('document');
$fileAvatar = FileBag::collect('avatar');

dump(
    $fileAvatar->all(),
    $fileAvatar->isset(),
    $fileAvatar->valid()->first(),
    // TameFileBag('document')->valid(),
    // $fileAvatar->valid()->first()->isImage(),
    // $fileDocument,
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <?= FileBag::publishJS(); ?>
    <?= FileBag::publishMaxSizeJS(); ?>
</head>
<body>
    <center>
        <form method="post" enctype="multipart/form-data">
            
                <h3 class="valign-wrapper prod_hding_main mb-3">Upload file</h3>
                
                <!--file upload-->
                <div class="col-sm-12 mt-3">
                    <div class="form-group" style="margin-bottom: 30px;">
                        <label for="upload" style="display: block; margin-bottom: 10px;">
                            Attachment Document
                        </label>
                        <input type="file" class="form-control-file" name="document[]" multiple>
                    </div>
                    <div class="form-group">
                        <label for="upload" style="display: block; margin-bottom: 10px;">
                            Avatar Image
                        </label>
                        <input type="file" class="form-control-file" name="avatar" multiple>
                    </div>
                </div>

                <button type="submit" style="margin-top: 40px;">
                    Upload File
                </button>
            
        </form>
    </center>
</body>
</html>