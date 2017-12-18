<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Repository\LatestArticlesRepository;

class LatestArticlesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function fetchNews(LatestArticlesRepository $repository,Request $request)
    {
        $response = $repository->getData();
        $meta = ['info_text'=>'string'];
        return jsonSuccess($response, $meta);
    }

    //
}
