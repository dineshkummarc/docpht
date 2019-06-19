<?php

/**
 * This file is part of the DocPHT project.
 * 
 * @author Valentino Pesce
 * @copyright (c) Valentino Pesce <valentino@iltuobrand.it>
 * @copyright (c) Craig Crosby <creecros@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */
namespace DocPHT\Model;

use DocPHT\Model\VersionModel;
use DocPHT\Lib\DocBuilder;

class BackupsModel extends PageModel
{

    /**
     * checkBackups
     *
     * @param  string $file
     * @param  string $id
     *
     * @return boolean
     */
    public function checkBackups($file_path)
    {
        $zipData = new \ZipArchive(); 
        if ($zipData->open($file_path) === TRUE) {

            (is_bool($zipData->locateName('data/pages.json')) === TRUE) ? $check = FALSE : $check = TRUE; 
            if ($check) {
                $backupPages = json_decode(file_get_contents("zip://".$file_path."#data/pages.json"),true);
                foreach ($backupPages as $pages) {
                    (is_bool($zipData->locateName($pages['pages']['phppath'])) === TRUE || is_bool($zipData->locateName($pages['pages']['jsonpath'])) === TRUE) ? $check = FALSE : $check = TRUE; 
                }
            }
            $zipData->close();
            
            if ($check) { return TRUE; } else { return FALSE; }
        } else {
        
        return FALSE;
        
        }
        
    }
    
    /**
     * getBackups
     *
     * @param  string $id
     *
     * @return array
     */
    public function getBackups()
    {
        $path = 'data/';
        $filePattern = 'DocPHT_Backup_*.zip';

        $versionList = array();
        foreach (glob($path . $filePattern) as $file) {
            $addFile = $this->checkBackups($file);
            if($addFile) array_push($versionList, ['path' => $file, 'date' => filemtime($file)]);
        }
        
        return $this->sortBackups($versionList);
    }
        
    /**
     * sortBackups
     *
     * @param  array $array
     * 
     * @return array boolean
     */
    public function sortBackups($array)
    {
    
        if (!empty($array)) {
            $column = array_column($array, 'date');
            array_multisort($column, SORT_DESC, $array);
            
            return $array;
        } else {
            return FALSE;
        }

    }
        
    /**
     * createBackup
     *
     * 
     * @return boolean
     */
    public function createBackup()
    {
        $this->versionModel = new VersionModel;
        $pages = $this->connect();
        $assets = ['data/pages.json'];
        
        $this->doc = new DocBuilder;
        $filename = 'data/DocPHT_Backup_' . $this->doc->datetimeNow() . '.zip';
        
        foreach($pages as $page) {
            ($this->versionModel->getAssets($page['pages']['id']) !== false) ? $asset = $this->versionModel->getAssets($page['pages']['id']) : $asset = '';
            ($this->versionModel->getVersions($page['pages']['id']) !== false) ? $version = $this->versionModel->getVersions($page['pages']['id']) : $version = [];
            
            foreach($asset as $a) { array_push($assets, $a); }
            if(!empty($version))foreach($version as $ver) { array_push($assets, $ver['path']); }
        }
        
        if (!empty($assets)) {
            $zipData = new \ZipArchive();
            $zipData->open($filename, \ZipArchive::CREATE);
            foreach ($assets as $file) {
                $zipData->addFile($file, $file);
            }
            $zipData->close();
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * deleteBackup
     *
     * @param  array $path
     * 
     * @return boolean
     */
    public function deleteBackup($path)
    {
        if (file_exists($path)) {
            unlink($path);
            return TRUE;
        } else {
            return FALSE;
        }
        
    }
}
    