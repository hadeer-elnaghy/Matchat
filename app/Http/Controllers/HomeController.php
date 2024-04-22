<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Country;
use App\Models\Government;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Place;
use App\Models\Playground;
use App\Models\Sport;
use App\Models\SportFacility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;
use Gate;

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
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {


$isUserHasRole = Auth::user()->roles()->first();
        if($isUserHasRole != ''){

            $userRole = $isUserHasRole['title'];

            if($userRole == 'Admin'){

                $playgrounds = Playground::all();
                $owners = User::all();
                session()->put('user_playgrounds', count($playgrounds));
                $invoices = Invoice::all();
                $invoicesNo = count( $invoices);
                session()->put('user_bookings', count($invoices));
                $user = Auth::user()->load('roles','media');
                session()->put('user', $user);


                $usersNo = User::where('id','!=',Auth::user()->id)->count();
                $bookingsNo =  Booking::count();

                return view('layouts.home', compact('bookingsNo','invoicesNo','usersNo','playgrounds','owners'));
              }

            else if($userRole == 'Manager'){
                $managerId = Auth::user()->id;
                $playgrounds = Playground::where('owner_id', $managerId)->get();
                $owners = User:: where('id', $managerId)->get();
                session()->put('user_playgrounds', $playgrounds->count());
                $invoices = Invoice::join('booking','invoices.id','=','booking.invoice_id')
                    -> whereHas('playground', function ($query) {
                        $query->where('owner_id' ,Auth::user()->id);
                    })->select('invoices.*')->get();
                $invoicesNo = $invoices->count();

                session()->put('user_bookings', $invoicesNo);
                $user = Auth::user()->load('roles','media');
                session()->put('user', $user);

                $bookingsNo = Booking::whereHas('invoice', function ($query) {
                    $query->whereHas('playground', function ($query) {
                        $query->where('owner_id' ,Auth::user()->id);
                    });
                })->count();
                return view('layouts.home', compact('bookingsNo','invoicesNo','invoicesNo','playgrounds','owners'));
            
            }else if($userRole == 'Employee'){
                abort_if(Gate::denies('booking_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

                $invoices = Invoice::join('booking','invoices.id','=','booking.invoice_id')
                        -> whereHas('playground', function ($query) {
                            $query->where('owner_id' ,Auth::user()->created_by);
                        })->orderBy('invoices.id', 'desc')->select('booking.*','invoices.*')->get();

                $users = User::all();
                 $playgrounds = Playground::where([['owner_id',Auth::user()->created_by],['open',1]])->get();

               $payments = PaymentMethod::all();

                return view('admin.Booking.only-booking-times', compact('users','invoices', 'playgrounds','payments'));

            }
        }


    }
}
