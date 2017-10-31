<?php

namespace App\Http\Controllers;

use App\User;
use App\MasterRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        $roles = MasterRole::all();
        return view('master.user.index', compact('users','roles'));
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
        	$password = bcrypt('rahasia');
            $user = $request->only('code','name','username','email','password','role','address1','address2','city','province','country','phone1','phone2','phone3');
            User::create($user);

            return redirect()->route('master.user.index');
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

            $user = User::find($id);
            $user->code 	= $request->get('code');
            $user->name 	= $request->get('name');
            $user->username = $request->get('username');
            $user->email 	= $request->get('email');
            $user->role		= $request->get('role');
            $user->address1	= $request->get('address1');
            $user->address2 = $request->get('address2');
            $user->city 	= $request->get('city');
            $user->state 	= $request->get('province');
            $user->state    = $request->get('country');
            $user->phone1 	= $request->get('phone1');
            $user->phone2 	= $request->get('phone2');
            $user->phone3 	= $request->get('phone3');
            $user->save();

            return redirect()->route('master.user.index');
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

            $user = User::find($id)->delete();
            return redirect()->route('master.user.index');
        } else {
            return redirect()->route('home');
        }
    }
}
