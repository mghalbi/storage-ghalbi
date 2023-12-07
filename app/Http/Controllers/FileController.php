<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\DestroyFilesRequest;

class FileController extends Controller
{
    public function myFiles(Request $reqeust, string $folder = null)
    {
        if ($folder) {
            $folder = File::query()->where('created_by', Auth::id())->where('path', $folder)->firstOrFail();
        }
        if (!$folder) {
            $folder = $this->getRoot();
        }
        $files = File::query()->where('parent_id', $folder->id)->where('created_by', Auth::id())->orderBy('isFolder', 'desc')->orderBy('created_at', 'desc')->paginate(9);

        $files = FileResource::collection($files);
        
        if ($reqeust->wantsJson()) {
            return $files;
        }

        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);

        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        $file = new File();
        $file->isfolder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }

    public function store(StoreFileRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                $this->saveFile($file, $parent, $user);
            }
        }
        
    }
    
    private function getRoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }

    public function saveFileTree($fileTree, $parent, $user){
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->isFolder = 1;
                $folder->name = $name;
                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {
                $this->saveFile($file, $parent, $user);
            }
            
        }
    }

    public function destroy(DestroyFilesRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;
        if ($data['all']) {
            $children =$parent->children;
            foreach ($children as $child) {
                $child->delete();
            }        
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    $file->delete();
                }
            }
        }

        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function download(DestroyFilesRequest $request) { 
        $data = $request->validated();
        $parent = $request->parent;
        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty(ids) ) {
            return ['message' => 'Please select files to download'];
        }

        if ($all) {
            $url = $this->createZip($parent->children);
            $filename = $parent->name .'.zip';
        } else {
            if (count($ids) == 1) {
                $file = File::find(ids[0]);
                if ($file->isFolder) {
                    if ($file->children->count() == 0) {
                        return ['message' => 'The folder is empty'];
                    }
                    $url = $this->createZip($file->children);
                    $filename = $file->name .'.zip';
                } else {
                    $path = 'public/' . pathinfo($file->storage_path);
                    Storage::copy($file->storage_path, $path);
                    $url = asset(Storage::url($path));
                    $filename = $file->name;  
                }
            } else {
                $files = File::query()->whereIn('id', $ids)->get();
                $url = $this->createZip($files);
                $filename = $parent->name .'.zip';
            }
        }
        return [
            'url' => $url,
            'file' => $filename
        ];
    }

    private function saveFile($file, $parent, $user): void 
    {
        $path = $file->store('/files/'.$user->id);
        $model = new File();
        $model->isFolder = false;
        $model->storage_path = $path;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();
        $parent->appendNode($model);
    }

    private function createZip($files): string
    {
        $zipPath = 'zip/'.Str::random() . '.zip';
        $publicPath = "public/".$zipPath;

        if (!is_dir(dirname($publicPath))) {
            Storage::makeDirectory($publicPath);
        }

        $zipFile = Storage::path($publicPath);
        $zip = new \ZipArchive();

        if ($zip->open($zipFile,   \ZipArchive::CREATE | \ZipArchive::OVERWRITE) == true) {
            $this->addFilesToZipFolder($zip, $files);
        }

        $zip->close();

        return asset(Storage::url($zipPath));
    }

    private function addFilesToZipFolder($zip, $files, $ancestors='') {
        foreach ($file as $files) {
            if ( $file->isFolder ) {
                $this->addFilesToZipFolder($zip,$file->children, $ancestors . $file->name .'/');
            } else {
                $zip->addFile(Storage::path($file->storage_path), $ancestors . $file->name);
            }
        }
    }
}
