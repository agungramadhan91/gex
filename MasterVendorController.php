<?php

namespace App\Http\Controllers;

use App\MasterVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterVendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vendors = MasterVendor::all();
        // $roles = MasterRole::all();
        return view('master.vendor.index', compact('vendors'));
        
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
        	
            $vendor = $request->only('name','nick_name','address1','address2','city','province','country','phone1','phone2','zipcode','remark','type');
            $master_vendor = MasterVendor::create($vendor);

            if ($master_vendor->id < 10) {
                $master_vendor->code = 'VD0'.$master_vendor->id;
            } else {
                $master_vendor->code = 'VD'.$master_vendor->id;
            }
            $master_vendor->save();

            return redirect()->route('master.vendor.index');
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

            $vendor             = MasterVendor::find($id);
            
            if ($vendor->id < 10) {
                $vendor->code = 'VD0'.$vendor->id;
            } else {
                $vendor->code = 'VD'.$vendor->id;
            }

            $vendor->name 		= $request->get('name');
            $vendor->nick_name	= $request->get('nick_name');
            $vendor->address1	= $request->get('address1');
            $vendor->address2 	= $request->get('address2');
            $vendor->city 		= $request->get('city');
            $vendor->province 	= $request->get('province');
            $vendor->country	= $request->get('country');
            $vendor->phone1 	= $request->get('phone1');
            $vendor->phone2 	= $request->get('phone2');
            $vendor->zipcode 	= $request->get('zipcode');
            $vendor->remark 	= $request->get('remark');
            $vendor->type       = $request->get('type');

            $vendor->save();

            return redirect()->route('master.vendor.index');
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

            $vendor = MasterVendor::find($id)->delete();
            return redirect()->route('master.vendor.index');
        } else {
            return redirect()->route('home');
        }
    }
}
