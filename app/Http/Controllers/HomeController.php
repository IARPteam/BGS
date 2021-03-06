<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use App\User;
use App\Models\ApprovalsModel;
use App\Models\BudgetModel;
use Illuminate\Support\Facades\Input;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
   
    /** 
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function add()
    {
        $branch_details = DB::table('branches')->where('branch_id', Auth::user()->branch_id_)->get();
        $reviewer_list = DB::table('users')->where( 'title','=','HFA' )->orWhere('title','=','PFA')->get();

        return view('add', compact('branch_details','reviewer_list'));
    }

    public function add_post()
    {

    DB::table('budget')->insert( array(

            'user_id' => Auth::user()->id,
            'month' => Input::get('month'),
            'market_cost' => Input::get('market_cost'),
            'travelling_cost' => Input::get('travelling_cost'),
            'fuel_cost' => Input::get('fuel_cost'),
            'postage_cost' => Input::get('postage_cost'),
            'fax_cost' => Input::get('fax_cost'),
            'budget_status' => 'created',
            'business_status' => 'Not settled',
            'description' => Input::get('output_description'),
            'expected_premium' => Input::get('expected_premium'),
            'carry_over_balance' => '15000',
            'first_approval' => Input::get('reviewer'),
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now()
        ));

    $created_id = DB::getPdo()->lastInsertId();

    DB::table('approvals')->insert( array(

            'budget_id' => $created_id,
            'category' => 'Reviewed by:',
            //'link_id' => Hash::make($created_id),
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now(),
        ));

    DB::table('approvals')->insert( array(

            'budget_id' => $created_id,
            'category' => 'Recommended for budget by:',
            //'link_id' => Hash::make($created_id),
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now(),
        ));

    DB::table('approvals')->insert( array(

            'budget_id' => $created_id,
            'category' => 'Recommended for activity by:',
            //'link_id' => Hash::make($created_id),
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now(),
        ));

    DB::table('approvals')->insert( array(

            'budget_id' => $created_id,
            'category' => 'Approved by:',
            //'link_id' => Hash::make($created_id),
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now(),
        ));

$total =  Input::get('market_cost')+Input::get('travelling_cost')+Input::get('fuel_cost')+Input::get('postage_cost')+Input::get('fax_cost');
    DB::table('balance')->insert( array(

            'budget_id' => $created_id,
            'total_cost' => $total,
            'created_at'     =>   Carbon::now(),
            'updated_at'     =>  Carbon::now(),
        ));



    return redirect('/requests')->with('success', 'Budget Submitted Successfully!');

    }

    public function approve($id)
    {
        $reviewer_list_1 = DB::table('users')->where( 'title','=','HFA' )->get();
        $reviewer_list_2 = DB::table('users')->where( 'title','=','DGM' )->orWhere('title','=','PFA')->get();
        $reviewer_list_3 = DB::table('users')->where( 'title','=','GM' )->orWhere('title','=','HFA')->get();
        $reviewer_list_4 = DB::table('users')->where( 'title','=','DGM' )->get();

        $show_budget_details = DB::table('budget')->where('budget_id', $id )->first();
        $show_reviewer = DB::table('approvals')->where('budget_id', $id )->get();
        $show_reviewer2 = DB::table('approvals')->where('budget_id', $id )->first();
        $name = DB::table('users')->where('id', $show_budget_details->user_id )->first();
        $branch = DB::table('branches')->where('branch_id', $name->branch_id_ )->first();
        $reviewer_name = DB::table('users')->where('id', $show_reviewer2->approving_user_id )->first();
        $total = DB::table('balance')->where('budget_id', $id )->first();



        return view('approve', compact('show_budget_details','show_reviewer','show_status','total','name','branch','reviewer_list_1', 'reviewer_list_2', 'reviewer_list_3','reviewer_list_4','reviewer_name'));
    }

    public function approve_post($id)
    {

        if(Auth::user()->title == 'PFA'){
        $approve = ApprovalsModel::where('category','=','Reviewed by:')->where('budget_id',$id)->first();
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Approved';
        $approve->comment = Input::get('comment');
        $approve->forward_to = Input::get('reviewer');
        $approve->save();
        return redirect('approved')->with('success','Budget approved Successfully!');
        }

        elseif(Auth::user()->title == 'HFA'){
        $approve = ApprovalsModel::where('category','=','Recommended for budget by:')->where('budget_id',$id)->first();
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Approved';
        $approve->comment = Input::get('comment');
        $approve->forward_to = Input::get('reviewer');
        $approve->save();
        return redirect('approved')->with('success','Budget approved Successfully!');
        }

        elseif(Auth::user()->title == 'DGM'){
        $approve = ApprovalsModel::where('category','=','Recommended for activity by:')->where('budget_id',$id)->first();
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Approved';
        $approve->comment = Input::get('comment');
        $approve->forward_to = Input::get('reviewer');
        $approve->save();
        return redirect('approved')->with('success','Budget approved Successfully!');
        }

        elseif(Auth::user()->title == 'GM'){
        
        $approve = ApprovalsModel::where('category','=','Approved by:')->where('budget_id',$id)->first();
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Approved';
        $approve->comment = Input::get('comment');
        $approve->forward_to = Input::get('reviewer');
        $approve->save();

        $approve = BudgetModel::where('budget_id', $id)->first();
        $approve->budget_status = 'Approved';
        $approve->save();


        return redirect('approved')->with('success','Budget approved Successfully!');


        }
    else{

        return redirect('approved')->with('failure','Sorry You Are Not Authorized to Perform This Operation');
    }

    }

/*
    public function reject_post($id)
    {
        if(Auth::user()->title == 'PFA'){
        $approve = Approvals::where('category','=','Reviewed by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Rejected';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Rejected Successfully!');
        }

        elseif(Auth::user()->title == 'HFA'){
        $approve = Approvals::where('category','=','Recommended for budget by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Rejected';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Rejected Successfully!');
        }

        elseif(Auth::user()->title == 'DGM'){
        $approve = Approvals::where('category','=','Recommended for activity by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Rejected';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Rejected Successfully!');
        }

        elseif(Auth::user()->title == 'GM'){
        $approve = Approvals::where('category','=','Approved by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Rejected';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Rejected Successfully!');
        }
    else{

        return redirect('approved')->with('failure','Sorry You Are Not Authorized to Perform This Operation');
    }

    }

    public function return_post($id)
    {
        if(Auth::user()->title == 'PFA'){
        $approve = Approvals::where('category','=','Reviewed by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Returned';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Returned Successfully!');
        }

        elseif(Auth::user()->title == 'HFA'){
        $approve = Approvals::where('category','=','Recommended for budget by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Returned';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Returned Successfully!');
        }

        elseif(Auth::user()->title == 'DGM'){
        $approve = Approvals::where('category','=','Recommended for activity by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Returned';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Returned Successfully!');
        }

        elseif(Auth::user()->title == 'GM'){
        $approve = Approvals::where('category','=','Approved by:')->find($id);
        $approve->approving_user_id = Auth::user()->id;
        $approve->status = 'Returned';
        $approve->comment = Input::get('comment');
        $approve->save();
        return redirect('approved')->with('success','Budget Returned Successfully!');
        }
    else{

        return redirect('approved')->with('failure','Sorry You Are Not Authorized to Perform This Operation');
    }

    }  
*/
    public function requests()
    {
        $list_requests = DB::table('budget')->where('user_id', Auth::user()->id)->get();

        return view('requests', compact('list_requests'));
    }

    public function reports()
    {
        return view('report');
    }

    public function follow($id)
    {
        $show_budget_details = DB::table('budget')->where('budget_id', $id )->get();
        $show_reviewer = DB::table('approvals')->where('budget_id', $id )->get();
        $show_reviewer2 = DB::table('approvals')->where('budget_id', $id )->first();
        $show_status = DB::table('approvals')->where('budget_id', $id )->where('category','=','Approved by:')->where('status','=','Approved')->count();
        $total = DB::table('balance')->where('budget_id', $id )->first();
        $reviewer_name = DB::table('users')->where('id', $show_reviewer2->approving_user_id )->first();

        $branch = DB::table('branches')->where('branch_id', Auth::user()->branch_id_ )->first();
        return view('follow', compact('show_budget_details','show_reviewer','show_status','total','reviewer_name','branch'));
    }

    public function edit_budget()
    {
        return view('edit_budget');
    }

    public function settle()
    {
        return view('settle');
    }

    public function settle_post()
    {
        //return view('settle');
    }

}
