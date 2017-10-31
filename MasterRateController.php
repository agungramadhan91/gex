<?php

namespace App\Http\Controllers;

use App\MasterRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rates = MasterRate::all();
        // $roles = MasterRole::all();
        return view('master.rate.index', compact('rates'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2' || Auth::user()->role == 'pajak') {
            // $this->validate($request,[
            //     'name'          =>'required|unique:master_roles,name',
            //     // 'description'   =>'',
            // ]);
        	
            $rate = $request->only('date','rate');

            MasterRate::create($rate);

            return redirect()->route('master.rate.index');
        } else {
            return redirect()->route('home');
        }
        
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2' || Auth::user()->role == 'pajak') {

            $rate = MasterRate::find($id);
            $rate->date 	= $request->get('date');
            $rate->rate		= $request->get('rate');
            $rate->save();

            return redirect()->route('master.rate.index');
        } else {
            return redirect()->route('home');
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
    	if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2' || Auth::user()->role == 'pajak') {

            $rate = MasterRate::find($id)->delete();
            return redirect()->route('master.rate.index');
        } else {
            return redirect()->route('home');
        }
    }
}
