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
use App\Reimbursement;
use App\Payment;
use App\PaymentDoc;
use App\RequestModel;

class _ApprovePay extends Controller
{
    //
    public function __construct()
	{
		$this->middleware('role:approvepay,admin,payable');
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

    public function approvepayment(Request $request, $id, $role)
    {
        $payment = Payment::find($id);
        $payment->status = 'approved';
        $payment->save();

        if ($role == 'operation' || $role == 'pricing') {
            return redirect('/approve/payment');
        } elseif ($role == 'marketing') {
            return redirect('/approve/payment-rc');
        }
    }

    public function rejectpayment(Request $request, $id, $role)
    {
        $payment = Payment::find($id);
        // $payment->status = 'reject';
        // $payment->save();

        if ($role == 'operation' || $role == 'pricing') {
            
            $payable = Payable::where('payment_id', $id)->get();
            
            for ($i=0; $i < count($payable); $i++) {
                $pay = Payable::where('payment_id', $payment->id)->first();
                $pay->rate = null;
                $pay->payment_id = null;
                $pay->save();

                $request = RequestModel::where('pay_id', $payable[$i]->id)->where('type','!=','marketing')->first();
                $request->status = 'requested';
                $request->save();
            }
            
            $paymentdoc = PaymentDoc::where('payment_id', $payment->id)->get();

            foreach ($paymentdoc as $key) {
                $key->delete();
            }

            $payment->delete();

            return redirect('/approve/payment');
        } elseif ($role == 'marketing') {

            $rc = RC::where('payment_id', $id)->get();

            for ($i=0; $i < count($rc); $i++) {
                $rcc = RC::where('payment_id', $payment->id)->first();
                $rcc->rate = null;
                $rcc->payment_id = null; 
                $rcc->save();

                $request = RequestModel::where('pay_id', $rc[$i]->id)->where('type','marketing')->first();
                $request->status = 'requested';
                $request->save();
            }

            $paymentdoc = PaymentDoc::where('payment_id', $payment->id)->get();

            foreach ($paymentdoc as $key) {
                $key->delete();
            }

            $payment->delete();

            return redirect('/approve/payment-rc');
        }
    }

    public function payable(Request $request)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','approved')->where('type','!=','marketing')->get();

        return view('payable._listpayment', compact('jobsheets'));
    }

    public function paymentrc(Request $request)
    {
        // $jobsheets = RequestModel::where('status','created')->where('type','!=','marketing')->get();
        $jobsheets = Payment::where('status','approved')->where('type','marketing')->get();

        return view('payable._listpayment', compact('jobsheets'));
    }

    public function report(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::all();
        
        // return json_encode($jobsheets);
        return view('report.approval', compact('jobsheets'));
    }
}
