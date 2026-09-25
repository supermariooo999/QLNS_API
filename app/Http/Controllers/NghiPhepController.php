<?php

namespace App\Http\Controllers;

use App\Models\NghiPhep;
use App\Models\SoDuPhep;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NghiPhepController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * POST /nghi_phep
     */
    private function calculateLeaveDays($startDate, $endDate, $startSession, $endSession)
    {
        
    }

    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NghiPhep $nghiPhep)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNghiPhepRequest $request, NghiPhep $nghiPhep)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NghiPhep $nghiPhep)
    {
        //
    }
}
