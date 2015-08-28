<?php
namespace Concrete\Package\ListFiles\Src\File;

use File;

class Proxy {

    public $file;
    public $attributesData = array();

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function getTitle()
    {
        if (isset($this->attributesData['title'])) {
            return $this->attributesData['title'];
        }
        return $this->file->getTitle();
    }

    public function getDescription()
    {
        if (isset($this->attributesData['description'])) {
            return $this->attributesData['description'];
        }
        return $this->file->getDescription();
    }

    public function getAttribute($attribute)
    {
        $key = $attribute;
        if (is_object($attribute)) {
            $key = $attribute->getAttributeKeyHandle();
        }
        if (isset($this->attributesData[$key])) {
            return $this->attributesData[$key];
        }
        return $this->file->getAttribute($attribute);
    }

    public function __call($func, $args)
    {
        return call_user_func_array(array($this->file, $func), $args);
    }

}