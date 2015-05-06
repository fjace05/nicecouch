<?php namespace Jferguson\EloquentCouchdb\Eloquent;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    public function offset($offset){
        return $this->slice($offset);
    }

    public function skip($skipOffset){
        return $this->offset($skipOffset);
    }

    public function limit($limit){
        return $this->slice(0, $limit);
    }

    public function get($key = null, $default = null){
        if(is_null($key)){
            return $this;
        }
        else {
            return parent::get($key, $default);
        }
    }

    public function orderBy($field, $direction = null){
        $descending = (strtolower($direction) == "desc") ? true : false;
        return $this->sortBy($field, SORT_REGULAR, $descending);
    }

}

?>