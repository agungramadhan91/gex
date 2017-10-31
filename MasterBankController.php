<?php

namespace App\Http\Controllers;

use App\MasterBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterBankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $banks = MasterBank::all();
        // $roles = MasterRole::all();
        return view('master.bank.index', compact('banks'));
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
        	
            // $bank = $request->only(strtoupper('name'),strtoupper('cabang'),'account',strtoupper('atas_nama'),strtoupper('address'),'swiftcode',strtoupper('remarks'));

            // MasterBank::create($bank);

            $bank = new MasterBank();
            $bank->name         = strtoupper($request->get('name'));
            $bank->cabang       = strtoupper($request->get('cabang'));
            $bank->account      = $request->get('account');
            $bank->atas_nama    = strtoupper($request->get('atas_nama'));
            $bank->address      = strtoupper($request->get('address'));
            $bank->swiftcode    = $request->get('swiftcode');
            $bank->remarks      = strtoupper($request->get('remarks'));
            $bank->save();

            return redirect()->route('master.bank.index');
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

            $bank = MasterBank::find($id);
            $bank->name 		= $request->get('name');
            $bank->cabang		= $request->get('cabang');
            $bank->account 		= $request->get('account');
            $bank->atas_nama    = $request->get('atas_nama');
            $bank->address      = $request->get('address');
            $bank->swiftcode 	= $request->get('swiftcode');
            $bank->remarks 		= $request->get('remarks');
            $bank->save();

            return redirect()->route('master.bank.index');
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

            $bank = MasterBank::find($id)->delete();
            return redirect()->route('master.bank.index');
        } else {
            return redirect()->route('home');
        }
    }
}
