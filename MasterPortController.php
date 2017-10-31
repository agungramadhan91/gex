<?php

namespace App\Http\Controllers;

use App\MasterPort;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterPortController extends Controller
{
    public function __construct()
    {
        // $this->middleware('role:admin,operation');
        // $this->middleware('role:operation');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ports = MasterPort::all();
        // $roles = MasterRole::all();
        return view('master.port.index', compact('ports'));
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
        	
            $port = $request->only('nick_name','city','address','province','country','type','loading');
            $master_port = MasterPort::create($port);

            if ($master_port->id < 10) {
                $master_port->code = 'PO0'.$master_port->id;
            } else {
                $master_port->code = 'PO'.$master_port->id;
            }
            $master_port->save();

            return redirect()->route('master.port.index');
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

            $port = MasterPort::find($id);
            $port->code         = $request->get('code');
            $port->nick_name    = $request->get('nick_name');
            $port->city		    = $request->get('city');
            $port->province		= $request->get('province');
            $port->country 		= $request->get('country');
            $port->type 	= $request->get('type');
            $port->loading 		= $request->get('loading');
            $port->save();

            return redirect()->route('master.port.index');
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

            $port = MasterPort::find($id)->delete();
            return redirect()->route('master.port.index');
        } else {
            return redirect()->route('home');
        }
    }
}
