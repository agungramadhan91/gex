<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Auth;
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
use App\RequestModel;


class _OperationController extends Controller
{
    public function __construct()
	{
		$this->middleware('role:operation, admin');
	}

    public function jobsheet_index(Request $request, Datatables $datatables)
    {
        if (Auth::user()->role == 'admin') {
            $jobsheets = JobSheet::all();
        } else {
            $jobsheets = JobSheet::whereNotIn('status',['revisi-marketing','revisi-pricing','revisi-payable'])->where('operation_id', Auth::user()->id)->get();
        }

        return view('jobsheet.index', compact('jobsheets'));
    }

    public function jobsheet_uncreated(Request $request)
    {
        return view('jobsheet.operation.uncreated');
    }

    public function jobsheet_store(Request $request)
    {
        try {
            $this->validate($request,[
                'marketing_id'  => 'required|integer',
                'customer_id'   => 'required|integer',
                'poo_id'        => 'required|integer',
                'pod_id'        => 'required|integer',
                'description'   => 'required',
                'etd'           => 'required|date|before:eta',
                'eta'           => 'required|date',
                'partymeas'     => 'required',
                'party_unit_id' => 'required',
                'freight_type'  => 'required',

                'ref_no'            => 'required_with:ref_document_id',
                'ref_document_id'   => 'required_with:ref_no'
            ]);

            // =======================Generate & Request=======================
            $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('tanggal'));
            $convert_etd = Carbon::createFromFormat('d-m-Y', $request->get('etd'));
            $convert_eta = Carbon::createFromFormat('d-m-Y', $request->get('eta'));
            
            $port = MasterPort::find($request->get('poo_id'));
            $generate_code = $convert_date->format('m').'/'.$convert_date->format('Y').'/'.$port->nick_name;

            // =======================Simpan Jobsheet baru=======================
            $jobsheet = new JobSheet();
            $jobsheet->operation_id = Auth::user()->id;
            $jobsheet->customer_id  = $request->get('customer_id');
            $jobsheet->poo_id       = $request->get('poo_id');
            $jobsheet->pod_id       = $request->get('pod_id');
            $jobsheet->freight_type = $request->get('freight_type');
            $jobsheet->vessel       = $request->get('vessel');
            $jobsheet->partymeas    = $request->get('partymeas');
            $jobsheet->party_unit_id= $request->get('party_unit_id');
            $jobsheet->marketing_id = $request->get('marketing_id');
            $jobsheet->description  = $request->get('description');
            $jobsheet->remarks      = $request->get('remarks');
            $jobsheet->instruction  = $request->get('instruction');
            $jobsheet->date         = $convert_date->toDateString();
            $jobsheet->etd          = $convert_etd->toDateString();
            $jobsheet->eta          = $convert_eta->toDateString();
            $jobsheet->status       = 'uncompleted';
            $jobsheet->save();

            if ($jobsheet->id < 10) {
                $jobsheet->code         = '000'.$jobsheet->id.'/'.$generate_code;
            } elseif ($jobsheet->id >= 10 && $jobsheet->id < 100) {
                $jobsheet->code         = '00'.$jobsheet->id.'/'.$generate_code;
            } elseif ($jobsheet->id >= 100 && $jobsheet->id < 1000) {
                $jobsheet->code         = '0'.$jobsheet->id.'/'.$generate_code;
            } else {
                $jobsheet->code         = $jobsheet->id.'/'.$generate_code;
            } 
            $jobsheet->save();

            if ($jobsheet) {
                
                // =======================Simpan Reference baru=======================
                $ref_no = $request->get('ref_no');
                $doc_id = $request->get('ref_document_id');

                for ($i=0; $i < count($ref_no); $i++) 
                { 
                    if(!empty($ref_no[$i]) && !empty($doc_id[$i]))
                    {
                        Reference::create([
                            'jobsheet_id'=>$jobsheet->id,
                            'document_id'=>$doc_id[$i],
                            'ref_no'     =>$ref_no[$i]
                        ]);
                    }
                }

                // =======================Simpan Payable baru=======================
                $charge = $request->get('document_id');
                $vendor = $request->get('vendor_id');
                $unit   = $request->get('unit_id');
                $quanty = $request->get('quantity');

                for ($j=0; $j < count($charge); $j++) 
                { 
                    if(!empty($charge[$j]))
                    {
                        $payable = new Payable();
                        $payable->user_id       = Auth::user()->id;
                        $payable->jobsheet_id   = $jobsheet->id;
                        $payable->document_id   = $charge[$j];
                        $payable->vendor_id     = $vendor[$j];
                        $payable->unit_id       = $unit[$j];
                        $payable->quantity      = $quanty[$j];
                        $payable->save();
                    }
                }
            }

            return redirect()->route('operation.jobsheet.index');

        } catch (Exception $e) {
            return "ada yg salah";
        }
    }

    public function jobsheet_show(Request $request, $id)
    {
        $jobsheet = JobSheet::find($id);
        $references = Reference::where('jobsheet_id', $jobsheet->id)->get();
        $payables = Payable::where('jobsheet_id', $jobsheet->id)->get();
        $revisions   = Revision::where('jobsheet_id', $id)->get();

        return view('jobsheet.operation.show', compact('jobsheet','references','payables','revisions'));
    }

    public function jobsheet_edit(Request $request, $id)
    {
        $jobsheet   = JobSheet::find($id);

        $users      = User::all();
        $units      = MasterUnit::all();
        $ports      = MasterPort::all();
        $vendors    = MasterVendor::all();
        $customers  = MasterCustomer::all();
        $documents  = MasterDocument::all();
        $payables   = Payable::where('jobsheet_id', $id)->where('user_id', Auth::user()->id)->get();
        $references = Reference::where('jobsheet_id', $id)->get();
        $revisions  = Revision::where('jobsheet_id', $id)->get();

        return view('jobsheet.operation.edit', compact('jobsheet','documents','customers','vendors','units','ports','users','payables','references','revisions'));
    }

    public function jobsheet_update(Request $request, $id)
    {
        $this->validate($request,[
            'marketing_id'  => 'required|integer',
            'customer_id'   => 'required|integer',
            'poo_id'        => 'required|integer',
            'pod_id'        => 'required|integer',
            'description'   => 'required',
            'etd'           => 'required|date|before:eta',
            'eta'           => 'required|date',
            'partymeas'     => 'required',
            'party_unit_id' => 'required',
            'freight_type'  => 'required',

            'ref_no'            => 'required_with:ref_document_id',
            'ref_document_id'   => 'required_with:ref_no'
        ]);

        //==============UPDATE JOBSHEET============
        $convert_date = Carbon::createFromFormat('d-m-Y', $request->get('tanggal'));
        $convert_etd = Carbon::createFromFormat('d-m-Y', $request->get('etd'));
        $convert_eta = Carbon::createFromFormat('d-m-Y', $request->get('eta'));

        $jobsheet = JobSheet::find($id);
        $jobsheet->operation_id = Auth::user()->id;
        $jobsheet->customer_id  = $request->get('customer_id');
        $jobsheet->poo_id       = $request->get('poo_id');
        $jobsheet->pod_id       = $request->get('pod_id');
        $jobsheet->freight_type = $request->get('freight_type');
        $jobsheet->vessel       = $request->get('vessel');
        $jobsheet->partymeas    = $request->get('partymeas');
        $jobsheet->party_unit_id= $request->get('party_unit_id');
        $jobsheet->marketing_id = $request->get('marketing_id');
        $jobsheet->description  = $request->get('description');
        $jobsheet->remarks      = $request->get('remarks');
        $jobsheet->instruction  = $request->get('instruction');
        $jobsheet->date         = $convert_date->toDateString();
        $jobsheet->etd          = $convert_etd->toDateString();
        $jobsheet->eta          = $convert_eta->toDateString();

        if ($jobsheet->status == 'revisi-invoice' || 
            $jobsheet->status == 'revisi-pricing' ||
            $jobsheet->status == 'revisi-payable') 
        {
            $cek_rec = Receivable::where('jobsheet_id', $jobsheet->id)->count();
            if ($cek_rec < 1) {
                $jobsheet->status       = 'uncompleted';
            }else{
                $jobsheet->status       = 'completed';
            }
        }elseif ($jobsheet->status == 'revisi-marketing') {
            $jobsheet->status       = 'uncompleted';
        }
        
        $jobsheet->save();

        if ($jobsheet) 
        {
            //==============UPDATE REFERENCE============
            $ref_id = $request->reference_id;
            $ref_no = $request->get('ref_no');
            $doc_id = $request->get('ref_document_id');
            
            for ($i=0; $i < count($ref_no); $i++) 
            { 
                $reference = Reference::find($ref_id[$i]);
                
                if ($ref_id[$i] != 0) {
                    if (!empty($ref_no[$i]) && !empty($doc_id[$i])) 
                    {
                        $reference->jobsheet_id = $jobsheet->id;
                        $reference->document_id = $doc_id[$i];
                        $reference->ref_no      = $ref_no[$i];
                        $reference->save();
                    }else{
                        $reference->delete();
                    }
                } else {
                    if(!empty($ref_no[$i]) && !empty($doc_id[$i]))
                    {
                        Reference::create([
                            'jobsheet_id'=>$jobsheet->id,
                            'document_id'=>$doc_id[$i],
                            'ref_no'     =>$ref_no[$i]
                        ]);
                    }
                }
                
            }

            //==============UPDATE PAYABLE============
            $pay_id = $request->payable_id;
            $charge = $request->get('document_id');
            $vendor = $request->get('vendor_id');
            $unit   = $request->get('unit_id');
            $quanty = $request->get('quantity');
            // $pay_currency = $request->get('pay_currency');

            for ($j=0; $j < count($charge); $j++) 
            { 
                if ($pay_id[$j] != 0) 
                {
                    $payable = Payable::find($pay_id[$j]);
                    
                    if(!empty($charge[$j]) && !empty($vendor[$j]))
                    {
                        $payable->user_id       = Auth::user()->id;
                        $payable->jobsheet_id   = $jobsheet->id;
                        $payable->document_id   = $charge[$j];
                        $payable->vendor_id     = $vendor[$j];
                        $payable->unit_id       = $unit[$j];
                        $payable->quantity      = $quanty[$j];
                        // $payable->currency      = $pay_currency[$j];
                        $payable->save();
                    
                    }else{
                        $payable->delete();
                    }
                } else {
                    $payable = new Payable();
                    $payable->user_id       = Auth::user()->id;
                    $payable->jobsheet_id   = $jobsheet->id;
                    $payable->document_id   = $charge[$j];
                    $payable->vendor_id     = $vendor[$j];
                    $payable->unit_id       = $unit[$j];
                    $payable->quantity      = $quanty[$j];
                    $payable->save();
                }
            }

            $cekrevisi = Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'operation')->count();
            if($cekrevisi>0) {
                Revision::where('jobsheet_id', $jobsheet->id)->where('role', 'operation')->delete();
            }
        }

        return redirect()->route('operation.jobsheet.index');
    }

    public function jobsheet_revision(Request $request)
    {
        $revisions = Revision::where('role', 'operation')->get();
        return view('jobsheet.revision', compact('revisions'));
    }

    //==============================================================================================
    //              REQUEST
    //==============================================================================================
    
    public function request_list(Request $request)
    {
        $requests = RequestModel::where('status', 'requested')
            ->where('type','operation')
            ->where('user_id', Auth::user()->getKey())
            ->get();

        return view('request.operation.index', compact('requests'));
    }

    public function request_create(Request $request)
    {
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
        return view('request.operation.create', compact('jobsheets','controllerRole'));
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
        return view('request.operation.detail_jobsheet', compact('jobsheet','references','payables','revisions',
            'requestedDates','requestedPayableIds','defaultRequestDate', 'controllerRole'));
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
            return redirect()->route('operation.request.create');
        }

        return redirect()->route('operation.request.detail-jobsheet', ['id'=>$jobsheetId]);
    }

    public function request_index(Request $request)
    {

    }

    //==============================================================================================
    //              REPORT
    //==============================================================================================

    public function report_all(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::all();
        return view('jobsheet.operation.report', compact('jobsheets'));
    }

    public function report_completed(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', 'completed')->get();
        return view('jobsheet.operation.report', compact('jobsheets'));
    }

    public function report_uncompleted(Request $request, Datatables $datatables)
    {
        $jobsheets = JobSheet::where('status', 'uncompleted')->get();
        return view('jobsheet.operation.report', compact('jobsheets'));
    }

    public function report_requested(Request $request, Datatables $datatables)
    {

    }

    public function report_unrequested(Request $request, Datatables $datatables)
    {

    }
}
