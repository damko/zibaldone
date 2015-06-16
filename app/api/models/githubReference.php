<?php
namespace Zibaldone\Api;

use Illuminate\Database\Eloquent\Model as Eloquent;

class GithubReference extends Eloquent {

    use subreferenceTrait;

    protected $database = 'zibaldone';
    protected $connection = 'mysql';
    protected $table = 'github_references';
    public $timestamps = false;


    public function add(\stdClass $newRef)
    {
        // checks the input
        $mandatory_attributes = array('book_id','subref', 'repo_user', 'repo_path');
        foreach ($mandatory_attributes as $attribute) {
            if (!isset($newRef->$attribute) || empty(trim($newRef->$attribute))) {
                return false;
            }
        }

        $this->repo_user = $newRef->repo_user;
        // when you copy the path of a file from github the repo name in first place. Ex.: repo_name/file_path
        $explode = explode('/', $newRef->repo_path);
        $this->repo_name = array_shift($explode);
        $this->repo_path = implode('/', $explode);
        if (isset($newRef->sha)) {
            $this->sha = $newRef->sha;
        }

        // Creates the referenceId: the unique id will be built using these attributes
        $id_attributes = array('book_id', 'subref', 'repo_user', 'repo_name', 'repo_path', 'sha');
        $id_components = array();
        foreach ($id_attributes as $attribute) {
            if (isset($newRef->$attribute)) {
                $id_components[] = trim($newRef->$attribute);
            }
        }
        $this->reference_id = self::makeId($id_components);

        // downloads the Reference
        $download = Downloader::downloadFromGithub($this);

        // stores the downloaded Reference as a Fragment File
        if ($download && $full_filename = $download->store(Book::find($newRef->book_id)->getManuscriptPath(), 'rename')) {

            // saves it as a Fragment record
            $fragment = new fragment();
            $fragment->book_id = $newRef->book_id;
            $fragment->reference_id = $this->reference_id;
            $fragment->type = 'reference';
            $fragment->full_filename = $full_filename;
            $fragment->menu_label = $fragment->guessMenuLabel();
            $fragment->save();
        }

        return parent::save() ? $this->reference_id : false ;
    }
}
