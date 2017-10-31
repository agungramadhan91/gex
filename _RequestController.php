<?php

namespace App\Http\Controllers;

use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\MasterDocument;
use App\MasterCustomer;
use App\MasterVendor;
use App\MasterUnit;
use App\MasterPort;
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\User;
use App\RC;
use App\RequestModel;

class _RequestController extends Controller
{
    //
 //    public function __construct()
	// {
	// 	$this->middleware('role:operation,marketing,pricing,payable,admin');
	// }

	public function request(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', '!=', 'revisi')->where('step_role', 2)->latest();
        return view('jobsheet.request', compact('jobsheets'));
    }

    public function requestrc(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', '!=', 'revisi')->where('step_role', 2)->where('marketing_id', Auth::user()->id)->get();
        return view('jobsheet.request', compact('jobsheets'));
    }

    public function detailrequest(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();

        return view('jobsheet.detailrequest', compact('jobsheet','references','payables'));
    }

    public function detailrequestrc(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();

        if (Auth::user()->role == 'marketing') {
            $rc = RC::where('jobsheet_id', $jobsheet->id)->where('rc_type', 'marketing')->get();
        } else {
            $rc = RC::where('jobsheet_id', $jobsheet->id)->get();
        }
        

        return view('jobsheet.detailrequestrc', compact('jobsheet','references','rc'));
    }

    public function createrequest(Request $request)
    {
    	$pay = $request->pay_id;
    	$tanggal = $request->tanggal;

        if(Auth::user()->role == 'operation' || Auth::user()->role == 'pricing' || Auth::user()->role == 'payable' ) {
        	for($i = 0; $i < count($pay); $i++) {
                if (!empty($pay[$i])) {
            		$convert_date = Carbon::createFromFormat('d-m-Y', $tanggal[$i])->toDateString();
            		$payables = Payable::find($pay[$i]);
            		$job = JobSheet::find($payables->jobsheet_id);

        	    	$savereq = new RequestModel;
        	    	$savereq->jobsheet_id = $job->id;
        	    	$savereq->pay_id = $pay[$i];
        	    	$savereq->tanggal = $convert_date;
        	    	$savereq->user_id = Auth::user()->id;
                    $savereq->status = 'requested';
                    $savereq->type = Auth::user()->role;
        	    	$savereq->timestamps = true;
        	    	$savereq->save();
                }
    	    }

    	   return redirect()->route('jobsheet.listrequest');

        }elseif(Auth::user()->role == 'marketing') {
            for($i = 0; $i < count($pay); $i++) {
                if (!empty($pay[$i])) {
                    $convert_date = Carbon::createFromFormat('d-m-Y', $tanggal[$i])->toDateString();
                    $rc = RC::find($pay[$i]);
                    $job = JobSheet::find($rc->jobsheet_id);

                    $savereq = new RequestModel;
                    $savereq->jobsheet_id = $job->id;
                    $savereq->pay_id = $pay[$i];
                    $savereq->tanggal = $convert_date;
                    $savereq->user_id = Auth::user()->id;
                    $savereq->status = 'requested';
                    $savereq->type = Auth::user()->role;
                    $savereq->timestamps = true;
                    $savereq->save();
                }
            }
            
            return redirect()->route('jobsheet.listrequestrc');
        }

    }

    public function listrequest(Request $request)
    {
    	$requests = RequestModel::where('status', 'requested')->where('type','!=','marketing')->get();

    	return view('jobsheet.listrequest', compact('requests'));
    }

    public function listrequestrc(Request $request)
    {
        if(Auth::user()->role == 'marketing') {
            $jobsheets = RequestModel::where('user_id', Auth::user()->id)->where('status', 'requested')->get();
        }elseif (Auth::user()->role == 'payable') {
            $jobsheets = RequestModel::where('status', 'requested')->where('type','marketing')->get();
        }

        return view('jobsheet.listrequestrc', compact('jobsheets'));
    }

    public function reportrequested(Request $request, Datatables $datatables)
    {
        $jobsheets = RequestModel::where('status', 'requested')->get();
        return view('jobsheet.reportrequested', compact('jobsheets'));
    }

}
