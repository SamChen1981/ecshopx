<?php

namespace app\api\controller;

use app\api\model\v2\Article;
use app\api\model\v2\ArticleCategory;
use think\facade\Request;

class ArticleController extends Controller
{
    /**
     * POST ecapi.article.list
     */
    public function index(Request $request)
    {
        $rules = [
            'id' => 'required|integer',
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1',
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = ArticleCategory::getList($this->validated);

        return $this->json($model);
    }

    /**
     * GET article.{id:[0-9]+}
     */
    public function show($id)
    {
        return Article::getArticle($id);
    }
}
