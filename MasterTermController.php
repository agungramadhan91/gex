<?php

namespace App\Http\Controllers;

use App\MasterTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterTermController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $terms = MasterTerm::all();
        // $roles = MasterRole::all();
        return view('master.term.index', compact('terms'));
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
        	
            $term = $request->only('name','type','days');

            MasterTerm::create($term);

            return redirect()->route('master.term.index');
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

            $term = MasterTerm::find($id);
            $term->name 	= $request->get('name');
            $term->type		= $request->get('type');
            $term->days 	= $request->get('days');
            $term->save();

            return redirect()->route('master.term.index');
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

            $term = MasterTerm::find($id)->delete();
            return redirect()->route('master.term.index');
        } else {
            return redirect()->route('home');
        }
    }
}
