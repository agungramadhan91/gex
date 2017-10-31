<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\MasterDocument;
use App\MasterCustomer;
use App\MasterVendor;
use App\MasterUnit;
use App\MasterPort;
use App\MasterRate;
use App\Reimbursement;
use App\InvoiceDocument;
use App\ReceivablePayment;
use App\Receivable;
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\Invoice;
use App\User;
use App\RC;
use PDF;

class _ReceivableController extends Controller
{
    public function __construct()
	{
		$this->middleware('role:receivable');
	}

	public function invoice()
	{
		$invoicerec = Invoice::all();
        
        return view('receivable._index', compact('invoicerec'));
	}

	public function show(Request $request, $id, $type)
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

    public function pdf(Request $request, $id, $type) {
    	if($type == 'receivable') {
    		$invoice = Invoice::find($id);
	        $arr = Receivable::where('rec_invoice_id', $invoice->id)->get();
	        $receivable = $arr->toArray();
	        $document = InvoiceDocument::where('invoice_id', $invoice->id)->get();
	        $max = $arr->max('rec_total');
	        $tot = $arr->sum('rec_total');
	        $pdf = PDF::loadView('invoice.printgexpdf', compact('invoice','receivable','max','tot','document'))->setPaper('a4', 'portrait');
	        return $pdf->stream('invoice-receivable.pdf');
    	} elseif ($type == 'reimbursement') {
	        $invoice = Invoice::find($id);
	        $arr = Reimbursement::where('rmb_invoice_id', $invoice->id)->get();
	        $receivable = $arr->toArray();
	        $document = InvoiceDocument::where('invoice_id', $invoice->id)->get();
	        $max = $arr->max('rmb_total');
	        $tot = $arr->sum('rmb_total');
	        $pdf = PDF::loadView('invoice.printgexrmbpdf', compact('invoice','receivable','max','tot','document'))->setPaper('a4', 'portrait');
        	return $pdf->stream('invoice-reimbursement.pdf');
	    }
    }

    public function adddate(Request $request, $id) {
    	$due_date = $request->input('due_date');
    	$receipt_date = $request->input('receipt_date');

    	if(!empty($due_date && $receipt_date)) {
    		$invoice = invoice::find($id);
    		$invoice->due_date = $due_date;
    		$invoice->receipt_date = $receipt_date;
    		$invoice->save();
    	}

    	return redirect()->route('receivable.invoice');
    }

	public function create_payment(Request $request)
	{
		// $invoicerec = invoice::where('status',0)->get();
		// $rate = MasterRate::find(1);

		$customer = $request->input('customer_id');
		$date = $request->input('date_rate');
		$bank = $request->payment_bank;
		$currency = $request->currency;

		if(!empty($customer && $date && $bank)) {
			$invoicerec = invoice::where('customer_id', $customer)->where('status',0)->get();
			$rate = MasterRate::where('date', $date)->first();
			$tes = 'filter'.$date;
		}else{
			$invoicerec = invoice::where('status',0)->get();
			$rate = MasterRate::find(1);
			$tes = "ga filter";
		}

		return view('receivable.create_payment', compact('invoicerec','rate','customer','tes'));
	}

	public function create_overpayment(Request $request)
	{
		$customer = $request->input('customer_id');
		$date = $request->input('date_rate');

		if(!empty($customer && $date)) {
			$invoicerec = invoice::where('customer_id', $customer)->where('status',3)->get();
			$rate = MasterRate::where('date', $date)->first();
			$tes = 'filter'.$date;
		}else{
			$invoicerec = invoice::where('status',3)->get();
			$rate = MasterRate::find(1);
			$tes = "ga filter";
		}

		return view('receivable.create_payment', compact('invoicerec','rate','customer','tes'));
	}

	// public function filter(Request $request) {
	// 	$customer = $request->input('customer_id');
	// 	$date = $request->input('date_rate');

	// 	if(!empty($customer && $date)) {
	// 		$invoicerec = invoice::where('customer_id', $customer)->where('status',0)->get();
	// 		$rate = MasterRate::where('date', $date)->first();
	// 	}else{
	// 		$invoicerec = invoice::where('status',0)->get();
	// 		$rate = MasterRate::find(1);
	// 	}

	// 	return view('receivable.create_payment', compact('invoicerec','rate'));
	// }

	// public function filterover(Request $request) {
	// 	$customer = $request->input('customer_id');
	// 	$date = $request->input('date_rate');

	// 	if(!empty($customer && $date)) {
	// 		$invoicerec = invoice::where('customer_id', $customer)->where('status',3)->get();
	// 		$rate = MasterRate::where('date', $date)->first();
	// 	}else{
	// 		$invoicerec = invoice::where('status',3)->get();
	// 		$rate = MasterRate::find(1);
	// 	}

	// 	return view('receivable.create_payment', compact('invoicerec','rate'));
	// }

	public function payment_store(Request $request)
	{
		$currency = $request->input('currency');
		$bank_id = $request->input('bank_id');

		$invoice_id = $request->input('id');
		$amount_rec = $request->input('amount_rec');
		$rate = $request->input('rate');
		$pph = $request->input('pph');
		$adm = $request->input('adm_bank');
		$other = $request->input('other');
		$remarks = $request->input('remarks');

		if (!empty($invoice_id)) {
			for ($i=0; $i < count($invoice_id); $i++) { 
				
				$invoice = invoice::find($invoice_id[$i]);
				// $cek = ReceivablePayment::where('invoice_id', $invoice_id[$i])->get();
				$rec_total = Receivable::where('rec_invoice_id', $invoice_id[$i])->sum('rec_total');
				$rmb_total = Reimbursement::where('rmb_invoice_id', $invoice_id[$i])->sum('rmb_total');

				$rec_payment = new ReceivablePayment;
				$rec_payment->no_form = 'xxx';
				$rec_payment->customer_id = $invoice->customer_id;
				$rec_payment->currency = $currency;
				$rec_payment->payment = $bank_id;

				$rec_payment->invoice_id = $invoice_id[$i];
				$rec_payment->jobsheet_id = $invoice->jobsheet_id;

				// $parts=explode(",",$amount_rec[$i]);
    //             $parts=array_filter($parts);
    //             $amount_rec_fix = (implode("",$parts));
				
				// if (count($cek) > 0) {
				// 	$rec_payment->amount_rec = $cek->amount_rec + $amount_rec[$i];
				// }else{
				$rec_payment->amount_rec = $amount_rec[$i];
				// }
				
				$rec_payment->rate = $rate[$i];
				$rec_payment->pph = $pph[$i];
				$rec_payment->adm_bank = $adm[$i];
				$rec_payment->other = $other[$i];
				$rec_payment->remarks = $remarks[$i];
				$rec_payment->note = 'normal';
				$rec_payment->timestamps = true;
				$rec_payment->save();

				$cek = ReceivablePayment::where('invoice_id', $invoice_id[$i])->sum('amount_rec');

				if($invoice->type == 'receivable') {
					if ($cek == $rec_total) {
						$invoice->status = 4;
					}elseif ($cek > $rec_total) {
						$invoice->status = 3;
					}
				}elseif($invoice->type == 'reimbursement') {
					if ($cek == $rmb_total) {
						$invoice->status = 4;
					}elseif ($cek > $rmb_total) {
						$invoice->status = 3;
					}
				}
				$invoice->save();

			}
		}

		return redirect()->route('receivable.payment.create');
	}

	public function payment_storeover(Request $request)
	{
		$currency = $request->input('currency');
		$bank_id = $request->input('bank_id');

		$invoice_id = $request->input('id');
		$amount_rec = $request->input('amount_rec');
		$rate = $request->input('rate');
		$pph = $request->input('pph');
		$adm = $request->input('adm_bank');
		$other = $request->input('other');
		$remarks = $request->input('remarks');

		if (!empty($invoice_id)) {
			for ($i=0; $i < count($invoice_id); $i++) { 
				
				$invoice = invoice::find($invoice_id[$i]);
				// $cek = ReceivablePayment::where('invoice_id', $invoice_id[$i])->get();
				$rec_total = Receivable::where('rec_invoice_id', $invoice_id[$i])->sum('rec_total');
				$rmb_total = Reimbursement::where('rmb_invoice_id', $invoice_id[$i])->sum('rmb_total');

				$rec_payment = new ReceivablePayment;
				$rec_payment->no_form = 'xxx';
				$rec_payment->customer_id = $invoice->customer_id;
				$rec_payment->currency = $currency;
				$rec_payment->payment = $bank_id;

				$rec_payment->invoice_id = $invoice_id[$i];
				$rec_payment->jobsheet_id = $invoice->jobsheet_id;

				// $parts=explode(",",$amount_rec[$i]);
    //             $parts=array_filter($parts);
    //             $amount_rec_fix = (implode("",$parts));
				
				// if (count($cek) > 0) {
				// 	$rec_payment->amount_rec = $cek->amount_rec + $amount_rec[$i];
				// }else{
				$rec_payment->amount_rec = $amount_rec[$i];
				// }
				
				$rec_payment->rate = $rate[$i];
				$rec_payment->pph = $pph[$i];
				$rec_payment->adm_bank = $adm[$i];
				$rec_payment->other = $other[$i];
				$rec_payment->remarks = $remarks[$i];
				$rec_payment->note = 'overpayment';
				$rec_payment->timestamps = true;
				$rec_payment->save();

				$cek = ReceivablePayment::where('invoice_id', $invoice_id[$i])->where('note','normal')->sum('amount_rec');
				$cek2 = ReceivablePayment::where('invoice_id', $invoice_id[$i])->where('note','overpayment')->sum('amount_rec');
				$fix_cek = $cek - $cek2;

				if($invoice->type == 'receivable') {
					if ($fix_cek == $rec_total) {
						$invoice->status = 4;
					}elseif ($cek > $rec_total) {
						$invoice->status = 3;
					}
				}elseif($invoice->type == 'reimbursement') {
					if ($fix_cek == $rmb_total) {
						$invoice->status = 4;
					}elseif ($cek > $rmb_total) {
						$invoice->status = 3;
					}
				}
				$invoice->save();

			}
		}

		return redirect()->route('receivable.payment.create');
	}

	public function history()
	{
		$invoicerec = invoice::all();
        
        return view('receivable._index', compact('invoicerec'));
	}

	public function profit_index(Request $request)
	{
		$from_date = $request->from_date;
		$to_date = $request->to_date;

		if (!empty($from_date) && !empty($to_date)) {
			$jobsheets = DB::table('jobsheets')
						->where('date','>=', Carbon::createFromFormat('d-m-Y', $from_date))
						->where('date','<',Carbon::createFromFormat('d-m-Y', $to_date))
						->get();
			$invoices = Invoice::all();
			$payables = Payable::all();
			$receivables = Receivable::all();
		}else{
			$jobsheets = JobSheet::all();
		}
        
        return view('receivable.profit.index', compact('jobsheets','invoices','payables','receivables','from_date','to_date'));
	}

	public function profit_show(Request $request, $id)
	{

		// if (!empty($from_date)) {
		// 	// $jobsheets = JobSheet::all();
		// 	// $jobsheets = JobSheet::where('date','>=', Carbon::createFromFormat('d-m-Y', $from_date))->where('date','<',Carbon::createFromFormat('d-m-Y', $to_date))->get();
		// 	$jobsheets = DB::table('jobsheets')->where('date','>=', Carbon::createFromFormat('d-m-Y', $from_date))->where('date','<',Carbon::createFromFormat('d-m-Y', $to_date))->get();
		// 	$invoices = Invoice::all();
		// 	$payables = Payable::all();
		// 	$receivables = Receivable::all();
		// }else{
		// 	$jobsheets = JobSheet::all();
		// }
        
  //       return view('receivable.profit.index', compact('jobsheets','invoices','payables','receivables','from_date','to_date'));
	}

	public function detailhistory(Request $request, $id) {
		$invoice = invoice::find($id);
		$rec = ReceivablePayment::where('invoice_id', $id)->get();

		return view('receivable.detailhistory', compact('rec','invoice'));
	}
}
