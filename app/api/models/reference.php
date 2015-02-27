<?php
namespace Zibaldone\Api;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Reference extends Eloquent {

    protected $database = 'zibaldone';
    protected $connection = 'mysql';
    protected $table = 'references';
    public $incrementing = false;

    public static function find($rid, $columns = array('*'), $no_extended_attrs = false)
    {
        $instance = parent::find($rid, $columns);

        if ($no_extended_attrs) {
            return $instance;
        }

        $extended_attrs = $instance->subreference->attributesToArray();
        unset($extended_attrs['id']);
        unset($extended_attrs['reference_id']);

        $subref = $instance->subref;
        unset($instance->$subref);

        if ($download = $instance->download) {
            $extended_attrs['html_url'] = $download->html_url;
            unset($instance->download);
        }

        foreach ($extended_attrs as $attribute => $value) {
            $instance->$attribute = $value;
        }

        return $instance;
    }

    public function book()
    {
        return $this->belongsTo('Zibaldone\Api\Book', 'book_id', 'id');
    }

    public function subreference()
    {
        //$class = 'Zibaldone\Api\GithubReference';
        //$class .= '\' . $this->subref;
        $class = 'Zibaldone\Api\\'. $this->subref;
        return $this->hasOne($class, 'reference_id', 'id');
    }

    public function download()
    {
        return $this->hasOne('Zibaldone\Api\Download', 'reference_id', 'id');
    }

    public function fragment()
    {
        return $this->hasOne('Zibaldone\Api\Fragment', 'reference_id', 'id');
    }

    public static function makeId(array $input)
    {
        return md5(implode('', $input));
    }

    public function isPresent($identifier)
    {
        return $this->where('id', '=', $identifier)->first();
    }

    // checks the input
    protected function checkBeforeAdd(\stdClass $newRef)
    {
        $mandatory_attributes = array('book_id', 'subref');
        foreach ($mandatory_attributes as $attribute) {
            if (!isset($newRef->$attribute) || empty(trim($newRef->$attribute))) {
                return false;
            }
        }
        return true;
    }

    protected function checkRepoType()
    {
        $possible_subrefs = array('GithubReference'); //TODO add here 'httpReference' 'gitlabReference'

        if (!in_array($this->subref, $possible_subrefs)) {
            return false;
        }

        return true;
    }

    public function add(\stdClass $newRef)
    {
        if (! $this->checkBeforeAdd($newRef)) {
            return false;
        }

        $this->book_id = trim($newRef->book_id);
        $this->subref = trim($newRef->subref);
        $this->synchrony = trim($newRef->synchrony);

        if (! $this->checkRepoType()) {
            return false;
        }
        
        $class = 'Zibaldone\Api\\'. $this->subref;

        $subref = new $class;
        if (! $this->id = $subref->add($newRef)) {
            return false;
        }

        // looks for duplicated references
        if ($this->isPresent($this->id)) {
            $subref->delete();
            return false;
        }

        if (! parent::save()) {
            $subref->delete();
            return false;
        }

        return true;

    }

    public function delete()
    {
        if ($this->fragment) {
            $this->fragment->delete();
        }

        if ($this->download) {
            $this->download->delete();
        }

        if ($this->subreference) {
            $this->subreference->delete();
        }

        return parent::delete();
    }
}
