<?php

namespace UniSharp\LaravelFilemanager\Controllers;

use Illuminate\Support\Facades\File;

/**
 * Class FolderController.
 */
class FolderController extends LfmController
{
    /**
     * Get list of folders as json to populate treeview.
     *
     * @return mixed
     */
    public function getFolders()
    {
        $folder_types = [];
        $root_folders = [];

        if (parent::allowMultiUser()) {
            $folder_types['user'] = 'root';
        }

        if (parent::allowShareFolder()) {
            $folder_types['share'] = 'shares';
        }

        foreach ($folder_types as $folder_type => $lang_key) {
            $root_folder_path = parent::getRootFolderPath($folder_type);

            $children = parent::getDirectories($root_folder_path);
            usort($children, function ($a, $b) {
                return strcmp($a->name, $b->name);
            });

            array_push($root_folders, (object) [
                'name' => trans('laravel-filemanager::lfm.title-' . $lang_key),
                'path' => parent::getInternalPath($root_folder_path),
                'children' => $children,
                'has_next' => ! ($lang_key == end($folder_types)),
            ]);
        }

        return view('laravel-filemanager::tree')
            ->with(compact('root_folders'));
    }

    /**
     * Add a new folder.
     *
     * @return mixed
     */
    public function getAddfolder()  // error-folder-name-invalid
    {
        $folder_name = parent::translateFromUtf8(trim(request('name')));        

        if (!preg_match("/^[a-z0-9áéíóúàèìòùñÁÉÍÓÚÀÈÌÒÙÑ \-_]+$/i", $folder_name)) {   
            return parent::error('folder-name-invalid');
        } else {
            $separator = ' '; 
            $language  = 'en'; 
            $title     = parent::ascii($folder_name, $language); 
            // Convert all dashes/underscores into separator
            $flip = $separator;
            $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);
            // Replace @ with the word 'at'
            $title = str_replace('@', $separator.'at'.$separator, $title);
            // Remove all characters that are not the separator, letters, numbers, or whitespace.
            // With lower case: $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
            $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', $title);
            // Replace all separator characters and whitespace by a single separator
            $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
            $folder_name = trim($title, $separator); 
        }

        $path = parent::getCurrentPath($folder_name);

        if (empty($folder_name)) {
            return parent::error('folder-name');
        }

        if (File::exists($path)) {
            return parent::error('folder-exist');
        }

        if (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $folder_name)) {
            return parent::error('folder-alnum');
        }    

        parent::createFolderByPath($path);
        return parent::$success_response;
    }
}
