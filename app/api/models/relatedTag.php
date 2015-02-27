<?php
namespace Zibaldone\Api;

use Illuminate\Database\Eloquent\Model as Eloquent;

class RelatedTag extends Eloquent {

    protected $database = 'zibaldone';
    protected $connection = 'mysql';
    protected $table = 'related_tags';
    public $timestamps = false;

    public function save() {
        
        //do not duplicate records
        if (
            self::where('tag_id', $this->tag_id)
            ->where('book_id', $this->book_id)
            ->where('article_id', $this->article_id)
            ->where('bookmark_id', $this->bookmark_id)
            ->first()
            ) {
            return true;
        }

        return parent::save();
    }
}