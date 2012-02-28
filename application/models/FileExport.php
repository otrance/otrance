<?php
/**
 * Model to manage file operations on language files
 */
class Application_Model_FileExport extends Msd_Application_Model
{
    /**
     * Builds the language pack archives.
     *
     * @return void
     */
    public function buildArchives()
    {
        $fileTree = new Application_Model_FileTree(EXPORT_PATH);
        $fileList = $fileTree->getSimpleTree();
        $filename = DOWNLOAD_PATH . DS . 'language_pack-' . date('Ymd-His');
        $zipArch = Msd_Archive::factory('Zip', $filename, EXPORT_PATH);
        $tarGzArch = Msd_Archive::factory('Tar_Gz', $filename, EXPORT_PATH);
        $tarBzArch = Msd_Archive::factory('Tar_Bz2', $filename, EXPORT_PATH);
        foreach ($fileList as $file) {
            $zipArch->addFile($file);
            $tarGzArch->addFile($file);
            $tarBzArch->addFile($file);
        }
        $zipArch->buildArchive();
        $tarGzArch->buildArchive();
        $tarBzArch->buildArchive();
        $this->_cleanDownloads();
    }

    /**
     * Remain the latest download package and delete older ones.
     *
     * Takes care of different extensions (zip, gz, tar.gz)
     *
     * @return void
     */
    private function _cleanDownloads()
    {
        $files = array();
        $fileExtensions = array();
        $iterator = new DirectoryIterator(DOWNLOAD_PATH);
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }
            $filename = $fileinfo->getFilename();
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $fileNameWoExt = substr($filename, 0, -(strlen($fileExtension)+1));
            if (!in_array($fileExtension, $fileExtensions)) {
                $fileExtensions[] = $fileExtension;
            }
            if (!in_array($fileNameWoExt, $files)) {
                $mTime = $fileinfo->getMTime();
                $files["$mTime"] = $fileNameWoExt;
            }
        }
        if (empty($files)) {
            return;
        }
        krsort($files); // move latest file to top

        $fileKeys = array_keys($files);
        unset($fileKeys[0]); // remove latest file from array for not deleting it
        foreach ($fileKeys as $key) {
            foreach ($fileExtensions as $ext) {
                $filename = DOWNLOAD_PATH . DS . $files[$key] . '.' .$ext;
                if (is_readable($filename)) {
                    @unlink($filename);
                }
            }
        }
    }

}