<?php

namespace App\Http\Controllers;

use App\RequestModel;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\MasterDocument;
use App\MasterCustomer;
use App\MasterVendor;
use App\MasterUnit;
use App\MasterPort;
use App\Receivable;
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\User;
use App\RC;
use App\Reimbursement;

class _PricingController extends Controller
{
    //
    public function __construct()
	{
		$this->middleware('role:pricing,payable,admin-access');
	}

    public function jobsheet_index(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('step_role', '2')->get();
        
        return view('jobsheet.index', compact('jobsheets'));
    }

    public function jobsheet_uncreated(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('step_role', '!=', '2')->whereNotIn('status',['revisi-marketing','revisi-pricing','revisi-payable'])->get();
        
        return view('jobsheet.uncreated', compact('jobsheets'));
    }

    public function jobsheet_create(Request $request, Datatables $datatables, $id)
    {
    	$jobsheet = JobSheet::find($id);
        if (!empty($jobsheet)) {
        	$payables   = Payable::where('jobsheet_id', $id)->get();
            $references = Reference::where('jobsheet_id', $id)->get();
            $reimbursements = Reimbursement::where('jobsheet_id', $id)->get();

            return view('jobsheet.pricing.created', compact('jobsheet','references','payables','reimbursements'));
        }

        return redirect('/');
    }

    public function jobsheet_update(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $cek_rec = Receivable::where('jobsheet_id', $jobsheet->id)->count();
        
        if ($cek_rec > 0) {
            $jobsheet->status = 'completed';
        }else{
            $jobsheet->status = 'uncompleted';
        }

        $jobsheet->step_role = '2';
        $jobsheet->save();

        if ($jobsheet) 
        {
            //==============UPDATE PAYABLE============
            $pay_id     = $request->payable_id;
            $charge     = $request->get('document_id');
            $vendor     = $request->get('vendor_id');
            $unit       = $request->get('unit_id');
            $quanty     = $request->get('quantity');
            $user_id    = $request->get('user_id');
            $currency   = $request->get('currency');
            $price      = $request->get('price');

            for ($j=0; $j < count($charge); $j++) 
            { 
                $parts=explode(",",$price[$j]);
                $parts=array_filter($parts);
                $pay = (implode("",$parts));

                if ($pay_id[$j] != 0) 
                {
                    if(!empty($charge[$j]) && !empty($vendor[$j]))
                    {
                        $payable = Payable::find($pay_id[$j]);
                        $payable->user_id       = $user_id[$j];
                        $payable->jobsheet_id   = $jobsheet->id;
                        $payable->document_id   = $charge[$j];
                        $payable->vendor_id     = $vendor[$j];
                        $payable->unit_id       = $unit[$j];
                        $payable->quantity      = $quanty[$j];
                        $payable->currency      = $currency[$j];
                        $payable->price      	= $pay;
                        $payable->total         = $quanty[$j] * $pay;
                        $payable->save();
                    }
                } else {
                    $payable = new Payable();
                    $payable->user_id       = Auth::user()->id;
                    $payable->jobsheet_id   = $jobsheet->id;
                    $payable->document_id   = $charge[$j];
                    $payable->vendor_id     = $vendor[$j];
                    $payable->unit_id       = $unit[$j];
                    $payable->quantity      = $quanty[$j];
                    $payable->currency      = $currency[$j];
                    $payable->price         = $pay;
                    $payable->total         = $quanty[$j] * $pay;
                    $payable->save();
                }
                
            }

            //====UPDATE REIMBURSEMENTS - VENDOR======================
            $rmb_id = $request->rmb_id;
            $vendor_id = $request->rmb_vendor_id;
            
            for ($i=0; $i < count($rmb_id); $i++) 
            { 
                if (!empty($vendor_id[$i])) 
                {
                    $rmb = Reimbursement::find($rmb_id[$i]);    
                    $rmb->rmb_vendor_id = $vendor_id[$i];
                    $rmb->save();
                }
            }

            // =====INPUT & EDIT RC============================
            $rc_id          = $request->rc_id;
            $rc_document_id = $request->rc_document_id;
            $rc_vendor_id   = $request->rc_vendor_id;
            $rc_quantity    = $request->rc_quantity;
            $rc_unit_id     = $request->rc_unit_id;
            $rc_currency    = $request->rc_currency;

            for ($a=0; $a < count($rc_document_id); $a++) 
            { 
                $rcparts=explode(",",$request->rc_price[$a]);
                $rcparts=array_filter($rcparts);
                $rc_price = (implode("",$rcparts));

                if ($rc_id[$a] != 0) 
                {
                    if (!empty($rc_document_id[$a])) 
                    {
                        $rc = RC::find($rc_id[$a]);
                        $rc->jobsheet_id      = $jobsheet->id;
                        $rc->rc_document_id   = $rc_document_id[$a];
                        $rc->rc_vendor_id     = $rc_vendor_id[$a];
                        $rc->rc_unit_id       = $rc_unit_id[$a];
                        $rc->rc_quantity      = $rc_quantity[$a];
                        $rc->rc_currency      = $rc_currency[$a];
                        $rc->rc_price         = $rc_price;
                        $rc->rc_total         = $rc_price * $rc_quantity[$a];
                        $rc->rc_type          = 'pricing';
                        $rc->save();
                    }else{
                        $rc->delete();
                    }
                } else {
                    if (!empty($rc_document_id[$a])) 
                    {
                        RC::create([
                            'jobsheet_id'      => $jobsheet->id,
                            'rc_document_id'   => $rc_document_id[$a],
                            'rc_vendor_id'     => $rc_vendor_id[$a],
                            'rc_unit_id'       => $rc_unit_id[$a],
                            'rc_quantity'      => $rc_quantity[$a],
                            'rc_currency'      => $rc_currency[$a],
                            'rc_price'         => $rc_price,
                            'rc_total'         => $rc_price * $rc_quantity[$a],
                            'rc_type'          => 'pricing',
                        ]);
                    }
                }
            }

            $cekrevisi = Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'pricing')->count();
            if($cekrevisi > 0) {
            	$cekrevisi->delete();
            }
        }

        return redirect()->route('pricing.jobsheet.uncreated');
    }

    public function jobsheet_show(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();
        $reimbursements = Reimbursement::where('jobsheet_id', $jobsheet->id)->get();
        $rcs = RC::where('jobsheet_id', $jobsheet->id)->where('rc_type','pricing')->get();

        $pay_total_price = $payables->sum('price');
        $pay_total_price_idr = $payables->where('currency',1);
        $pay_total_price_usd = $payables->where('currency',2);

        $rmb_total_price = $reimbursements->sum('rmb_price');
        $rmb_total_price_idr = $reimbursements->where('rmb_currency',1);
        $rmb_total_price_usd = $reimbursements->where('rmb_currency',2);

        $rc_total_price  = $rcs->sum('rc_price');
        $rc_total_price_idr = $rcs->where('rc_currency',1);
        $rc_total_price_usd = $rcs->where('rc_currency',2);

        if (count($payables) > 0) {
            return view('jobsheet.pricing.show', compact('jobsheet','references','payables','reimbursements','rcs','pay_total_price','pay_total_price_idr','pay_total_price_usd','rmb_total_price','rmb_total_price_idr','rmb_total_price_usd','rc_total_price','rc_total_price_idr','rc_total_price_usd'));
        }

        return redirect()->route('pricing.jobsheet.index');
    }

    public function jobsheet_decline(Request $request, Datatables $datatables)
    {
        $this->validate($request, [
            'note' => 'required_with:receiver'
        ]);

        $jobsheet_id    = $request->jobsheet_id;
        $sender_id      = $request->sender_id;
        $note           = $request->note;
        $receiver       = $request->receiver;
        $role 			= User::find($receiver);

        Revision::create([
            'jobsheet_id'   => $jobsheet_id,
            'sender'        => $sender_id,
            'receiver'      => $receiver,
            'note'          => $note,
            'role'          => $role->role
        ]);

        $jobsheet = JobSheet::find($jobsheet_id);
        $sender = User::find($sender_id);
        $jobsheet->status = 'revisi-'.$sender->role;
        $jobsheet->save();

        return redirect()->route('pricing.jobsheet.index');
    }

    public function jobsheet_revision(Request $request)
    {
        $revisions = Revision::where('role', 'pricing')->get();
        return view('jobsheet.revision', compact('revisions'));
    }

    //==============================================================================================
    //              REQUEST
    //==============================================================================================

    public function request_list(Request $request)
    {
        $requests = RequestModel::where('status', 'requested')
            ->where('user_id', Auth::user()->getKey())
            ->get();
        //dd($requests);
        return view('request.pricing.index', compact('requests'));
    }

    public function request_create(Request $request)
    {
        //$jobSheetsRequested = RequestModel::where('status', 'requested')->where('type','operation')->pluck('jobsheet_id','jobsheet_id');
        $role = Auth::user()->role;
        if (in_array($role, ['admin','admin2'])) {
            $query = JobSheet::query();
        } else {
            $query = JobSheet::where('status', 'completed')
                ->where('operation_id', Auth::user()->getKey());
        }
        //$jobsheets = $query->whereNotIn('id',$jobSheetsRequested)->get();
        $jobsheets = $query->get();
        $controllerRole = $role;
        return view('request.pricing.create', compact('jobsheets','controllerRole'));
    }

    public function request_detail_jobsheet(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();
        $revisions   = Revision::where('jobsheet_id', $id)->get();
        $requestedModel = RequestModel::where('jobsheet_id', $id)->get()->toArray();
        $requestedPayableIds = $requestedModel ? array_pluck($requestedModel, 'pay_id', 'pay_id') : [];
        $requestedDates = $requestedModel ? array_pluck($requestedModel, 'tanggal', 'pay_id') : [];
        $defaultRequestDate = Carbon::now()->toDateString();
        $controllerRole = Auth::user()->role;
        $pay_total_price = $payables->sum('price');
        $pay_total_price_idr = $payables->where('currency',1);
        $pay_total_price_usd = $payables->where('currency',2);
        $rcs = RC::where('jobsheet_id', $id)->get();

        return view('request.pricing.detail_jobsheet', compact('jobsheet','references','payables','revisions',
            'requestedDates','requestedPayableIds','defaultRequestDate', 'controllerRole', 'pay_total_price',
            'pay_total_price_idr','pay_total_price_idr','pay_total_price_usd','rcs'));
    }

    public function request_store(Request $request)
    {
        try {
            $this->validate($request,[
                'payable_ids'  => 'required',
                'jobsheet_id'   => 'required'
            ]);
            $userId = \Auth::user()->getKey();
            $role = \Auth::user()->role;
            $requestDate = Carbon::now()->toDateString();
            if( ($payableIds = $request->get('payable_ids')) && ($jobsheetId = $request->get('jobsheet_id')) ){
                $requestDates = $request->get('request_dates');
                $i = 0;
                foreach($payableIds as $payableId){
                    $attributes = [
                        'jobsheet_id' => $jobsheetId,
                        'pay_id' => $payableId,
                        'user_id' => $userId,
                        'tanggal' => isset($requestDates[$i]) ? Carbon::createFromTimestamp(strtotime($requestDates[$i]))->toDateString() : $requestDate,
                        'status' => 'requested',
                        'type' => $role
                    ];
                    $requestModel = new RequestModel();
                    if ($requestModel->fill($attributes) && $requestModel->save()) {
                        $request->session()->put('message-success', 'Success Created Request');
                    }
                    $i++;
                }
            }
        }
        catch (\Exception $e) {
            $request->session()->put('message-error','Something Wrong');
            return redirect()->route('pricing.request.create');
        }

        return redirect()->route('pricing.request.detail-jobsheet', ['id'=>$jobsheetId]);
    }

    public function request_index(Request $request)
    {

    }
}