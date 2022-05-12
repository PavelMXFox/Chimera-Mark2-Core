<?php 
namespace fox;
class searchResult {
    public $page=0;
    public $pages=0;
    public $result=[];
    public $count=0;
    protected $idx=1;
    
    public function setIndex($idx) {
        $this->idx = $idx;
    }
    
    public function push($val) {
        $this->result[$this->idx] = $val;
        $this->idx++;
        $this->count++;
    }
    
    public function __construct($idx=null) {
        if (isset($idx)) {
            $this->idx = $idx;
        }
    }
    
    public function setIndexByPage($page=null, $pagesize=null) {
        if ($page===null) { $page = $this->page; }
        $this->idx = (($page-1)*$pagesize)+1;
    }
}
?>