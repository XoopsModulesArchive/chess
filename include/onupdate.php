<?php

/*
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright    {@link https://xoops.org/ XOOPS Project}
 * @license      {@link http://www.gnu.org/licenses/gpl-2.0.html GNU GPL 2 or later}
 * @package
 * @since
 * @author       XOOPS Development Team
 */
use XoopsModules\Chess;

if ((!defined('XOOPS_ROOT_PATH')) || !($GLOBALS['xoopsUser'] instanceof XoopsUser)
    || !$GLOBALS['xoopsUser']->isAdmin()) {
    exit('Restricted access' . PHP_EOL);
}

/**
 * @param string $tablename
 *
 * @return bool
 */
function tableExists($tablename)
{
    $result = $GLOBALS['xoopsDB']->queryF("SHOW TABLES LIKE '$tablename'");

    return $GLOBALS['xoopsDB']->getRowsNum($result) > 0;
}

/**
 * Prepares system prior to attempting to install module
 * @param \XoopsModule $module {@link XoopsModule}
 * @return bool true if ready to install, false if not
 */
function xoops_module_pre_update_xxxx(\XoopsModule $module)
{
    $moduleDirName = basename(dirname(__DIR__));

    /** @var Chess\Helper $helper */

    /** @var Chess\Utility $utility */

    $helper = Chess\Helper::getInstance();

    $utility = new Chess\Utility();

    $xoopsSuccess = $utility::checkVerXoops($module);

    $phpSuccess = $utility::checkVerPhp($module);

    $migrator = new \XoopsModules\Chess\Common\Migrate();

    $migrator->synchronizeSchema();

    return $xoopsSuccess && $phpSuccess;
}

/**
 * Performs tasks required during update of the module
 * @param \XoopsModule $module {@link XoopsModule}
 * @param null        $previousVersion
 *
 * @return bool true if update successful, false if not
 */
function xoops_module_update_xxxx(\XoopsModule $module, $previousVersion = null)
{
    $moduleDirName = basename(dirname(__DIR__));

    $moduleDirNameUpper = mb_strtoupper($moduleDirName);

    /** @var Chess\Helper $helper */

    /** @var Chess\Utility $utility */

    /** @var Chess\Common\Configurator $configurator */

    $helper = Chess\Helper::getInstance();

    $utility = new Chess\Utility();

    $configurator = new Chess\Common\Configurator();

    $helper->loadLanguage('common');

    if ($previousVersion < 240) {
        //rename column EXAMPLE

        $tables = new \Tables();

        $table = 'xxxx_categories';

        $column = 'order';

        $newName = 'order';

        $attributes = "INT(5) NOT NULL DEFAULT '0'";

        if ($tables->useTable($table)) {
            $tables->alterColumn($table, $column, $attributes, $newName);

            if (!$tables->executeQueue()) {
                echo '<br>' . constant('CO_' . $moduleDirNameUpper . '_UPGRADEFAILED0') . ' ' . $migrate->getLastError();
            }
        }

        //delete old HTML templates

        if (count($configurator->templateFolders) > 0) {
            foreach ($configurator->templateFolders as $folder) {
                $templateFolder = $GLOBALS['xoops']->path('modules/' . $moduleDirName . $folder);

                if (is_dir($templateFolder)) {
                    $templateList = array_diff(scandir($templateFolder, SCANDIR_SORT_NONE), ['..', '.']);

                    foreach ($templateList as $k => $v) {
                        $fileInfo = new SplFileInfo($templateFolder . $v);

                        if ('html' === $fileInfo->getExtension() && 'index.html' !== $fileInfo->getFilename()) {
                            if (file_exists($templateFolder . $v)) {
                                unlink($templateFolder . $v);
                            }
                        }
                    }
                }
            }
        }

        //  ---  DELETE OLD FILES ---------------

        if (count($configurator->oldFiles) > 0) {
            //    foreach (array_keys($GLOBALS['uploadFolders']) as $i) {

            foreach (array_keys($configurator->oldFiles) as $i) {
                $tempFile = $GLOBALS['xoops']->path('modules/' . $moduleDirName . $configurator->oldFiles[$i]);

                if (is_file($tempFile)) {
                    unlink($tempFile);
                }
            }
        }

        //  ---  DELETE OLD FOLDERS ---------------

        xoops_load('XoopsFile');

        if (count($configurator->oldFolders) > 0) {
            //    foreach (array_keys($GLOBALS['uploadFolders']) as $i) {

            foreach (array_keys($configurator->oldFolders) as $i) {
                $tempFolder = $GLOBALS['xoops']->path('modules/' . $moduleDirName . $configurator->oldFolders[$i]);

                /** @var XoopsObjectHandler $folderHandler */

                $folderHandler = \XoopsFile::getHandler('folder', $tempFolder);

                $folderHandler->delete($tempFolder);
            }
        }

        //  ---  CREATE UPLOAD FOLDERS ---------------

        if (count($configurator->uploadFolders) > 0) {
            //    foreach (array_keys($GLOBALS['uploadFolders']) as $i) {

            foreach (array_keys($configurator->uploadFolders) as $i) {
                $utility::createFolder($configurator->uploadFolders[$i]);
            }
        }

        //  ---  COPY blank.png FILES ---------------

        if (count($configurator->copyBlankFiles) > 0) {
            $file = dirname(__DIR__) . '/assets/images/blank.png';

            foreach (array_keys($configurator->copyBlankFiles) as $i) {
                $dest = $configurator->copyBlankFiles[$i] . '/blank.png';

                $utility::copyFile($file, $dest);
            }
        }

        //delete .html entries from the tpl table

        $sql = 'DELETE FROM ' . $GLOBALS['xoopsDB']->prefix('tplfile') . " WHERE `tpl_module` = '" . $module->getVar('dirname', 'n') . "' AND `tpl_file` LIKE '%.html%'";

        $GLOBALS['xoopsDB']->queryF($sql);

        /** @var XoopsGroupPermHandler $gpermHandler */

        $gpermHandler = xoops_getHandler('groupperm');

        return $gpermHandler->deleteByModule($module->getVar('mid'), 'item_read');
    }

    return true;
}
