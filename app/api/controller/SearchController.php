<?php

namespace app\api\controller;

use app\api\model\v2\Keywords;

class SearchController extends Controller
{
    //POST  ecapi.search.keyword.list
    public function index()
    {
        return $this->json(Keywords::getHot());
    }
}
