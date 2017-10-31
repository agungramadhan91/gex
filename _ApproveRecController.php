<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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

class _ApproveRecController extends Controller
{
    //
    public function __construct()
	{
		$this->middleware('role:approverec');
	}

	public function invoicecancel()
	{
		$invoicerec = invoice::where('status', 2)->get();
        
        return view('receivable._index', compact('invoicerec'));
	}

	public function invoice()
	{
		$invoicerec = invoice::where('status',4)->get();
        
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

    public function approve(Request $request, $id)
    {
        $invoice = invoice::find($id);
        $invoice->status = 1;
        $invoice->save();

        return redirect()->route('approverec.invoice');
    }

    public function approverevisi(Request $request, $id)
    {
        $invoice = invoice::find($id);
        $invoice->status = 5;
        $invoice->save();

        return redirect()->route('approverec.invoicecancel');
    }

    public function decline(Request $request, Datatables $datatables, $id)
    {
        $jobsheet_id    = JobSheet::find($id);
        // $jobsheet_id    = $request->jobsheet_id;
        $sender_id      = $request->sender_id;
        $note           = $request->note;
        $receiver       = $request->receiver;
        $role           = User::find($receiver);

        // Revision::create([
        //     'jobsheet_id'   => $jobsheet_id,
        //     'sender'        => $sender_id,
        //     'receiver'      => $receiver,
        //     'note'          => $note,
        //     'role'          => $role->role
        // ]);

        $revisi = new Revision;
        $revisi->jobsheet_id = $jobsheet_id;
        $revisi->sender = $sender_id;
        $revisi->receiver = $receiver;
        $revisi->note = $note;
        $revisi->role = $role->role;
        $revisi->timestamps = true;
        $revisi->save();

        $jobsheet = JobSheet::find($jobsheet_id);
        $jobsheet->status = 'revisi';
        $jobsheet->save();

        return redirect()->route('approverec.invoice');
    }
}
