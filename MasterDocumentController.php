<?php

namespace App\Http\Controllers;

use App\MasterDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:operation,marketing,pricing,payable,admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $documents = MasterDocument::all();
        // $roles = MasterRole::all();
        return view('master.document.index', compact('documents'));
    }

    public function index_payable()
    {
        $documents = MasterDocument::all();
        // $roles = MasterRole::all();
        return view('master.document.index_payable', compact('documents'));
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
        	
            $document = $request->only('name','display_name','type','remark');
            $master_document = MasterDocument::create($document);

            if ($master_document->type == 'payable') {
                if ($master_document->id < 10) {
                    $master_document->code = 'DPAY0'.$master_document->id;
                } else {
                    $master_document->code = 'DPAY'.$master_document->id;
                }
            } elseif ($master_document == 'receivable') {
                if ($master_document->id < 10) {
                    $master_document->code = 'DREC0'.$master_document->id;
                } else {
                    $master_document->code = 'DREC'.$master_document->id;
                }
            } else {
                if ($master_document->id < 10) {
                    $master_document->code = 'DOC0'.$master_document->id;
                } else {
                    $master_document->code = 'DOC'.$master_document->id;
                }
                
            }
            $master_document->save();

            return redirect()->route('master.document.index');
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

            $document = MasterDocument::find($id);
            // $document->code 		= $request->get('code');
            $document->name 		= $request->get('name');
            $document->type			= $request->get('type');
            $document->remark 		= $request->get('remark');
            $document->remark       = $request->get('display_name');
            $document->save();

            if ($document->id < 10) {
                $document->code = 'DC0'.$document->id;
            } else {
                $document->code = 'DC'.$document->id;
            }
            $document->save();

            return redirect()->route('master.document.index');
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

            $document = MasterDocument::find($id)->delete();
            return redirect()->route('master.document.index');
        } else {
            return redirect()->route('home');
        }
    }
}
