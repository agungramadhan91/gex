<?php

namespace App\Http\Controllers;

use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\MasterCustomer;
use App\MasterCurrency;
use App\MasterDocument;
use App\MasterTerm;
use App\MasterUnit;

use App\Marketing;
use App\Reimbursement;
use App\Receivable;
use App\Reference;
use App\JobSheet;
use App\Revision;
use App\Payable;
use App\User;
use App\RC;


class _MarketingController extends Controller
{
    public function __construct()
    {
        // $this->middleware('role:admin,admin2');
        $this->middleware('role:marketing');
    }   

    //==============================================================================================
    //              JOBSHEET
    //==============================================================================================
    public function jobsheet_index(Request $request, Datatables $datatables)
    {
        if (Auth::user()->role == 'admin' || Auth::user()->role == 'admin2') {
            $jobsheets = JobSheet::where('status', 'completed')->get();
        } else {
            $jobsheets = JobSheet::where('marketing_id', Auth::user()->id)->where('status', 'completed')->get();
        }
        
        return view('jobsheet.index', compact('jobsheets'));
    }

    public function jobsheet_uncreated(Request $request, Datatables $datatables)
    {
        if (Auth::user()->role == 'admin') {
            $jobsheets = JobSheet::all();
        } else {
            $jobsheets = JobSheet::where('marketing_id', Auth::user()->id)->where('status', 'uncompleted')->get();
        }
        
        return view('jobsheet.uncreated', compact('jobsheets'));
    }

    public function jobsheet_create(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();

        return view('jobsheet.marketing.create', compact('jobsheet','references'));
    }

    public function jobsheet_store(Request $request, $id)
    {
        // $this->validate($request,[
        //     'remarks'       => 'required',
        //     'instruction'   => 'required',

        //     'rec_term_id'       => 'exists:master_terms,id',
        //     'rec_customer_id'   => 'exists:master_customers,id',
        //     'rec_document_id'   => 'exists:master_documents,id',
        //     'rec_charge_type'   => 'integer',
        //     'rec_tax'           => 'integer',
        //     'rec_quantity'      => 'integer',
        //     'rec_unit_id'       => 'exists:master_units,id',
        //     'rec_currency'      => 'integer',

        //     'rmb_term_id'       => 'exists:master_terms,id',
        //     'rmb_customer_id'   => 'exists:master_customers,id',
        //     'rmb_document_id'   => 'exists:master_documents,id',
        //     'rmb_charge_type'   => 'integer',
        //     'rmb_tax'           => 'integer',
        //     'rmb_quantity'      => 'integer',
        //     'rmb_unit_id'       => 'exists:master_units,id',
        //     'rmb_currency'      => 'integer',
        // ]);

        $jobsheet = JobSheet::find($id);

        if ($jobsheet) {
            
            // =====INPUT RECEIVABLE============================

            // $marketing              = new Marketing();
            
            $rec_term_id        = $request->rec_term_id;
            $rec_customer_id    = $request->rec_customer_id;

            $rec_document_id    = $request->rec_document_id;
            $rec_charge_type    = $request->rec_charge_type;
            $rec_tax            = $request->rec_tax;
            $rec_quantity       = $request->rec_quantity;
            $rec_unit_id        = $request->rec_unit_id;
            $rec_currency       = $request->rec_currency;

            for ($i=0; $i < count($rec_term_id); $i++) 
            {
                if (!empty($rec_term_id[$i])) 
                {
                    $rec_marketing = Marketing::create([
                        'jobsheet_id'   => $jobsheet->id,
                        'term_id'       => $rec_term_id[$i],
                        'customer_id'   => $rec_customer_id[$i]
                    ]);
                    
                    for ($j=0; $j < count($rec_document_id[$i]); $j++) 
                    { 
                        $parts=explode(",",$request->rec_price[$i][$j]);
                        $parts=array_filter($parts);
                        $rec_price = (implode("",$parts));
                        $rec_qty = $rec_quantity[$i][$j];
                        $tax = $rec_tax[$i][$j];

                        if ($rec_tax[$i][$j] != '1') {
                            $rec_total = ((string)$rec_price * $rec_qty) / 1.01;
                            $ppn = $rec_total * (1 / 100);
                        } else {
                            $rec_total = ((string)$rec_price * $rec_qty);
                            $ppn = $rec_total * (1 / 100);
                        }

                        $receive = new Receivable();
                        $receive->jobsheet_id       = $jobsheet->id;
                        $receive->rec_marketing_id  = $rec_marketing->id;
                        $receive->rec_document_id   = $rec_document_id[$i][$j];
                        $receive->rec_invoice_id    = null;
                        $receive->rec_unit_id       = $rec_unit_id[$i][$j];
                        $receive->rec_currency      = $rec_currency[$i][$j];
                        
                        $receive->rec_price         = $rec_price;
                        $receive->rec_quantity      = $rec_qty;
                        $receive->rec_total         = $rec_total;
                        
                        $receive->rec_tax           = $tax;
                        $receive->rec_tax_amount    = $ppn;
                        $receive->rec_charge_type   = $rec_charge_type[$i][$j];
                        $receive->save();
                    }
                } 
            }

            // =====INPUT REIMBURSEMENT============================

            $rmb_term_id        = $request->rmb_term_id;
            $rmb_customer_id    = $request->rmb_customer_id;

            $rmb_document_id    = $request->rmb_document_id;
            $rmb_quantity       = $request->rmb_quantity;
            $rmb_unit_id        = $request->rmb_unit_id;
            $rmb_currency       = $request->rmb_currency;

            for ($k=0; $k < count($rmb_term_id); $k++) 
            {
                if (!empty($rmb_term_id[$k])) 
                {
                    $rmb_marketing = Marketing::create([
                        'jobsheet_id'   => $jobsheet->id,
                        'term_id'       => $rmb_term_id[$k],
                        'customer_id'   => $rmb_customer_id[$k]
                    ]);

                    for ($l=0; $l < count($rmb_document_id[$k]); $l++) 
                    {   
                        $rmbparts=explode(",",$request->rmb_price[$k][$l]);
                        $rmbparts=array_filter($rmbparts);
                        $rmb_price = (implode("",$rmbparts));
                        $rmb_qty = $rmb_quantity[$k][$l];
                        $rmb_total = (string)$rmb_price * $rmb_qty;

                        $reimb = Reimbursement::create([
                            'jobsheet_id'       => $jobsheet->id,
                            'rmb_marketing_id'  => $rmb_marketing->id,

                            'rmb_document_id'   => $rmb_document_id[$k][$l],
                            'rmb_quantity'      => $rmb_qty,
                            'rmb_unit_id'       => $rmb_unit_id[$k][$l],
                            'rmb_invoice_id'    => null,
                            'rmb_currency'      => $rmb_currency[$k][$l],
                            'rmb_price'         => $rmb_price,
                            'rmb_total'         => $rmb_total,
                        ]);  
                    }
                } 
            }

            // =====INPUT RC============================
            $rc_document_id = $request->rc_document_id;
            $rc_vendor_id   = $request->rc_vendor_id;
            $rc_quantity    = $request->rc_quantity;
            $rc_unit_id     = $request->rc_unit_id;
            $rc_currency    = $request->rc_currency;

            for ($a=0; $a < count($rc_document_id); $a++) 
            { 
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
                        'rc_type'          => 'marketing',
                    ]);
                }
            }
        }

        $jobsheet->remarks      = $request->remarks;
        $jobsheet->instruction  = $request->instruction;
        $jobsheet->status       = 'completed';
        $jobsheet->save();

        return redirect()->route('marketing.jobsheet.index');
    }

    public function jobsheet_show(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $receivables = Receivable::where('jobsheet_id', $jobsheet->id)->get();
        $reimbursements = Reimbursement::where('jobsheet_id', $jobsheet->id)->get();
        $documents = MasterDocument::all();
        $rcs = RC::where('jobsheet_id', $jobsheet->id)->where('rc_type','marketing')->get();

        $rec_total_tax_idr = $receivables->where('rec_currency',1)->sum('rec_tax_amount');
        $rec_total_tax_usd = $receivables->where('rec_currency',2)->sum('rec_tax_amount');
        $rec_total_price = $receivables->sum('rec_price');
        $rec_total_price_idr = $receivables->where('rec_currency',1);
        $rec_total_price_usd = $receivables->where('rec_currency',2);

        $rmb_total_price = $reimbursements->sum('rmb_price');
        $rmb_total_price_idr = $reimbursements->where('rmb_currency',1);
        $rmb_total_price_usd = $reimbursements->where('rmb_currency',2);

        $rc_total_price  = $rcs->sum('rc_price');
        $rc_total_price_idr = $rcs->where('rc_currency',1);
        $rc_total_price_usd = $rcs->where('rc_currency',2);

        if (count($receivables) > 0) {
            return view('jobsheet.marketing.show',compact('jobsheet','references','receivables','reimbursements','rcs','rec_total_price','rec_total_tax_idr','rec_total_tax_usd','rmb_total_price','rc_total_price','rec_total_price_idr','rec_total_price_usd','rmb_total_price_idr','rmb_total_price_usd','rc_total_price_idr','rc_total_price_usd'));
        }

        return redirect()->route('marketing.jobsheet.index');
    }

    public function jobsheet_edit(Request $request, Datatables $datatables, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();

        $receivables = Receivable::where('jobsheet_id', $id)->get();
        $reimbursements = Reimbursement::where('jobsheet_id', $id)->get();
        $rcs = RC::where('jobsheet_id', $id)->where('rc_type','marketing')->get();
        $revision   = Revision::where('jobsheet_id', $id)->first();

        return view('jobsheet.marketing.edit',compact('jobsheet','references','receivables','reimbursements','rcs','revision'));
    }

    public function jobsheet_update(Request $request, Datatables $datatables, $id)
    {
        // $this->validate($request,[
        //     'remarks'       => 'required',
        //     'instruction'   => 'required',

        //     'rec_term_id'       => 'exists:master_terms,id',
        //     'rec_customer_id'   => 'exists:master_customers,id',
        //     'rec_document_id'   => 'exists:master_documents,id',
        //     'rec_charge_type'   => 'integer',
        //     'rec_tax'           => 'integer',
        //     'rec_quantity'      => 'integer',
        //     'rec_unit_id'       => 'exists:master_units,id',
        //     'rec_currency'      => 'integer',

        //     'rmb_term_id'       => 'exists:master_terms,id',
        //     'rmb_customer_id'   => 'exists:master_customers,id',
        //     'rmb_document_id'   => 'exists:master_documents,id',
        //     'rmb_charge_type'   => 'integer',
        //     'rmb_quantity'      => 'integer',
        //     'rmb_unit_id'       => 'exists:master_units,id',
        //     'rmb_currency'      => 'integer',
        // ]);

        $jobsheet = JobSheet::find($id);

        if ($jobsheet) {

            // =====UPDATE RECEIVABLE============================
            $rec_marketing_id   = $request->rec_marketing_id;
            $rec_term_id        = $request->rec_term_id;
            $rec_customer_id    = $request->rec_customer_id;

            $rec_id             = $request->rec_id;
            $rec_document_id    = $request->rec_document_id;
            $rec_charge_type    = $request->rec_charge_type;
            $rec_tax            = $request->rec_tax;
            // $rec_price          = $request->rec_price;
            $rec_quantity       = $request->rec_quantity;
            $rec_unit_id        = $request->rec_unit_id;
            $rec_currency       = $request->rec_currency;

            for ($i=0; $i < count($rec_term_id); $i++) 
            {
                if (!empty($rec_term_id[$i])) 
                {
                    if ($rec_marketing_id[$i] != 0) {
                        $rec_marketing = Marketing::find($rec_marketing_id[$i]);
                        $rec_marketing->jobsheet_id   = $jobsheet->id;
                        $rec_marketing->term_id       = $rec_term_id[$i];
                        $rec_marketing->customer_id   = $rec_customer_id[$i];
                        $rec_marketing->save();
                    }else{
                        $rec_marketing = new Marketing();
                        $rec_marketing->jobsheet_id   = $jobsheet->id;
                        $rec_marketing->term_id       = $rec_term_id[$i];
                        $rec_marketing->customer_id   = $rec_customer_id[$i];
                        $rec_marketing->save();
                    }
                    
                    for ($j=0; $j < count($rec_document_id[$i]); $j++) 
                    { 
                        $parts=explode(",",$request->rec_price[$i][$j]);
                        $parts=array_filter($parts);
                        $rec_price = (implode("",$parts));
                        $rec_qty = $rec_quantity[$i][$j];

                        if ($rec_id[$i][$j] != 0) {
                            $receive = Receivable::find($rec_id[$i][$j]);
                            $receive->jobsheet_id       = $jobsheet->id;
                            $receive->rec_marketing_id  = $rec_marketing->id;

                            if (!empty($rec_document_id[$i][$j])) 
                            {
                                if ($rec_tax[$i][$j] != '1') {
                                    $rec_total = ((string)$rec_price * $rec_qty) / 1.01;
                                    $ppn = $rec_total * (1 / 100);
                                } else {
                                    $rec_total = ((string)$rec_price * $rec_qty);
                                    $ppn = $rec_total * (1 / 100);
                                }

                                $receive->rec_document_id   = $rec_document_id[$i][$j];
                                $receive->rec_unit_id       = $rec_unit_id[$i][$j];
                                $receive->rec_invoice_id    = null;
                                $receive->rec_currency      = $rec_currency[$i][$j];

                                $receive->rec_price         = $rec_price;
                                $receive->rec_quantity      = $rec_qty;
                                $receive->rec_total         = $rec_total;

                                $receive->rec_tax           = $rec_tax[$i][$j];
                                $receive->rec_tax_amount    = $ppn;
                                $receive->rec_charge_type   = $rec_charge_type[$i][$j];
                                $receive->save();
                            } else {
                                $receive->delete();
                            }
                            
                        } else {
                            if ($rec_tax[$i][$j] != '1') {
                                $rec_total = ((string)$rec_price * $rec_qty) / 1.01;
                                $ppn = $rec_total * (1 / 100);
                            } else {
                                $rec_total = ((string)$rec_price * $rec_qty);
                                $ppn = $rec_total * (1 / 100);
                            }
                            
                            $receive = Receivable::create([
                                'jobsheet_id'       => $jobsheet->id,
                                'rec_marketing_id'  => $rec_marketing->id,

                                'rec_document_id'   => $rec_document_id[$i][$j],
                                'rec_unit_id'       => $rec_unit_id[$i][$j],
                                'rec_invoice_id'    => null,
                                'rec_currency'      => $rec_currency[$i][$j],

                                'rec_price'         => $rec_price,
                                'rec_quantity'      => $rec_qty,
                                'rec_total'         => $rec_total,

                                'rec_tax'           => $rec_tax[$i][$j],
                                'rec_tax_amount'    => $ppn,
                                'rec_charge_type'   => $rec_charge_type[$i][$j],
                            ]);
                        }
                        
                    }
                } 
            }

            // =====UPDATE REIMBURSEMENT============================
            $rmb_marketing_id   = $request->rmb_marketing_id;
            $rmb_term_id        = $request->rmb_term_id;
            $rmb_customer_id    = $request->rmb_customer_id;

            $rmb_id             = $request->rmb_id;
            $rmb_document_id    = $request->rmb_document_id;
            $rmb_price          = $request->rmb_price;
            $rmb_quantity       = $request->rmb_quantity;
            $rmb_unit_id        = $request->rmb_unit_id;
            $rmb_currency       = $request->rmb_currency;

            for ($k=0; $k < count($rmb_term_id); $k++) 
            {
                if (!empty($rmb_term_id[$k])) 
                {
                    if ($rmb_marketing_id[$k] != 0) {
                        $rmb_marketing = Marketing::find($rmb_marketing_id[$k]);
                        $rmb_marketing->jobsheet_id   = $jobsheet->id;
                        $rmb_marketing->term_id       = $rmb_term_id[$k];
                        $rmb_marketing->customer_id   = $rmb_customer_id[$k];
                        $rmb_marketing->save();
                    }else{
                        $rmb_marketing = Marketing::create([
                            'jobsheet_id'   => $jobsheet->id,
                            'term_id'       => $rmb_term_id[$k],
                            'customer_id'   => $rmb_customer_id[$k]
                        ]);
                    }

                    for ($l=0; $l < count($rmb_document_id[$k]); $l++) 
                    {   
                        $rmbparts=explode(",",$rmb_price[$k][$l]);
                        $rmbparts=array_filter($rmbparts);
                        $price = (implode("",$rmbparts));
                        $rmb_qty = $rmb_quantity[$k][$l];
                        $rmb_total = (string)$price * $rmb_qty;

                        if ($rmb_id[$k][$l] != 0) {
                            $reimb = Reimbursement::find($rmb_id[$k][$l]);  
                            $reimb->jobsheet_id       = $jobsheet->id;
                            $reimb->rmb_marketing_id  = $rmb_marketing->id;
                            
                            if (!empty($rmb_document_id[$k][$l])) {
                                $reimb->rmb_document_id   = $rmb_document_id[$k][$l];
                                $reimb->rmb_unit_id       = $rmb_unit_id[$k][$l];
                                $reimb->rmb_quantity      = $rmb_qty;
                                $reimb->rmb_invoice_id    = null;
                                $reimb->rmb_currency      = $rmb_currency[$k][$l];
                                $reimb->rmb_price         = $price;
                                $reimb->rmb_total         = $rmb_total;
                                $reimb->save();
                            } else {
                                $reimb->delete();
                            }
                        } else {
                            $reimb = Reimbursement::create([
                                'jobsheet_id'       => $jobsheet->id,
                                'rmb_marketing_id'  => $rmb_marketing->id,

                                'rmb_document_id'   => $rmb_document_id[$k][$l],
                                'rmb_quantity'      => $rmb_qty,
                                'rmb_unit_id'       => $rmb_unit_id[$k][$l],
                                'rmb_invoice_id'    => null,
                                'rmb_currency'      => $rmb_currency[$k][$l],
                                'rmb_price'         => $price,
                                'rmb_total'         => $rmb_total,
                            ]);
                        }
                    }
                } 
            }

            // =====UPDATE RC============================
            $rc_id          = $request->rc_id;
            $rc_document_id = $request->rc_document_id;
            $rc_vendor_id   = $request->rc_vendor_id;
            $rc_quantity    = $request->rc_quantity;
            $rc_unit_id     = $request->rc_unit_id;
            $rc_price       = $request->rc_price;
            $rc_currency    = $request->rc_currency;

            // DB::table('rc')->where('jobsheet_id', '=', $jobsheet->id)->delete();

            for ($a=0; $a < count($rc_document_id); $a++) 
            { 
                if (!empty($rc_document_id[$a])) 
                {
                    $rcparts=explode(",",$rc_price[$a]);
                    $rcparts=array_filter($rcparts);
                    $price = (implode("",$rcparts));

                    if ($rc_id[$a] != 0) {
                        $rc = RC::find($rc_id[$a]);
                        $rc->jobsheet_id      = $jobsheet->id;
                        $rc->rc_document_id   = $rc_document_id[$a];
                        $rc->rc_vendor_id     = $rc_vendor_id[$a];
                        $rc->rc_unit_id       = $rc_unit_id[$a];
                        $rc->rc_quantity      = $rc_quantity[$a];
                        $rc->rc_currency      = $rc_currency[$a];
                        $rc->rc_price         = $price;
                        $rc->rc_total         = $price * $rc_quantity[$a];
                        $rc->rc_type          = 'marketing';
                        $rc->save();
                    }else{
                        $rc = new RC();
                        $rc->jobsheet_id      = $jobsheet->id;
                        $rc->rc_document_id   = $rc_document_id[$a];
                        $rc->rc_vendor_id     = $rc_vendor_id[$a];
                        $rc->rc_unit_id       = $rc_unit_id[$a];
                        $rc->rc_quantity      = $rc_quantity[$a];
                        $rc->rc_currency      = $rc_currency[$a];
                        $rc->rc_price         = $price;
                        $rc->rc_total         = $price * $rc_quantity[$a];
                        $rc->rc_type          = 'marketing';
                        $rc->save();
                    }
                }
            }
        }

        $jobsheet->remarks      = $request->remarks;
        $jobsheet->instruction  = $request->instruction;
        
        $cek_rec = Receivable::where('jobsheet_id', $jobsheet->id)->count();
        $cek_rmb = Reimbursement::where('jobsheet_id', $jobsheet->id)->count();

        if ($jobsheet->status == 'revisi-invoice' || $jobsheet->status == 'revisi-pricing') {
            if ($cek_rec < 1) {
                $jobsheet->status       = 'uncompleted';
            }else{
                $jobsheet->status       = 'completed';
            }
            // $jobsheet->status       = 'completed';
        }elseif ($jobsheet->status == 'revisi-marketing') {
            $jobsheet->status       = 'uncompleted';
        }

        if ($cek_rmb > 0) {
            $jobsheet->step_role = '';
        }
        
        $jobsheet->save();

        $cekrevisi = Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'marketing')->count();
        if($cekrevisi>0) {
            Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'marketing')->delete();
        }

        return redirect()->route('marketing.jobsheet.index');
    }

    public function jobsheet_decline(Request $request, Datatables $datatables)
    {
        $this->validate($request, [
            'note' => 'required_with:receiver'
        ]);
        
        $jobsheet_id    = $request->jobsheet_id;
        $sender_id      = $request->sender_id;
        $note           = $request->note;
        $receiver       = JobSheet::find($jobsheet_id);

        Revision::create([
            'jobsheet_id'   => $jobsheet_id,
            'sender'        => $sender_id,
            'receiver'      => $receiver->operation_id,
            'note'          => $note,
            'role'          => 'operation'
        ]);

        $jobsheet = JobSheet::find($jobsheet_id);
        $sender = User::find($sender_id);
        $jobsheet->status = 'revisi-'.$sender->role;
        $jobsheet->save();

        return redirect()->route('marketing.jobsheet.index');
    }

    public function jobsheet_revision(Request $request)
    {
        $revisions = Revision::where('role', 'marketing')->get();
        return view('jobsheet.revision', compact('revisions'));
    }

    public function receivable_destroy($id)
    {
        Receivable::find($id)->destroy();
        return redirect()->route('jobsheet.marketing.edit', $id);
    }

    //==============================================================================================
    //              REQUEST
    //==============================================================================================


    //==============================================================================================
    //              REPORT
    //==============================================================================================

    public function reportjob(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', 'completed')->get();
        return view('jobsheet.operation.report', compact('jobsheets'));
    }

    public function reportcharge(Request $request, Datatables $datatables)
    {
        $jobsheets = Receivable::all();
        return view('jobsheet.marketing.reportcharge', compact('jobsheets'));
    }
}
