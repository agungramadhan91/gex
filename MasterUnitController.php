<?php

namespace App\Http\Controllers;

use App\MasterUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $units = MasterUnit::all();
        // $roles = MasterRole::all();
        return view('master.unit.index', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2') {
            // $this->validate($request,[
            //     'name'          =>'required|unique:master_roles,name',
            //     // 'description'   =>'',
            // ]);
        	
            $unit = $request->only('name','display_name');

            MasterUnit::create($unit);

            return redirect()->route('master.unit.index');
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
        if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2') {

            $unit = MasterUnit::find($id);
            $unit->name 		= $request->get('name');
            $unit->display_name	= $request->get('display_name');
            $unit->save();

            return redirect()->route('master.unit.index');
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
    	if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2') {

            $unit = MasterUnit::find($id)->delete();
            return redirect()->route('master.unit.index');
        } else {
            return redirect()->route('home');
        }
    }
}
