<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Activity;
use App\Booking;
use App\BusinessOwner;
use App\Customer;
use App\Employee;
use App\WorkingTime;

use Carbon\Carbon as Time;

class BookingController extends Controller
{
	public function __construct() {
		// Check auth, if not auth then redirect to login
        $this->middleware('auth:web_user', [
            'only' => [
                'indexCustomer',
                'newCustomer',
                'showCustomer',
            ]
        ]);
        $this->middleware('auth:web_admin', [
            'only' => [
                'indexAdmin',
                'showAdmin',
                'summary',
                'history',
                'assignEmployee',
            ]
        ]);

        $this->middleware('auth:web_user,web_admin',[
            'only' => [
                'store',
            ]
        ]);
    }

    /**
     * Show summary of bookings
     *
     * @return Response
     */
    public function summary()
    {
        return view('admin.summary', [
            'bookings' => Booking::allLatest('7'),
            'business' => BusinessOwner::first(),
            'latest' => Booking::allLatest('+7 days')
        ]);
    }

    /**
     * Show history of bookings
     *
     * @return Response
     */
    public function history()
    {
        return view('admin.history', [
            'bookings' => Booking::allLatest('7'),
            'business' => BusinessOwner::first(),
            'history' => Booking::allHistory()
        ]);
    }

    /**
     * Show booking by employee ID
     *
     * @param  String $monthYear    month year string from URL (mm-yyyy)
     * @param  String $employeeID   employee ID
     * @return view
     */
    public function showAdmin($monthYear, $employeeID = null)
    {
        // List of months
        $monthList = getMonthList($monthYear);

        // Set date string
        $date = monthYearToDate($monthYear);

        // Get bookings of the month
        $bookings = Booking::where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString())
            ->get()
            ->sortBy('date');

        // Find employee
        $employee = Employee::find($employeeID);

        if ($employeeID) {
            // Find working time by employee ID
            $workingTimes = WorkingTime::where('employee_id', $employeeID);
        }
        else {
            // Else get all working times
            $workingTimes = WorkingTime::all();
        }

        $workingTimes = $workingTimes->where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString())
            ->get();

        return view('admin.bookings', [
            'bookings'      => $bookings,
            'business'      => BusinessOwner::first(),
            'employeeID'    => $employeeID,
            'employee'      => $employee,
            'roster'        => $workingTimes,
            'date'          => $date,
            'dateString'    => $date->format('m-Y'),
            'months'        => $monthList
        ]);
    }

    /**
     * Show booking by employee ID
     *
     * @param  String $monthYear    month year string from URL (mm-yyyy)
     * @param  String $employeeID   employee ID
     * @return view
     */
    public function showCustomer($monthYear, $employeeID)
    {
        // Set date string
        $date = monthYearToDate($monthYear);

        // Get bookings of the month
        $bookings = Booking::where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString())
            ->get()
            ->sortBy('date');

        // Find working time by employee ID
        $workingTimes = WorkingTime::where('employee_id', $employeeID);

        // Get working times within the month
        $workingTimes = $workingTimes
            ->where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString())
            ->get();

        return view('customer.create_bookings', [
            'business'      => BusinessOwner::first(),
            'employeeID'    => $employeeID,
            'employee'      => Employee::find($employeeID),
            'roster'        => $workingTimes,
            'date'          => $date,
            'dateString'    => $date->format('m-Y'),
            'months'        => getMonthList($monthYear)
        ]);
    }

    /**
     * Show booking by employee ID
     *
     * @param  String $monthYear    month year string from URL (mm-yyyy)
     * @return view
     */
    public function newCustomer($monthYear)
    {
        // Set date string
        $date = monthYearToDate($monthYear);

        // Else get all working times
        $workingTimes = WorkingTime::all();

        // Get working times within the month
        $workingTimes = $workingTimes
            ->where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString());

        return view('customer.create_bookings', [
            'business'      => BusinessOwner::first(),
            'employeeID'    => null,
            'employee'      => null,
            'roster'        => $workingTimes,
            'date'          => $date,
            'dateString'    => $date->format('m-Y'),
            'months'        => getMonthList($monthYear)
        ]);
    }

    public function indexAdmin($monthYear)
    {
        // Get the date from URL
        $date = monthYearToDate($monthYear);

        // Get bookings of the month
        $bookings = Booking::where('date', '<=', $date->endOfMonth()->toDateString())
            ->where('date', '>=', $date->startOfMonth()->toDateString())
            ->get()
            ->sortBy('date');

        return view('admin.bookings', [
            'business'      => BusinessOwner::first(),
            'bookings'      => $bookings,
            'employeeID'    => null,
            'employee'      => null,
            'date'          => $date,
            'dateString'    => $date->format('m-Y'),
            'months'        => getMonthList($monthYear),
            'roster'        => WorkingTime::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info("An attempt to create a booking from the Business Owner Dashboard was made", $request->all());

        // If month year format requested
        if ($request->month_year) {
            $monthYear = explode('-', $request->month_year);
            $date = Time::createFromDate($monthYear[1], $monthYear[0], $request->day)->toDateString();
            $request->merge(['date' => $date]);
        }
        else {
            $date = $request->date;
        }

        // If customer is logged in, user their customer ID
        if (isUser()) {
            $request->merge(['customer_id' => Auth::id()]);
        }

        // Convert start time to proper time format
        $request->merge([
            'start_time' => toTime($request->start_time)
        ]);

        // Validation error messages
        $messages = [
            'start_time.date_format' => 'The :attribute field must be in the correct time format.',
            'end_time.date_format' => 'The :attribute field must be in the correct time format.',
            'customer_id.exists' => 'The :attribute does not exist.',
            'customer_id.is_on_booking' => 'The :attribute is already set on at that time.',
            'employee_id.exists' => 'The :attribute does not exist.',
            'employee_id.is_employee_working' => 'The :attribute is not working on that time.',
            'employee_id.is_on_booking' => 'The :attribute is already working on another booking at that time.',
            'activity_id.exists' => 'The :attribute does not exist.',
            'activity_id.is_end_time_valid' => 'The :attribute duration added on start time is invalid. Please add a start time that does not go to the next day.',
            'date.after' => 'The :attribute must be before today ' . toDate(getNow(), true) . '.',
        ];

        // Validation rules
        $rules = [
            'activity_id' => 'required|exists:activities,id|is_end_time_valid',
            'customer_id' => 'required|exists:customers,id|is_on_booking',
            'employee_id' => 'required|exists:employees,id|is_employee_working|is_on_booking',
            'start_time' => 'required|date_format:H:i:s',
            'date' => 'required|date|after:' . getDateNow(),
        ];

        // Attributes replace the field name with a more readable name
        $attributes = [
            'customer_id' => 'customer',
            'employee_id' => 'employee',
            'activity_id' => 'activity',
            'start_time' => 'start time',
            'end_time' => 'end time',
        ];

        Log::debug("Validating Business Owner input");

        // Validate form
        $this->validate($request, $rules, $messages, $attributes);

        // If end time is requested then do not calculate
        if ($request->end_time) {
            $request->merge([
                'end_time' => toTime($request->end_time)
            ]);
        }
        else {
            $request->merge([
                'end_time' => Booking::calcEndTime(Activity::find($request->activity_id)->duration, $request->start_time)
            ]);
        }

        // Create customer
        $booking = Booking::create([
            'customer_id' => $request->customer_id,
            'employee_id' => $request->employee_id,
            'activity_id' => $request->activity_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => $request->date,
        ]);

        // Check if user is an admin or user
        if (isAdmin()) {
            // Redirect to the business owner admin page
            $url = '/admin/bookings/' . toMonthYear(getNow());
            $user = 'Business Owner';
        }
        else {
            $url = '/bookings';
            $user = 'Customer';
        }

        Log::notice("Booking was created by " . $user . " ID " . Auth::id(), $booking->toArray());

        // Session flash
        session()->flash('message', 'Booking has successfully been created.');

        return redirect($url);
    }

    /**
     * View index of customer bookings
     */
    public function indexCustomer()
    {
        // Find customer bookings by customer ID on booking
        $bookings = Booking::all()
            ->where('customer_id', Auth::id())
            ->where('date', '>=', getDateNow())
            ->sortBy('date');

        return view('customer.bookings', compact('bookings'));
    }

    /**
     * Shows the create booking page
     */
    public function newCustomerBooking()
    {
        return view('customer.create_bookings');
    }
}
