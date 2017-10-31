<?php

namespace App\Http\Controllers;

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
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\Payment;
use App\PaymentDoc;
use App\User;
use App\RC;
use App\Reimbursement;
use App\RequestModel;
use App\MasterRate;
use App\invoice;

class _PayableController extends Controller
{
    //
    public function __construct()
	{
		$this->middleware('role:payable,admin,approvepay');
	}

	public function jobsheet_index(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status',['completed','uncompleted'])->get();
        
        return view('jobsheet.index', compact('jobsheets'));
    }

    public function jobsheet_show(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();
        $reimbursements = Reimbursement::where('jobsheet_id', $id)->get();
        $rcs = RC::where('jobsheet_id', $id)->get();

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
            return view('jobsheet.payable.show', compact('jobsheet','references','payables','reimbursements','rcs','pay_total_price','pay_total_price_idr','pay_total_price_usd','rmb_total_price','rmb_total_price_idr','rmb_total_price_usd','rc_total_price','rc_total_price_idr','rc_total_price_usd'));
        }

        return redirect()->route('pricing.jobsheet.index');

        // return view('jobsheet.payable.show', compact('jobsheet','references','payables','reimbursements','rcs'));
    }

    public function jobsheet_edit(Request $request, Datatables $datatables, $id)
    {
    	$jobsheet = JobSheet::find($id);
    	$payables   = Payable::where('jobsheet_id', $id)->get();
        $references = Reference::where('jobsheet_id', $id)->get();
        $reimbursements = Reimbursement::where('jobsheet_id', $id)->get();
        $rcs = RC::where('jobsheet_id', $id)->get();

        return view('jobsheet.payable.edit', compact('jobsheet','references','payables','reimbursements','rcs'));
    }

    public function jobsheet_update(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        // $jobsheet->step_role = '2';
        // $jobsheet->save();

        if ($jobsheet) 
        {
            //==============UPDATE PAYABLE============
            $payable_id = $request->payable_id;
            $charge     = $request->get('document_id');
            $vendor     = $request->get('vendor_id');
            $unit       = $request->get('unit_id');
            $quanty     = $request->get('quantity');
            $user_id    = $request->get('user_id');
            $currency   = $request->get('currency');
            $price      = $request->get('price');

            for ($j=0; $j < count($charge); $j++) 
            { 
                if ($payable_id[$j] != 0) 
                {
                    $payable = Payable::find($payable_id[$j]);
                
                    if(!empty($charge[$j]))
                    {
                        $payable->user_id       = $user_id[$j];
                        $payable->jobsheet_id   = $jobsheet->id;
                        $payable->document_id   = $charge[$j];
                        $payable->vendor_id     = $vendor[$j];
                        $payable->unit_id       = $unit[$j];
                        $payable->quantity      = $quanty[$j];
                        $payable->currency      = $currency[$j];

                        $parts=explode(",",$price[$j]);
                        $parts=array_filter($parts);
                        $pay = (implode("",$parts));

                        $payable->price         = $pay;
                        $payable->total         = $pay * $quanty[$j];
                        $payable->save();
                    }else{
                        $payable->delete();
                    }
                } else {
                    if(!empty($charge[$j]) || !empty($vendor[$j]))
                    {
                        $payable = new Payable();
                        $payable->user_id       = Auth::user()->id;
                        $payable->jobsheet_id   = $jobsheet->id;
                        $payable->document_id   = $charge[$j];
                        $payable->vendor_id     = $vendor[$j];
                        $payable->unit_id       = $unit[$j];
                        $payable->quantity      = $quanty[$j];
                        $payable->currency      = $currency[$j];

                        $parts=explode(",",$price[$j]);
                		$parts=array_filter($parts);
                		$pay = (implode("",$parts));

                        $payable->price      	= $pay;
                        $payable->total         = $pay * $quanty[$j];
                        $payable->save();
                    }
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

            // =====INPUT & EDIT RC======================================
            $rcs = RC::where('jobsheet_id', $jobsheet->id)->get();
            
            $rc_id          = $request->rc_id;
            $rc_type        = $request->rc_type;
            $rc_document_id = $request->rc_document_id;
            $rc_vendor_id   = $request->rc_vendor_id;
            $rc_quantity    = $request->rc_quantity;
            $rc_unit_id     = $request->rc_unit_id;
            $rc_currency    = $request->rc_currency;

            for ($a=0; $a < count($rc_document_id); $a++) 
            { 
                if ($rc_id[$a] != 0) 
                {
                    $rc = RC::find($rc_id[$a]);
                    $rcparts=explode(",",$request->rc_price[$a]);
                    $rcparts=array_filter($rcparts);
                    $rc_price = (implode("",$rcparts));

                    if (!empty($rc_document_id[$a])) 
                    {
                        $rc->jobsheet_id      = $jobsheet->id;
                        $rc->rc_document_id   = $rc_document_id[$a];
                        $rc->rc_vendor_id     = $rc_vendor_id[$a];
                        $rc->rc_unit_id       = $rc_unit_id[$a];
                        $rc->rc_quantity      = $rc_quantity[$a];
                        $rc->rc_currency      = $rc_currency[$a];
                        $rc->rc_price         = $rc_price;
                        $rc->rc_total         = $rc_price * $rc_quantity[$a];
                        $rc->rc_type          = $rc_type[$a];
                        $rc->save();
                    }else{
                        $rc->delete();
                    }
                } else {
                    if (!empty($rc_document_id[$a])) 
                    {
                        $rcparts=explode(",",$request->rc_price[$a]);
                        $rcparts=array_filter($rcparts);
                        $rc_price = (implode("",$rcparts));

                        RC::create([
                            'jobsheet_id'      => $jobsheet->id,
                            'rc_document_id'   => $rc_document_id[$a],
                            'rc_vendor_id'     => $rc_vendor_id[$a],
                            'rc_unit_id'       => $rc_unit_id[$a],
                            'rc_quantity'      => $rc_quantity[$a],
                            'rc_currency'      => $rc_currency[$a],
                            'rc_price'         => $rc_price,
                            'rc_total'         => $rc_price * $rc_quantity[$a],
                            'rc_type'          => $rc_type[$a],
                        ]);
                    }
                }
            }


            // $cekrevisi = Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'pricing')->count();
            // if($cekrevisi > 0) {
            // 	$cekrevisi->delete();
            // }
        }

        return redirect()->route('payable.jobsheet.index');
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
        $receiver_role  = User::find($receiver);

        Revision::create([
            'jobsheet_id'   => $jobsheet_id,
            'sender'        => $sender_id,
            'receiver'      => $receiver,
            'note'          => $note,
            'role'          => $receiver_role->role
        ]);

        $jobsheet = JobSheet::find($jobsheet_id);
        $sender = User::find($sender_id);
        $jobsheet->status = 'revisi-'.$sender->role;
        $jobsheet->save();

        return redirect()->route('payable.index');   
    }

    public function payment_payable(Request $request)
    {
        $jobsheets = RequestModel::where('status','approved')->where('type','!=','marketing')->select('jobsheet_id','type')->distinct()->get();

        return view('payable._payment', compact('jobsheets'));
    }

    public function payment_rc(Request $request)
    {
        $jobsheets = RequestModel::where('status','requested')->where('type','marketing')->select('jobsheet_id','type')->distinct()->get();

        return view('payable._paymentrc', compact('jobsheets'));
    }

    public function paymentcreated(Request $request, $id, $role)
    {
        $roles = $role;

        if ($role == 'operation' || $role == 'pricing') {
            $jobsheet = JobSheet::find($id);
            $payables = RequestModel::where('jobsheet_id', $id)->where('status','requested')->where('type','!=','marketing')->get();
            
            return view('payable._paymentcreated', compact('jobsheet','payables','roles'));
        } elseif ($role == 'marketing') {
            $jobsheet = JobSheet::find($id);
            $payables = RequestModel::where('jobsheet_id', $id)->where('status','requested')->where('type','marketing')->get();

            return view('payable._paymentcreated', compact('jobsheet','payables','roles'));
        }

    }

    public function paymentsubmit(Request $request, Datatables $datatables)
    {
        $roles = $request->input('roles');
        $id = $request->input('id');
        $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('date_rate'));

        if($roles == 'operation' || $roles == 'pricing') {
            $pay = Payable::where('id', $id[0])->first();
            $vid = $pay->vendor_id;
        }elseif ($roles == 'marketing') {
            $rc = RC::where('id', $id[0])->first();
            $vid = $rc->rc_vendor_id;
        }

        $date_rate = MasterRate::where('date', $convert_date->toDateString())->first();
        $add_type = $request->input('add_type');
        $add_amount = $request->input('add_amount');
        // $sum_amount = array_sum($add_amount);
        $datereq = RequestModel::where('pay_id',$id[0])->first();

        return view('payable._paymentsubmit', compact('date_rate','id','vid','add_type','add_amount','sum_amount','datereq','roles'));
    }

    public function paymentstore(Request $request)
    {
        $roles = $request->roles;

        $vendor_id      = $request->vendor_id;
        $paymentt        = $request->payment;
        $currency       = $request->currency;
        $note           = $request->note;
        $date_payment = Carbon::createFromFormat('d-m-Y', $request->date_payment);

        // $payment = Payment::create([
        //     'code'          => 'xxx',
        //     'vendor_id'     => $vendor_id,
        //     'payment'       => $payment,
        //     'currency'      => $currency,
        //     'date_payment'  => $date_payment->toDateString(),
        //     'note'          => $note,
        //     'status'        => 'created'
        // ]);

        $payment = new Payment;
        // $payment->code = '0001';
        $payment->vendor_id = $vendor_id;
        $payment->payment = $paymentt;
        $payment->currency = $currency;
        $payment->date_payment = $date_payment->toDateString();
        $payment->note = $note;
        $payment->type = $roles;
        $payment->status = 'created';
        $payment->timestamps = true;
        $payment->save();

        if ($payment->id < 10) {
            $payment->code         = '000'.$payment->id;
        } elseif ($payment->id >= 10 && $payment->id < 100) {
            $payment->code         = '00'.$payment->id;
        } elseif ($payment->id >= 100 && $payment->id < 1000) {
            $payment->code         = '0'.$payment->id;
        } else {
            $payment->code         = ''.$payment->id;
        }

        $payment->save();

        // ===================================================================================
        $add_type = $request->add_type;
        $add_amount = $request->add_amount;

        for ($y=0; $y < count($add_type) ; $y++) 
        { 
            if (!empty($add_type[$y])) 
            {
                $type   = $add_type[$y];

                $parts=explode(",",$add_amount[$y]);
                $parts=array_filter($parts);
                $a = (implode("",$parts));
                $amount = $a;

                PaymentDoc::create([
                    'payment_id'=> $payment->id,
                    'add_type'  => $type,
                    'add_amount'=> $amount
                ]);
            }
        }

        // ===================================================================================
        $payable_id = $request->payable_id;
        $rate       = $request->rate;
        
        if ($roles == 'operation' || $roles == 'pricing') {
            for ($x=0; $x < count($payable_id); $x++) 
            {
                $parts=explode(",",$rate[$x]);
                $parts=array_filter($parts);
                $r = (implode("",$parts));

                $payable = Payable::find($payable_id[$x]);
                $payable->payment_id = $payment->id;
                $payable->rate = $r;
                $payable->save();

                $updaterequest = RequestModel::where('pay_id', $payable_id[$x])->where('type','!=','marketing')->first();
                $updaterequest->status = 'created';
                $updaterequest->save();
            }
        } elseif ($roles == 'marketing') {
            for ($x=0; $x < count($payable_id); $x++) 
            {
                $parts=explode(",",$rate[$x]);
                $parts=array_filter($parts);
                $r = (implode("",$parts));

                $payable = RC::find($payable_id[$x]);
                $payable->payment_id = $payment->id;
                $payable->rate = $r;
                $payable->save();

                $updaterequest = RequestModel::where('pay_id', $payable_id[$x])->where('type','marketing')->first();
                $updaterequest->status = 'created';
                $updaterequest->save();
            }
        }


        // =====INPUT & EDIT RC============================
        // $rcs = RC::where('jobsheet_id', $jobsheet->id)->get();
        
        // $rc_document_id = $request->rc_document_id;
        // $rc_vendor_id   = $request->rc_vendor_id;
        // $rc_quantity    = $request->rc_quantity;
        // $rc_unit_id     = $request->rc_unit_id;
        // $rc_currency    = $request->rc_currency;

        // if($rcs->count() < 1)
        // {
        //     for ($a=0; $a < count($rc_document_id); $a++) 
        //     { 
        //         if (!empty($rc_document_id[$a])) 
        //         {
        //             $rcparts=explode(",",$request->rc_price[$a]);
        //             $rcparts=array_filter($rcparts);
        //             $rc_price = (implode("",$rcparts));

        //             RC::create([
        //                 'jobsheet_id'      => $jobsheet->id,
        //                 'rc_document_id'   => $rc_document_id[$a],
        //                 'rc_vendor_id'     => $rc_vendor_id[$a],
        //                 'rc_unit_id'       => $rc_unit_id[$a],
        //                 'rc_quantity'      => $rc_quantity[$a],
        //                 'rc_currency'      => $rc_currency[$a],
        //                 'rc_price'         => $rc_price,
        //                 'rc_total'         => $rc_price * $rc_quantity[$a],
        //                 'rc_type'          => 'pricing',
        //             ]);
        //         }
        //     }
        // }else{
        //     DB::table('rc')->where('jobsheet_id', $jobsheet->id)->delete();

        //     for ($a=0; $a < count($rc_document_id); $a++) 
        //     { 
        //         if (!empty($rc_document_id[$a])) 
        //         {
        //             $rcparts=explode(",",$request->rc_price[$a]);
        //             $rcparts=array_filter($rcparts);
        //             $rc_price = (implode("",$rcparts));

        //             RC::create([
        //                 'jobsheet_id'      => $jobsheet->id,
        //                 'rc_document_id'   => $rc_document_id[$a],
        //                 'rc_vendor_id'     => $rc_vendor_id[$a],
        //                 'rc_unit_id'       => $rc_unit_id[$a],
        //                 'rc_quantity'      => $rc_quantity[$a],
        //                 'rc_currency'      => $rc_currency[$a],
        //                 'rc_price'         => $rc_price,
        //                 'rc_total'         => $rc_price * $rc_quantity[$a],
        //                 'rc_type'          => 'pricing',
        //             ]);
        //         }
        //     }
        // }
        if ($roles == 'operation' || $roles == 'pricing') {
            return redirect()->route('payable.listpayment');
        } elseif ($roles == 'marketing') {
            return redirect()->route('payable.listpaymentrc');
        }
    }

    public function listpayment(Request $request)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','created')->where('type','!=','marketing')->get();

        return view('payable._listpayment', compact('jobsheets'));
    }

    public function listpaymentrc(Request $request)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','created')->where('type','marketing')->get();

        return view('payable._listpayment', compact('jobsheets'));
    }

    public function showpayment(Request $request, $id, $role)
    {
        $roles = $role;

        if ($role == 'operation' || $role == 'pricing') {
            $payment = Payment::find($id);
            $payable = Payable::where('payment_id',$payment->id)->get();
            $paydoc = PaymentDoc::where('payment_id',$payment->id)->get();
            $sum_amount = PaymentDoc::where('payment_id',$payment->id)->sum('add_amount');
    
            return view('payable._showpayment', compact('payment','payable','paydoc','sum_amount','roles'));
        } elseif ($role == 'marketing') {
            $payment = Payment::find($id);
            $payable = RC::where('payment_id',$payment->id)->get();
            $paydoc = PaymentDoc::where('payment_id',$payment->id)->get();
            $sum_amount = PaymentDoc::where('payment_id',$payment->id)->sum('add_amount');

            return view('payable._showpayment', compact('payment','payable','paydoc','sum_amount','roles'));
        }
    }

    public function report(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::all();
        return view('jobsheet.operation.report', compact('jobsheets'));
    }

    public function reportpayment(Request $request, Datatables $datatables)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','created')->where('type','!=','marketing')->get();
        return view('payable._reportpayment', compact('jobsheets'));
    }

    public function reportpaymentrc(Request $request, Datatables $datatables)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','created')->where('type','marketing')->get();
        return view('payable._reportpayment', compact('jobsheets'));
    }

    public function overpayment()
    {
        $invoicerec = invoice::where('status', 3)->get();
        
        return view('receivable._index', compact('invoicerec'));
    }

    public function showinvoice(Request $request, $id, $type)
    {
        if($type == 'receivable') {
            $invoice = Invoice::find($id);
            $jobsheet = JobSheet::find($invoice->jobsheet_id);
            $charges = Receivable::where('rec_invoice_id', $invoice->id)->get();
            $references = Reference::where('jobsheet_id', $id)->get();

            $receivables = Receivable::where('rec_invoice_id', $invoice->id)->first();

            $max = $charges->max('rec_total');
            $tot = $charges->sum('rec_total');
        } elseif ($type == 'reimbursement') {
            $invoice = Invoice::find($id);
            $jobsheet = JobSheet::find($invoice->jobsheet_id);
            $charges = Reimbursement::where('rmb_invoice_id', $invoice->id)->get();
            $references = Reference::where('jobsheet_id', $id)->get();

            $receivables = Reimbursement::where('rmb_invoice_id', $invoice->id)->first();

            $max = $charges->max('rmb_total');
            $tot = $charges->sum('rmb_total');
        }

        return view('receivable._show', compact('invoice','jobsheet','charges','references','receivables','max','tot','type'));
    }

    public function requestoverpayment(Request $request) {
        $id = $request->input('id');
        $date = $request->input('date');

        for ($i=0; $i < count($id) ; $i++) { 
            $invoice = invoice::find($id[$i]);
            $invoice->date_request = $date[$i];
            $invoice->save();
        }

        return redirect()->route('payable.overpayment');
    }

    public function listoverpayment(Request $request) {
        $invoicerec = invoice::where('date_request','>',0)->get();
        
        return view('receivable._index', compact('invoicerec'));
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
        return view('request.payable.index', compact('requests'));
    }

    public function request_create(Request $request)
    {
        //$jobSheetsRequested = RequestModel::where('status', 'requested')->where('type','operation')->pluck('jobsheet_id','jobsheet_id');
        $role = Auth::user()->role;
        if (in_array($role, ['admin','admin2'])) {
            $query = JobSheet::query();
        } else {
            $query = JobSheet::where('status', 'completed');
        }
        //$jobsheets = $query->whereNotIn('id',$jobSheetsRequested)->get();
        $jobsheets = $query->get();
        $controllerRole = $role;
        return view('request.payable.create', compact('jobsheets','controllerRole'));
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

        return view('request.payable.detail_jobsheet', compact('jobsheet','references','payables','revisions',
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
            return redirect()->route('payable.request.create');
        }

        return redirect()->route('payable.request.detail-jobsheet', ['id'=>$jobsheetId]);
    }

    public function request_index(Request $request)
    {

    }
}
