<?php
namespace Zibaldone\Api;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Tag extends Eloquent {

    protected $database = 'zibaldone';
    protected $connection = 'mysql';
    protected $table = 'tags';
    //public $incrementing = false;
    public $timestamps = false;


    public function save() {

    	$this->name = strtolower(preg_replace('/[^A-Za-z0-9\-_ ]/', '', trim($this->name)));

    	// it does not add a tag if it's already present
    	if (self::where('name', $this->name)->first()) {
    		return true;
    	}

    	return parent::save();
    }

    public static function listTags() {

    	$tags = array();

    	foreach (self::all() as $tag) {
    	 	$tags[$tag->name] = RelatedTag::where('tag_id', $tag->id)->count();
    	}

    	return $tags;
    }
}