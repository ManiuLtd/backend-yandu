<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Services\Admin\PartnerService;
use Illuminate\Http\Request;

class PartnerController extends Controller
{

    protected $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
    }

    public function index()
    {
        return view('admin.partner.index');
    }

    public function indexRequest(Request $request)
    {
        $data =$this->partnerService->indexAjax($request);
        return $data;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function check(Request $request)
    {
        $data =$this->partnerService->checkStore($request);
        return $data;
    }

    /**
     * 确认验收报告
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request)
    {
        $data =$this->partnerService->confirmCheck($request);
        return $data;
    }
}