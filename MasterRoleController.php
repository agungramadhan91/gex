<?php

namespace App\Http\Controllers;

use App\MasterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterRoleController extends Controller
{
    public function index()
    {
    	if (Auth::user()->role == 'admin' || Auth::user()->role == 'operation' || Auth::user()->role == 'marketing' || Auth::user()->role == 'pricing' || Auth::user()->role == 'admin2') {
	        $roles = MasterRole::all();
	        return view('master.role.index', compact('roles'));
    	} else {
    		return redirect()->back();
    	}
    	
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
            $this->validate($request,[
                'name'          =>'required|unique:master_roles,name',
                // 'description'   =>'',
            ]);

            $data = $request->only('name', 'description');
            MasterRole::create($data);

            return redirect()->route('master.role.index');
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

            $role = MasterRole::find($id);
            $role->name = $request->get('name');
            $role->description = $request->get('description');
            $role->save();

            return redirect()->route('master.role.index');
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

            $role = MasterRole::find($id)->delete();
            return redirect()->route('master.role.index');
        } else {
            return redirect()->route('home');
        }
    }
}
