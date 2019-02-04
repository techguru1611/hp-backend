<?php

namespace App\Helpers;
use File;
use Image;

Class ImageUpload {
    
    /**
     * To upload image with creating thumb
     * @param File $file
     * @param array $params contain ['originalPath', 'thumbPath', 'thumbHeight', 'thumbWidth', 'previousImage']
     */
    public static function uploadWithThumbImage($file, $params) {
        try {
            if (!empty($file) && !empty($params)) {
                $name = str_random(20). '.' . $file->getClientOriginalExtension();
                
                $originalPath = $params['originalPath'] . $name;
                $thumbPath = $params['thumbPath'] . $name;
                
                if (!file_exists($params['originalPath'])) File::makeDirectory($params['originalPath'], 0777, true, true);
                if (!file_exists($params['thumbPath'])) File::makeDirectory($params['thumbPath'], 0777, true, true);

                // created instance
                $img = Image::make($file->getRealPath());
                $img->save($originalPath);
                
                // resize the image to a height of $this->contestThumbImageHeight and constrain aspect ratio (auto width)
                $imgHeight = ($img->height() < $params['thumbHeight']) ? $img->height(): $params['thumbHeight'];
                
                $img->resize(null, $imgHeight, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($thumbPath);
                    
                if ($params['previousImage'] != '') {
                    $originalImage = $params['originalPath']. $params['previousImage'];
                    $thumbImage = $params['thumbPath'] . $params['previousImage'];
                    if (file_exists($originalImage)) {
                        File::delete($originalImage);
                    }
                    if (file_exists($thumbImage)) {
                        File::delete($thumbImage);
                    }
                }
                return [
                    'imageName' => $name
                ];
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * To upload image 
     * @param File $file
     * @param array $params contain ['originalPath', 'previousImage']
     */
    public static function uploadImage($file, $params) {
        try {
            if (!empty($file) && !empty($params)) {
                $name = str_random(20). '.' . $file->getClientOriginalExtension();
                
                $originalPath = $params['originalPath'] . $name;
                
                if (!file_exists($params['originalPath'])) File::makeDirectory($params['originalPath'], 0777, true, true);

                // created instance
                $file->move($params['originalPath'], $name);
                
                if ($params['previousImage'] != '') {
                    $originalImage = $params['originalPath']. $params['previousImage'];
                    if (file_exists($originalImage)) {
                        File::delete($originalImage);
                    }
                }
                return [
                    'imageName' => $name
                ];
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * To delete image 
     * @param array $params contain ['originalPath', 'thumbPath']
     */
    public static function deleteImage($params) {
        try {
            if ($params['imageName'] != '') {
                $originalImage = $params['originalPath']. $params['imageName'];
                $thumbImage = $params['thumbPath'] . $params['imageName'];
                if (file_exists($originalImage)) {
                    File::delete($originalImage);
                }
                if (file_exists($thumbImage)) {
                    File::delete($thumbImage);
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * To upload image with creating thumb. It's using laravel inbuilt STORAGE facility to upload images.
     * 
     * @param File $file
     * @param array $params contain ['originalPath', 'thumbPath', 'thumbHeight', 'thumbWidth', 'previousImage']
     * 
     * NOTE: originalPath and thumbPath path MUST be start with "PUBLIC" folder. Otherwise Fill will not uploaded.
     */
    public static function storageUploadWithThumbImage($file, $params) {
        try {
            if (!empty($file) && !empty($params)) {
                $name = str_random(20). '.' . $file->getClientOriginalExtension();
                
                $originalPath = $params['originalPath'] . $name;
                $thumbPath = $params['thumbPath'] . $name;
                
                // created instance
                $img = Image::make($file->getRealPath());

                $filePath = \Storage::putFileAs($params['originalPath'], ($file), $name);             
                \Storage::setVisibility($filePath, 'public');
                
                // resize the image to a height of $this->contestThumbImageHeight and constrain aspect ratio (auto width)
                $imgHeight = ($img->height() < $params['thumbHeight']) ? $img->height(): $params['thumbHeight'];
                
                $img->resize(null, $imgHeight, function ($constraint) {
                    $constraint->aspectRatio();
                });

                \Storage::put($thumbPath, (string) $img->encode());                                  
                \Storage::setVisibility($thumbPath, 'public');
                    
                if ($params['previousImage'] != '') {
                    $originalImage = $params['originalPath']. $params['previousImage'];
                    $thumbImage = $params['thumbPath'] . $params['previousImage'];
                    if (\Storage::exists($originalImage)) {
                        \Storage::delete($originalImage);
                    }
                    if (\Storage::exists($thumbImage)) {
                        \Storage::delete($thumbImage);
                    }
                }
                $img->destroy();
                return [
                    'imageName' => $name
                ];
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
