<?php

trait subreferenceTrait {

    public function reference()
    {
        return $this->belongsTo('Reference', 'reference_id', 'id');
    }

    public function download()
    {
        return $this->hasOne('Download', 'reference_id', 'reference_id');
    }

    public function fragment()
    {
        return $this->hasOne('Fragment', 'reference_id', 'reference_id');
    }

    public function isPresent($identifier)
    {
        return $this->where('reference_id', '=', $identifier)->first();
    }

    public static function makeId(array $input)
    {
        return md5(implode('', $input));
    }

}
