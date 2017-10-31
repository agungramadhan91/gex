<?php

namespace App\Http\Controllers;

use App\MasterCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = MasterCustomer::all();
        // $roles = MasterRole::all();
        return view('master.customer.index', compact('customers'));
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
            $agent      = $request->agent;
            $shipper    = $request->shipper;
            $consignee  = $request->consignee;
            
            if (($agent && $shipper && $consignee) == true) {
                $type = $agent.'+'.$shipper.'+'.$consignee;
            } elseif (($agent && $shipper) == true) {
                $type = $agent.'+'.$shipper;
            } elseif (($agent && $consignee) == true){
                $type = $agent.'+'.$consignee;
            } elseif (($shipper && $consignee) == true) {
                $type = $shipper.'+'.$consignee;
            } elseif ($consignee == true) {
                $type = $consignee;
            } elseif ($shipper == true) {
                $type = $shipper;
            } else {
                $type = $agent;
            }

            // dd($type);
            
            $customer = $request->only('nick_name','address1','address2','city','province','country','phone1','phone2','fax','zipcode');

            $master_customer = MasterCustomer::create($customer);
            $master_customer->name = strtoupper($request->name);
            $master_customer->type = $type;
            $master_customer->save();

            if ($master_customer->id < 10) {
                $master_customer->code = 'CS0'.$master_customer->id;
            } else {
                $master_customer->code = 'CS'.$master_customer->id;
            }
            $master_customer->save();

            return redirect()->route('master.customer.index');
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

            $customer = MasterCustomer::find($id);

            if ($customer->id < 10) {
                $customer->code = 'CS0'.$customer->id;
            } else {
                $customer->code = 'CS'.$customer->id;
            }

            $customer->name 	= strtoupper($request->name);
            $customer->nick_name= $request->get('nick_name');
            $customer->address1	= $request->get('address1');
            $customer->address2 = $request->get('address2');
            $customer->city 	= $request->get('city');
            $customer->province = $request->get('province');
            $customer->country 	= $request->get('country');
            $customer->phone1 	= $request->get('phone1');
            $customer->phone2 	= $request->get('phone2');
            $customer->fax		= $request->get('fax');
            $customer->zipcode 	= $request->get('zipcode');

            $agent      = $request->agent;
            $shipper    = $request->shipper;
            $consignee  = $request->consignee;
            
            if (($agent && $shipper && $consignee) == true) {
                $type = $agent.'+'.$shipper.'+'.$consignee;
            } elseif (($agent && $shipper) == true) {
                $type = $agent.'+'.$shipper;
            } elseif (($agent && $consignee) == true){
                $type = $agent.'+'.$consignee;
            } elseif (($shipper && $consignee) == true) {
                $type = $shipper.'+'.$consignee;
            } elseif ($consignee == true) {
                $type = $consignee;
            } elseif ($shipper == true) {
                $type = $shipper;
            } else {
                $type = $agent;
            }
            $customer->type 	= $type;
            $customer->save();

            return redirect()->route('master.customer.index');
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

            $customer = MasterCustomer::find($id)->delete();
            return redirect()->route('master.customer.index');
        } else {
            return redirect()->route('home');
        }
    }

    public function search(Request $request)
    {
        $param = $request->param;
        $customer = MasterCustomer::select('id', 'nick_name','name')
            ->where('nick_name','like',"%$param%")
            ->orWhere('name', 'like',"%$param%")
            ->limit(5)
            ->offset(0)
            ->get();
        return response()->json(["data"=>$customer],200);
    }
}
