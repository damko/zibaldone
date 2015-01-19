<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

class Download extends Eloquent {

    protected $database = 'zibaldone';
    protected $table = 'downloads';
    protected $connection = 'mysql';

    public function reference()
    {
        return $this->belongsTo('Reference', 'reference_id');
    }

    public function fragment()
    {
        return $this->hasOne('Fragment', 'reference_id', 'reference_id');
    }

    /**
     * Stores the content of the downloaded Reference in a file
     * @param  string $path             Manuscript directory
     * @param  string $collision_action What to do if an homonymous file is found in the fs
     * @return string                   Full filename (no path)
     */
    public function store($path = null, $collision_action = 'rename')
    {
        if (is_null($path)) {
            try {
                $path = $this->reference->book->getManuscriptPath();
            } catch (Exception $e) {
                return false;
            }
        }

        $filesystem = new Filesystem(new Adapter($path));

        if ($filesystem->has($this->full_filename)) {

            switch ($collision_action) {

                case 'donttouch':
                    return $this->full_filename;
                break;

                case 'overwrite':
                    if (!$filesystem->delete($this->full_filename)) {
                        return false;
                    }
                break;

                case 'rename':
                    $pieces = explode('.', $this->full_filename);
                    $ext = array_pop($pieces);
                    $pieces[] = '_' . time();
                    $this->full_filename = implode('', $pieces) . '.' . $ext;
                    $this->save();

                break;

                default:
                    return false;
                break;
            }
        }

        return $filesystem->write($this->full_filename, base64_decode($this->content)) ? $this->full_filename : false;
    }
}
