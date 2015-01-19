<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * The fragment object represents a file on the filesystem
 */

class Fragment extends Eloquent{

    protected $database = 'zibaldone';
    protected $table = 'fragments';
    protected $connection = 'mysql';

    public function book()
    {
        return $this->belongsTo('Book', 'book_id');
    }

    public function reference()
    {
        return $this->belongsTo('Reference', 'reference_id');
    }

    /**
     * Creates a label starting from the file name
     * @return string The menu label
     */
    public function guessMenuLabel()
    {
        $string = strtolower(preg_replace('/[^A-Za-z0-9\-. ]/', '', str_replace('_', ' ', $this->full_filename)));

        //removes the file extension
        $pieces = explode('.', $string);
        array_pop($pieces);
        return ucfirst(implode('.', $pieces));
    }

    protected function isPresent($filename)
    {
        return self::where('full_filename', '=', $filename)->first()? true : false;
    }

    public function getContent()
    {
        $filesystem = new Filesystem(new Adapter($this->book->getManuscriptPath()));
        return $filesystem->read($this->full_filename);
    }

    public function checksum()
    {
        return md5(base64_encode($this->getContent()));
    }

    public function delete()
    {

        $filesystem = new Filesystem(new Adapter($this->book->getManuscriptPath()));

        if ($filesystem->has($this->full_filename)) {
            if (! $filesystem->delete($this->full_filename)) {
                return false;
            }
        }

        return parent::delete();
    }
}
