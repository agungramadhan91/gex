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
use App\Invoice;

class _PajakController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('role:pajak,admin');
    }   

    public function jobsheet(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::all();
        return view('jobsheet.index', compact('jobsheets'));
    }

    public function show(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();

        return view('jobsheet.operation.show', compact('jobsheet','references','payables'));
    }

    public function invoice()
    {
        $invoicerec = invoice::all();
        
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

    public function faktur(Request $request, $id) {
        $faktur = $request->input('faktur');

        $efakture = invoice::find($id);
        $efakture->efaktur = $faktur;
        $efakture->save();

        return redirect()->route('pajak.invoice');
    }

    public function report(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::all();
        // return json_decode($jobsheets);
        return view('report.pajak', compact('jobsheets'));
    }
}
