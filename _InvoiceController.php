<?php

namespace App\Http\Controllers;

use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\MasterCustomer;
use App\MasterCurrency;
use App\MasterDocument;
use App\MasterTerm;
use App\MasterUnit;

use App\Reimbursement;
use App\Receivable;
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\User;
use App\RC;
use App\Invoice;
use App\InvoiceDocument;
use PDF;

class _InvoiceController extends Controller
{
    public function __construct()
	{
		$this->middleware('role:invoice,admin');
	}

    public function receivable_uncreated(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', 'completed')->get();
        
        return view('invoice.receivable.uncreated_index', compact('jobsheets'));
    }

    public function receivable_selection(Request $request, Datatables $datatables, $id)
    {
    	$jobsheet = JobSheet::find($id);
    	$references = Reference::where('jobsheet_id', $id)->get();
    	$receivables = Receivable::where('jobsheet_id', $id)->where('rec_invoice_id',null)->get();

        return view('invoice.receivable.selection', compact('jobsheet','references','receivables'));
    }

    public function receivable_decline(Request $request, Datatables $datatables, $job_id)
    {
        $jobsheet       = JobSheet::find($job_id);
        $sender_id      = $request->sender_id;
        $note           = $request->note;
        $receiver       = $request->receiver;
        $role           = User::find($receiver);

        Revision::create([
            'jobsheet_id'   => $jobsheet->id,
            'sender'        => $sender_id,
            'receiver'      => $receiver,
            'note'          => $note,
            'role'          => $role->role
        ]);

        // $jobsheet = JobSheet::find($jobsheet_id);
        $sender = User::find($sender_id);
        $jobsheet->status = 'revisi-'.$sender->role;
        $jobsheet->save();

        return redirect()->route('invoice.receivable.uncreated');
    }

    public function receivable_create(Request $request, Datatables $datatables, $id)
    {   
        $charges = $request->input('charges');
        $total = $request->input('total');

        if (!empty($charges) || !empty($total)) {
            $max = max($total);
            $tot = array_sum($total);

        	$jobsheet = JobSheet::find($id);
        	$references = Reference::where('jobsheet_id', $id)->get();
            
            return view('invoice.receivable.create', compact('jobsheet','references','charges','max','tot'));
        } else {
            return redirect()->route('invoice.receivable.index');
        }
    }

    public function receivable_store(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $jobsheet->step_role = 3;
        $jobsheet->save();

        $references = Reference::where('jobsheet_id', $id)->get();

        $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('date'));
        $generate_code = 'AR/'.$convert_date->format('m').'/'.$convert_date->format('d');

        $saveinvoice = new Invoice;
        // $saveinvoice->code = $generate_code;
        $saveinvoice->customer_id = $request->input('customer');
        $saveinvoice->jobsheet_id = $jobsheet->id;
        $saveinvoice->bank_id = $request->input('bank_id');
        $saveinvoice->tanggal = $convert_date->toDateString();
        $saveinvoice->status = 0;
        $saveinvoice->approval = 0;
        $saveinvoice->type = 'receivable';
        $saveinvoice->efaktur = '-';
        $saveinvoice->timestamps = true;
        $saveinvoice->save();

        if ($saveinvoice->id < 10) {
            $saveinvoice->code         = $generate_code.'/000'.$saveinvoice->id;
        } elseif ($saveinvoice->id >= 10 && $saveinvoice->id < 100) {
            $saveinvoice->code         = $generate_code.'/00'.$saveinvoice->id;
        } elseif ($saveinvoice->id >= 100 && $saveinvoice->id < 1000) {
            $saveinvoice->code         = $generate_code.'/0'.$saveinvoice->id;
        } else {
            $saveinvoice->code         = $generate_code.'/'.$saveinvoice->id;
        }
        
        $saveinvoice->save();

        $ref = $request->input('ref');
        for ($i=0; $i < count($ref); $i++) { 

            $parts=explode("-",$ref[$i]);

            $saveref = new InvoiceDocument;
            $saveref->invoice_id = $saveinvoice->id;
            $saveref->name = $parts[0];
            $saveref->no_ref = $parts[1];
            $saveref->timestamps = true;
            $saveref->save();
        }

        $rec_id = $request->input('rec_id');
        for ($j=0; $j < count($rec_id); $j++) { 
            $receivable = Receivable::find($rec_id[$j]);
            $receivable->rec_invoice_id = $saveinvoice->id;
            $receivable->save();
        }

        return redirect()->route('invoice.receivable.index');
    }

    public function receivable_index(Request $request, Datatables $datatables)
    {
        $invoicerec = invoice::where('type', 'receivable')->get();
        
        return view('invoice.receivable.index', compact('jobsheets','invoicerec'));
    }

    public function receivable_show(Request $request, Datatables $datatables, $id, $rec_id)
    {
        $invoice = Invoice::find($rec_id);
        $jobsheet = JobSheet::find($id);
        $charges = Receivable::where('rec_invoice_id', $invoice->id)->get();
        $references = Reference::where('jobsheet_id', $id)->get();

        $receivables = Receivable::where('rec_invoice_id', $invoice->id)->first();

        $max = $charges->max('rec_total');
        $tot = $charges->sum('rec_total');

        return view('invoice.receivable.show', compact('invoice','jobsheet','charges','references','receivables','max','tot'));
    }

    // public function createdinvoice(Request $request, Datatables $datatables, $id)
    // {
    //  $jobsheet = JobSheet::find($id);
    //  $references = Reference::where('jobsheet_id', $id)->get();
    //  $receivable = Receivable::where('jobsheet_id', $id)->get();

    //     return view('invoice._createdinvoice', compact('jobsheet','references','receivable'));
    // }

    // public function declinereceivable(Request $request, Datatables $datatables, $id)
    // {
    //     $jobsheet_id    = JobSheet::find($id);
    //     // $jobsheet_id    = $request->jobsheet_id;
    //     $sender_id      = $request->sender_id;
    //     $note           = $request->note;
    //     $receiver       = $request->receiver;
    //     $role           = User::find($receiver);

    //     Revision::create([
    //         'jobsheet_id'   => $jobsheet_id,
    //         'sender'        => $sender_id,
    //         'receiver'      => $receiver,
    //         'note'          => $note,
    //         'role'          => $role->role
    //     ]);

    //     $jobsheet = JobSheet::find($jobsheet_id);
    //     $jobsheet->status = 'revisi';
    //     $jobsheet->save();

    //     return redirect()->route('invoice.receivable.uncreatedreceivable');
    // }

    public function pdf(Request $request, $id) {
        $invoice = Invoice::find($id);
        $arr = Receivable::where('rec_invoice_id', $invoice->id)->get();
        $receivable = $arr->toArray();
        $document = InvoiceDocument::where('invoice_id', $invoice->id)->get();
        $max = $arr->max('rec_total');
        $tot = $arr->sum('rec_total');
        $pdf = PDF::loadView('invoice.printgexpdf', compact('invoice','receivable','max','tot','document'))->setPaper('a4', 'portrait');
        return $pdf->stream('invoice-receivable.pdf');
    }

    public function revisionreceivable(Request $request) {
        $revisi = Invoice::where('status', 5)->where('type','receivable')->get();

        return view('invoice.revision', compact('revisi'));
    }

    // reimbursement
    public function uncreatedreimbursement(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', 'completed')->get();
        
        return view('jobsheet.marketing.uncreated.indexrmb', compact('jobsheets'));
    }

    public function createdreimbursement(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $id)->get();
        $reimbursement = Reimbursement::where('jobsheet_id', $id)->where('rmb_invoice_id',null)->get();

        return view('invoice._createdreimbursement', compact('jobsheet','references','reimbursement'));
    }

    public function storereimbursement(Request $request, Datatables $datatables, $id)
    {
        $charges = $request->input('charges');
        $total = $request->input('total');

        if (!empty($charges) || !empty($total)) {
            $max = max($total);
            $tot = array_sum($total);

            $jobsheet = JobSheet::find($id);
            $references = Reference::where('jobsheet_id', $id)->get();

            return view('invoice._createdinvoicermb', compact('jobsheet','references','charges','max','tot'));
        } else {
            return redirect()->route('invoice.reimbursement');
        }
    }

    public function storeinvoicereimbursement(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $jobsheet->step_role = 3;
        $jobsheet->save();

        $references = Reference::where('jobsheet_id', $id)->get();

        $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('date'));
        $generate_code = 'RMB/'.$convert_date->format('m').'/'.$convert_date->format('d');

        $saveinvoice = new Invoice;
        // $saveinvoice->code = $generate_code;
        $saveinvoice->customer_id = $request->input('customer');
        $saveinvoice->jobsheet_id = $jobsheet->id;
        $saveinvoice->bank_id = $request->input('bank_id');
        $saveinvoice->tanggal = $convert_date->toDateString();
        $saveinvoice->status = 0;
        $saveinvoice->approval = 0;
        $saveinvoice->type = 'reimbursement';
        $saveinvoice->efaktur = '-';
        $saveinvoice->timestamps = true;
        $saveinvoice->save();

        if ($saveinvoice->id < 10) {
            $saveinvoice->code         = $generate_code.'/000'.$saveinvoice->id;
        } elseif ($saveinvoice->id >= 10 && $saveinvoice->id < 100) {
            $saveinvoice->code         = $generate_code.'/00'.$saveinvoice->id;
        } elseif ($saveinvoice->id >= 100 && $saveinvoice->id < 1000) {
            $saveinvoice->code         = $generate_code.'/0'.$saveinvoice->id;
        } else {
            $saveinvoice->code         = $generate_code.'/'.$saveinvoice->id;
        }
        
        $saveinvoice->save();

        $ref = $request->input('ref');
        for ($i=0; $i < count($ref); $i++) { 

            $parts=explode("-",$ref[$i]);

            $saveref = new InvoiceDocument;
            $saveref->invoice_id = $saveinvoice->id;
            $saveref->name = $parts[0];
            $saveref->no_ref = $parts[1];
            $saveref->timestamps = true;
            $saveref->save();
        }

        $rmb_id = $request->input('rmb_id');
        for ($j=0; $j < count($rmb_id); $j++) { 
            $receivable = Reimbursement::find($rmb_id[$j]);
            $receivable->rmb_invoice_id = $saveinvoice->id;
            $receivable->save();
        }

        return redirect()->route('invoice.reimbursement');
    }

    public function invoicereimbursement(Request $request, Datatables $datatables)
    {
        $invoicerec = invoice::where('type', 'reimbursement')->get();
        
        return view('invoice._indexrmb', compact('jobsheets','invoicerec'));
    }

    public function showreimbursement(Request $request, Datatables $datatables, $id)
    {
        $invoice = Invoice::find($id);
        $jobsheet = JobSheet::find($invoice->jobsheet_id);
        $charges = Reimbursement::where('rmb_invoice_id', $invoice->id)->get();
        $references = Reference::where('jobsheet_id', $id)->get();

        $receivables = Reimbursement::where('rmb_invoice_id', $invoice->id)->first();

        $max = $charges->max('rmb_total');
        $tot = $charges->sum('rmb_total');

        return view('invoice._showrmb', compact('invoice','jobsheet','charges','references','receivables','max','tot'));
    }

    public function declinereimbursement(Request $request, Datatables $datatables, $id)
    {
        $jobsheet_id    = JobSheet::find($id);
        // $jobsheet_id    = $request->jobsheet_id;
        $sender_id      = $request->sender_id;
        $note           = $request->note;
        $receiver       = $request->receiver;
        $role           = User::find($receiver);

        Revision::create([
            'jobsheet_id'   => $jobsheet_id,
            'sender'        => $sender_id,
            'receiver'      => $receiver,
            'note'          => $note,
            'role'          => $role->role
        ]);

        $jobsheet = JobSheet::find($jobsheet_id);
        $jobsheet->status = 'revisi';
        $jobsheet->save();

        return redirect()->route('invoice.reimbursement.uncreatedreimbursement');
    }

    public function pdfrmb(Request $request, $id) {
        $invoice = Invoice::find($id);
        $arr = Reimbursement::where('rmb_invoice_id', $invoice->id)->get();
        $receivable = $arr->toArray();
        $document = InvoiceDocument::where('invoice_id', $invoice->id)->get();
        $max = $arr->max('rmb_total');
        $tot = $arr->sum('rmb_total');
        $pdf = PDF::loadView('invoice.printgexrmbpdf', compact('invoice','receivable','max','tot','document'))->setPaper('a4', 'portrait');
        return $pdf->stream('invoice-reimbursement.pdf');
    }

    public function revisionreimbursement(Request $request) {
        $revisi = Invoice::where('status', 5)->where('type','reimbursement')->get();

        return view('invoice.revision', compact('revisi'));
    }


    // report
    public function reportrec(Request $request, Datatables $datatables)
    {
        $invoicerec = invoice::where('type', 'receivable')->get();
        
        return view('invoice._report', compact('jobsheets','invoicerec'));
    }

    public function reportrmb(Request $request, Datatables $datatables)
    {
        $invoicerec = invoice::where('type', 'reimbursement')->get();
        
        return view('invoice._report', compact('jobsheets','invoicerec'));
    }

    public function edit(Request $request, Datatables $datatables, $id, $type)
    {
        if($type == 'receivable'){
            $invoice = Invoice::find($id);
            $jobsheet = JobSheet::find($invoice->jobsheet_id);
            $charges = Receivable::where('jobsheet_id', $jobsheet->id)->get();
            $references = Reference::where('jobsheet_id', $id)->get();

            $receivables = Receivable::where('rec_invoice_id', $invoice->id)->first();

            $max = $charges->max('rec_total');
            $tot = $charges->sum('rec_total');
        }elseif ($type == 'reimbursement') {
            $invoice = Invoice::find($id);
            $jobsheet = JobSheet::find($invoice->jobsheet_id);
            $charges = Reimbursement::where('jobsheet_id', $jobsheet->id)->get();
            $references = Reference::where('jobsheet_id', $id)->get();

            $receivables = Reimbursement::where('rmb_invoice_id', $invoice->id)->first();

            $max = $charges->max('rmb_total');
            $tot = $charges->sum('rmb_total');
        }

        return view('invoice._edit', compact('invoice','jobsheet','charges','references','receivables','max','tot','type'));
    }

    public function nextedit(Request $request, Datatables $datatables, $id, $type, $invoice_id)
    {
        $invoice = Invoice::find($id);
        $docu = InvoiceDocument::where('invoice_id',$invoice->id)->get();

        $charges = $request->input('charges');
        $total = $request->input('total');
        $max = max($total);
        $tot = array_sum($total);

        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $id)->get();

        return view('invoice._editform', compact('jobsheet','references','charges','max','tot','type','invoice','docu'));
    }

    public function storeedit(Request $request, Datatables $datatables, $id, $type, $invoice_id)
    {
        if($type == 'receivable') {
            $jobsheet = JobSheet::find($id);
            $jobsheet->step_role = 3;
            $jobsheet->save();
            
            $references = Reference::where('jobsheet_id', $id)->get();
            $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('date'));
            // $generate_code = 'AR/'.$convert_date->format('m').'/'.$convert_date->format('d').'/'.'xxx';

            $saveinvoice = Invoice::find($invoice_id);
            // $saveinvoice->code = $generate_code;
            $saveinvoice->customer_id = $request->input('customer');
            $saveinvoice->jobsheet_id = $jobsheet->id;
            $saveinvoice->bank_id = $request->input('bank_id');
            $saveinvoice->tanggal = $convert_date->toDateString();
            $saveinvoice->status = 0;
            $saveinvoice->approval = 0;
            $saveinvoice->type = 'receivable';
            $saveinvoice->efaktur = '-';
            $saveinvoice->timestamps = true;
            $saveinvoice->save();

            $ref = $request->input('ref');
            for ($i=0; $i < count($ref); $i++) { 

                $parts=explode("-",$ref[$i]);

                $saveref = InvoiceDocument::where('invoice_id', $saveinvoice->id)->first();
                $saveref->invoice_id = $saveinvoice->id;
                $saveref->name = $parts[0];
                $saveref->no_ref = $parts[1];
                $saveref->timestamps = true;
                $saveref->save();
            }

            $rec_id = $request->input('rec_id');
            for ($j=0; $j < count($rec_id); $j++) { 
                $receivable = Receivable::find($rec_id[$j]);
                $receivable->rec_invoice_id = $saveinvoice->id;
                $receivable->save();
            }

            return redirect()->route('invoice.receivable');
        }elseif ($type == 'reimbursement') {
            $jobsheet = JobSheet::find($id);
        $jobsheet->step_role = 3;
        $jobsheet->save();

        $references = Reference::where('jobsheet_id', $id)->get();

        $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('date'));
        $generate_code = 'RMB/'.$convert_date->format('m').'/'.$convert_date->format('d').'/'.'xxx';

        $saveinvoice = new Invoice;
        $saveinvoice->code = $generate_code;
        $saveinvoice->customer_id = $request->input('customer');
        $saveinvoice->jobsheet_id = $jobsheet->id;
        $saveinvoice->bank_id = $request->input('bank_id');
        $saveinvoice->tanggal = $convert_date->toDateString();
        $saveinvoice->status = 0;
        $saveinvoice->approval = 0;
        $saveinvoice->type = 'reimbursement';
        $saveinvoice->efaktur = '-';
        $saveinvoice->timestamps = true;
        $saveinvoice->save();

        $ref = $request->input('ref');
        for ($i=0; $i < count($ref); $i++) { 

            $parts=explode("-",$ref[$i]);

            $saveref = new InvoiceDocument;
            $saveref->invoice_id = $saveinvoice->id;
            $saveref->name = $parts[0];
            $saveref->no_ref = $parts[1];
            $saveref->timestamps = true;
            $saveref->save();
        }

        $rmb_id = $request->input('rmb_id');
        for ($j=0; $j < count($rmb_id); $j++) { 
            $receivable = Reimbursement::find($rmb_id[$j]);
            $receivable->rmb_invoice_id = $saveinvoice->id;
            $receivable->save();
        }

        return redirect()->route('invoice.reimbursement');
        }
    }

    public function cancel(Request $request, $id, $type) {
        $reason = $request->input('reason');

        $invoice = invoice::find($id);
        $invoice->status = 2;
        $invoice->reason = $reason;
        $invoice->save();

        if($type == 'receivable') {
            return redirect()->route('invoice.receivable.index');
        }elseif($type == 'reimbursement') {
            return redirect()->route('invoice.reimbursement');
        }
    } 

}
